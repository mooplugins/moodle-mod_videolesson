<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Logs
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson;

use core\persistent;
use core_text;

/**
 * Logs class.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logs extends persistent {
    /**
     * Database table.
     */
    const TABLE = 'videolesson_logs';

    /** @var int Maximum length of stored error_message values. */
    public const ERROR_MESSAGE_MAX_LENGTH = 500;

    /** @var string Default support inbox prefilled when hosted mode is first enabled. */
    public const DEFAULT_HOSTED_ERRORLOG_EMAIL = 'technical@scholarlms.com';

    /**
     * Allow-listed diagnostic field names for ERROR log payloads.
     */
    private const DIAGNOSTIC_FIELDS = [
        'error_code',
        'error_message',
        'error_type',
        'job_id',
        'contenthash',
        'source',
    ];

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'type' => [
                'type' => PARAM_TEXT,
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'other' => [
                'type' => PARAM_TEXT,
            ],
            'senttoadmin' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * Prefill the error-log recipient when hosted mode is newly enabled.
     *
     * Self-managed / external installs stay empty (no outbound email) unless an
     * admin sets a recipient. Hosted sites get the vendor support inbox once so
     * digests keep working; admins can clear the setting to disable.
     *
     * @param string|false|null $previoushostingtype Hosting type before the change.
     */
    public static function prefill_errorlog_email_for_hosted($previoushostingtype = null): void {
        if ($previoushostingtype === 'hosted') {
            return;
        }

        $email = get_config('mod_videolesson', 'errorlogemail');
        if ($email !== false && trim((string) $email) !== '') {
            return;
        }

        set_config('errorlogemail', self::DEFAULT_HOSTED_ERRORLOG_EMAIL, 'mod_videolesson');
    }

    /**
     * Build a minimized JSON diagnostic payload for ERROR (and related) logs.
     *
     * Only allow-listed fields are kept. Messages are truncated and obvious
     * credential-like substrings are redacted.
     *
     * @param array $fields Raw diagnostic fields.
     * @return string JSON-encoded payload suitable for the other column.
     */
    public static function encode_diagnostic(array $fields): string {
        $out = [];
        foreach (self::DIAGNOSTIC_FIELDS as $key) {
            if (!array_key_exists($key, $fields) || $fields[$key] === null || $fields[$key] === '') {
                continue;
            }
            $value = $fields[$key];
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $value = self::sanitize_diagnostic_value((string) $value, $key === 'error_message');
            if ($value === '') {
                continue;
            }
            $out[$key] = $value;
        }
        return json_encode($out);
    }

    /**
     * Redact credential-like substrings and optionally truncate.
     *
     * @param string $value Raw value.
     * @param bool $truncate Whether to apply the error_message length limit.
     * @return string
     */
    private static function sanitize_diagnostic_value(string $value, bool $truncate): string {
        $value = preg_replace('/AKIA[0-9A-Z]{16}/', '[REDACTED]', $value) ?? $value;
        $value = preg_replace(
            '/(?i)(secret|password|token|authorization|access[_-]?key)\s*[:=]\s*\S+/',
            '$1=[REDACTED]',
            $value
        ) ?? $value;

        if ($truncate && core_text::strlen($value) > self::ERROR_MESSAGE_MAX_LENGTH) {
            $value = core_text::substr($value, 0, self::ERROR_MESSAGE_MAX_LENGTH) . '...';
        }
        return $value;
    }
}
