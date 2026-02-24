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
 * Library page
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use mod_videolesson\local\services\video_list_service;
use mod_videolesson\library\action_router;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/weblib.php');
require_once($CFG->dirroot . '/mod/videolesson/classes/util.php');
require_once($CFG->dirroot . '/mod/videolesson/lib.php');
require_once($CFG->dirroot . '/mod/videolesson/classes/local/services/video_list_service.php');

// No guest autologin
require_login(0, false);
global $DB;

$systemcontext = \context_system::instance();
$pageurl = new \moodle_url('/mod/videolesson/library.php');
$PAGE->set_url($pageurl);

// Manual page setup (replacing admin_externalpage_setup)
$PAGE->set_context($systemcontext);

$heading = get_string('header_manage_videos', 'mod_videolesson');

// Access check
$access = new \mod_videolesson\access();
$isrestricted = $access->restrict();
$islibraryrestricted = $access->restrict_library();
if ($isrestricted || $islibraryrestricted) {
    $PAGE->set_title($heading);
    $PAGE->set_heading($heading);
    echo $OUTPUT->header();
    echo $islibraryrestricted
        ? $access->get_library_restriction_message()
        : $access->get_message();
    echo $OUTPUT->footer();
    die();
}

// Capability check
if (!has_capability('mod/videolesson:manage', $systemcontext)) {
    redirect($CFG->wwwroot,
        get_string('error:nocap:access', 'mod_videolesson'),
        null,
        \core\output\notification::NOTIFY_WARNING);
}

// Route to appropriate action handler
$action = optional_param('action', 'list', PARAM_TEXT);
action_router::execute($action);
