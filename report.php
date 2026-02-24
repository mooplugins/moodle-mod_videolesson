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
 * Reports page.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/locallib.php');
require_once($CFG->dirroot . '/mod/videolesson/classes/util.php');

$access = new \mod_videolesson\access();
if ($access->restrict()) {
    $PAGE->set_title($heading);
    $PAGE->set_heading($heading);
    echo $OUTPUT->header();
    echo $access->get_message();;
    echo $OUTPUT->footer();
    die();
}

$action = optional_param('action', 'all', PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);
$cmid = optional_param('id', 0, PARAM_INT);
$contenthash = optional_param('contenthash', '', PARAM_TEXT);
$videoconv = null;
$libraryurl = new moodle_url('/mod/videolesson/library.php');

if (!$cmid || $action == 'video') {
    if (!$contenthash) {
        redirect($libraryurl);
    }

    require_login(null, false, null);

    $context = context_course::instance(SITEID);
    $analytics = new \mod_videolesson\video_analytics($contenthash);
    $topdata = $analytics->get_data();

    // Try to get video info from videolesson_conv (AWS videos)
    $videoconv = $DB->get_record('videolesson_conv', ['contenthash' => $contenthash]);

    // If not found, try to get from external sources or activity instances
    if (!$videoconv) {
        // Try to get from videolesson_data_external
        $external = $DB->get_record('videolesson_data_external', ['sourcehash' => $contenthash]);
        if ($external) {
            // Create a compatible object for external videos
            $videoname = 'External Video';
            if ($external->externalvideoid && $external->externaltype) {
                $videoname = ucfirst($external->externaltype) . ': ' . $external->externalvideoid;
            } else if ($external->sourceurl) {
                // Use a shortened version of the URL as the name
                $urlparts = parse_url($external->sourceurl);
                $videoname = $urlparts['host'] ?? $external->sourceurl;
                if (strlen($videoname) > 50) {
                    $videoname = substr($videoname, 0, 47) . '...';
                }
            }
            $videoconv = (object) [
                'contenthash' => $external->sourcehash,
                'name' => $videoname,
            ];
        } else {
            // Fallback: try to get name from first activity instance using this video
            $instances = $DB->get_records('videolesson', ['sourcedata' => $contenthash], 'id', 'id, name', 0, 1);
            if (!empty($instances)) {
                $instance = reset($instances);
                $videoconv = (object) [
                    'contenthash' => $contenthash,
                    'name' => $instance->name,
                ];
            }
        }
    }
} else {
    if (!$cm = get_coursemodule_from_id('videolesson', $cmid)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
        throw new moodle_exception('coursemisconf');
    }

    require_login($course, false, $cm);
    $context = context_module::instance($cm->id);
    $activity = new \mod_videolesson\activity($cmid);
    $analytics = new \mod_videolesson\cm_analytics($activity, $userid);
    $contenthash = $analytics->sourcehash;
    $topdata = $analytics->get_data();
}
$PAGE->set_context($context);
$url = new moodle_url('/mod/videolesson/report.php', ['id' => $cmid, 'action' => 'all', 'contenthash' => $contenthash]);
$PAGE->set_url('/mod/videolesson/report.php', ['id' => $cmid]);
$PAGE->activityheader->set_attrs([
    'hidecompletion' => true,
    'description' => ''
]);

$topdata['urls'] = [
    'impressions' => $url->out(true, ['action' => 'impressions']),
    'plays' => $url->out(true, ['action' => 'plays']),
    'unique' => $url->out(true, ['action' => 'unique']),
];

if (!$cmid) {
    $PAGE->navbar->add('Library', $libraryurl);
}

if ($videoconv) {
    $heading = get_string('report:all:header', 'mod_videolesson', $videoconv->name);
    $PAGE->set_heading($heading);
    $PAGE->set_title($heading);
    $videoreporturl = new moodle_url('/mod/videolesson/report.php', ['contenthash' => $contenthash, 'action' => 'video']);
    $PAGE->navbar->add($videoconv->name, $videoreporturl);
}

echo $OUTPUT->header();

$options = ['0' => get_string('all')];
$instances = $DB->get_records('videolesson', ['sourcedata' => $contenthash]);
foreach ($instances as $instance) {
    $cm = get_coursemodule_from_instance('videolesson', $instance->id);
    if ($cm) {
        $course = get_course($cm->course);
        $options[$cm->id] = $course->fullname . ' - ' . $cm->name;
    }
}

$url = new moodle_url('/mod/videolesson/report.php', ['contenthash' => $contenthash]);
echo $OUTPUT->single_select($url, 'id', $options, $cmid, []);

switch ($action) {
    case 'user':
        $userid = required_param('userid', PARAM_INT);
        echo $OUTPUT->render_from_template('mod_videolesson/report_user', []);
        break;

    case 'impressions':
        $impressions = $analytics->get_impression(true);
        echo $OUTPUT->render_from_template('mod_videolesson/report_all', $topdata);
        echo $OUTPUT->render_from_template('mod_videolesson/report_impressions', $impressions);
        break;

    case 'plays':
        $plays = $analytics->get_plays(true);

        if ($analytics->user) {
            $plays['user'] = $analytics->user;
        }

        echo $OUTPUT->render_from_template('mod_videolesson/report_all', $topdata);
        echo $OUTPUT->render_from_template('mod_videolesson/report_plays', $plays);
        $PAGE->requires->js_call_amd('mod_videolesson/report', 'init', []);
        break;

    case 'play':
        $playid = required_param('playid', PARAM_INT);
        $play = $analytics->get_play_data($playid);
        echo $OUTPUT->render_from_template('mod_videolesson/report_play_data', $play);
        break;

    case 'unique':
        $views = $analytics->get_unique_views();
        $id = required_param('id', PARAM_INT);
        $playsurl = new moodle_url('/mod/videolesson/report.php', ['id' => $id, 'action' => 'plays', 'contenthash' => $contenthash]);
        $views['playsurl'] = $playsurl->out();
        echo $OUTPUT->render_from_template('mod_videolesson/report_all', $topdata);
        echo $OUTPUT->render_from_template('mod_videolesson/report_unique', $views);
        break;

    default:
        $tabdata = $analytics->get_chart_data_for_tabs();
        echo $OUTPUT->render_from_template('mod_videolesson/report_all', $topdata);
        echo $OUTPUT->render_from_template('mod_videolesson/report_charts', $tabdata);
        break;
}

echo $OUTPUT->footer();
