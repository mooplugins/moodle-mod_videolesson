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
 * License page
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/videolesson/classes/form/provision_form.php');
$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

$license = new \mod_videolesson\license();

$url = new moodle_url('/mod/videolesson/provision.php');
$PAGE->set_url($url);

admin_externalpage_setup('videolessonprovision');

$PAGE->set_context($context);
$PAGE->set_title(get_string('provision:header', 'mod_videolesson'));
$PAGE->set_heading(get_string('provision:header', 'mod_videolesson'));

$mform = new provision_form();
if ($mform->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present
    redirect($url);
} else if ($data = $mform->get_data()) {
    \core\notification::add(
        get_string('provision:request:accepted', 'mod_videolesson'),
        \core\output\notification::NOTIFY_SUCCESS
    );
    redirect($url);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
