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
 * Admin license field
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson;
// Ensure the file is accessible in Moodle.
defined('MOODLE_INTERNAL') || die();

class admin_setting_moopluginlicense extends \admin_setting_configtext {

    public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype = PARAM_RAW) {
        // Call parent constructor to initialize the setting
        parent::__construct($name, $visiblename, $description, $defaultsetting, $paramtype);
    }

    public function validate($data) {
        // Instantiate the license class to handle activation and deactivation
        $license = new \mod_videolesson\license();

        // Check if the hosting type is set to 'hosted' (via POST data)
        if (isset($_POST['s_mod_videolesson_hosting_type']) && $_POST['s_mod_videolesson_hosting_type'] === 'hosted') {
            // Attempt to activate the license
            $result = $license->activate($data);

            // If activation fails, return the error message from the license system
            if ($result['result'] === 'error') {
                return !empty($result['message']) ? $result['message'] : get_string('license:invalid', 'mod_videolesson');
            }

            if ($result['type'] === 'self') {
                return !empty($result['message']) ? $result['message'] : get_string('license:invalid', 'mod_videolesson');
            }
        } else {
            // Deactivate the license if the hosting type is not 'hosted'
            $result = $license->deactivate();
        }

        return true;
    }
}
