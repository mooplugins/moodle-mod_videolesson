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
 * External API for bulk-moving BBB recordings within BBB folders
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
use core_external\external_multiple_structure;
use context_system;

/**
 * Bulk move BBB recordings external API.
 *
 * @package    mod_videolesson
 */
class bulk_move_bbb_recordings extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'recordingids' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'BBB recording id')
            ),
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
            'success' => new external_value(PARAM_BOOL, 'Whether all moves succeeded'),
            'moved' => new external_value(PARAM_INT, 'Number of recordings moved'),
            'error' => new external_value(PARAM_TEXT, 'Error message if failed', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Execute.
     *
     * @param array $recordingids
     * @param string|null $folderid
     * @return array
     */
    public static function execute(array $recordingids, $folderid = 'bbb'): array {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('mod/videolesson:manage', $context);

        $params = self::validate_parameters(self::execute_parameters(), [
            'recordingids' => $recordingids,
            'folderid' => $folderid,
        ]);

        $target = $params['folderid'];
        if ($target === '' || $target === 'bbb' || $target === '0' || $target === null) {
            $target = null;
        } else {
            $target = (int) $target;
        }

        $moved = 0;
        foreach ($params['recordingids'] as $recordingid) {
            if (\mod_videolesson\bbb_recordings::move_recording((string) $recordingid, $target)) {
                $moved++;
            }
        }

        if ($moved === count($params['recordingids'])) {
            return ['success' => true, 'moved' => $moved];
        }

        return [
            'success' => false,
            'moved' => $moved,
            'error' => 'Some BBB recordings could not be moved.',
        ];
    }
}
