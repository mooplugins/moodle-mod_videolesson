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
 * View activity.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/locallib.php');

$cmid = required_param('id', PARAM_INT);
$activity = new \mod_videolesson\activity($cmid);
$course = $activity->get_course();
$instance = $activity->get_instance();

require_login($course, true, $activity->get_cm());

$cm = $activity->get_cm();
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/videolesson/view.php', ['id' => $cmid]);
$PAGE->set_title(format_string($instance->name));
$PAGE->add_body_class('limitedwidth');
$PAGE->set_heading(format_string($instance->name));
$PAGE->set_context($activity->get_context());
$activity->activity_header();
$activity->js_amd();
echo $OUTPUT->header();
echo $activity->content();
echo $OUTPUT->footer();
