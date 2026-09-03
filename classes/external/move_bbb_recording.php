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
 * External API for moving BBB recordings within BBB folders
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use context_system;

/**
 * Move BBB recording external API.
 *
 * @package    mod_videolesson
 */
class move_bbb_recording extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'recordingid' => new external_value(PARAM_TEXT, 'BBB recording id', VALUE_REQUIRED),
            'folderid' => new external_value(PARAM_TEXT, 'Target BBB folder id or bbb for root', VALUE_DEFAULT, 'bbb'),
        ]);
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'error' => new external_value(PARAM_TEXT, 'Error message if failed', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Execute.
     *
     * @param string $recordingid
     * @param string|null $folderid
     * @return array
     */
    public static function execute(string $recordingid, $folderid = 'bbb'): array {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('mod/videolesson:manage', $context);

        $params = self::validate_parameters(self::execute_parameters(), [
            'recordingid' => $recordingid,
            'folderid' => $folderid,
        ]);

        $target = $params['folderid'];
        if ($target === '' || $target === 'bbb' || $target === '0' || $target === null) {
            $target = null;
        } else {
            $target = (int) $target;
        }

        $success = \mod_videolesson\bbb_recordings::move_recording($params['recordingid'], $target);

        if ($success) {
            return ['success' => true];
        }

        return [
            'success' => false,
            'error' => 'Failed to move BBB recording. Target must be within BBB folders.',
        ];
    }
}
