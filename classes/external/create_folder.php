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
 * External API for creating folders
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
 * Create folder external API.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_folder extends external_api {
    /**
     * Returns the description of the method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_TEXT, 'Folder name', VALUE_REQUIRED),
            'parentid' => new external_value(
                PARAM_ALPHANUMEXT,
                'Parent folder ID, 0/null for library root, or bbb for BBB root',
                VALUE_DEFAULT,
                null
            ),
        ]);
    }

    /**
     * Returns the description of the method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'folderid' => new external_value(PARAM_INT, 'Folder ID if created', VALUE_OPTIONAL),
            'error' => new external_value(PARAM_TEXT, 'Error message if failed', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Execute the external function
     *
     * @param string $name Folder name
     * @param string|int|null $parentid Parent folder ID or 'bbb'
     * @return array Result
     */
    public static function execute(string $name, $parentid = null): array {
        // Validate context and permissions.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('mod/videolesson:manage', $context);

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'parentid' => $parentid,
        ]);

        $scope = \mod_videolesson\folder_manager::SCOPE_LIBRARY;
        $parent = $params['parentid'];
        if ($parent === 'bbb' || $parent === 'BBB') {
            $parent = null;
            $scope = \mod_videolesson\folder_manager::SCOPE_BBB;
        } else if ($parent === null || $parent === '' || $parent === 0 || $parent === '0') {
            $parent = null;
        } else {
            $parent = (int) $parent;
            $parentfolder = \mod_videolesson\folder_manager::get_folder($parent);
            if (
                $parentfolder
                && \mod_videolesson\folder_manager::normalise_scope($parentfolder->scope ?? '')
                    === \mod_videolesson\folder_manager::SCOPE_BBB
            ) {
                $scope = \mod_videolesson\folder_manager::SCOPE_BBB;
            }
        }

        $folderid = \mod_videolesson\folder_manager::create_folder($params['name'], $parent, $scope);

        if ($folderid) {
            return [
                'success' => true,
                'folderid' => $folderid,
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to create folder. Check if max depth is reached or name is invalid.',
            ];
        }
    }
}
