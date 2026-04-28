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
 * Process conversions
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson\task;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/videolesson/lib.php");
require_once("$CFG->dirroot/mod/videolesson/classes/conversion.php");
require_once("$CFG->dirroot/mod/videolesson/classes/ffprobe.php");

/**
 * Process conversions task
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_conversions extends \core\task\scheduled_task {
    /**
     * Get the name of the task
     * @return string The name of the task
     */
    public function get_name() {
        return get_string('task:processconversions', 'mod_videolesson');
    }
    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $access = new \mod_videolesson\access();
        if ($access->restrict()) {
            mtrace('mod_videolesson: No valid hosted license or no missing config');
            return;
        }

        // Check for pending conversions.
        $pendingconversions = $DB->count_records_select('videolesson_conv', 'transcoder_status <> 200');

        // Check for pending/processing subtitles.
        $pendingsubtitles = $DB->count_records_select(
            'videolesson_subtitles',
            "status IN ('pending', 'processing')"
        );

        // Check subtitles via S3 (new method - no SQS dependency).
        if ($pendingsubtitles > 0) {
            mtrace('mod_videolesson: Found ' . $pendingsubtitles . ' pending/processing subtitles. Checking subtitle files.');
            $subtitleservice = \mod_videolesson\local\services\subtitle_service::class;
            $results = $subtitleservice::check_pending_subtitles_via_s3();
            mtrace('mod_videolesson: Subtitle S3 check results - Checked: ' . $results['checked'] .
                ', Completed: ' . $results['completed'] .
                ', Failed: ' . $results['failed'] .
                ', Still pending: ' . $results['still_pending']);
        }

        // Check conversions via DynamoDB (hosted API or self-managed table).
        if ($pendingconversions > 0) {
            $config = get_config('mod_videolesson');
            $hostingtype = $config->hosting_type ?? '';
            if ($hostingtype === 'hosted') {
                mtrace('mod_videolesson: Found ' . $pendingconversions . ' pending conversions. Processing via hosted API.');
            } else {
                mtrace('mod_videolesson: Found ' . $pendingconversions . ' pending conversions. Processing via DynamoDB.');
            }

            $conversion = new \mod_videolesson\conversion();

            mtrace('mod_videolesson: Updating pending conversions');
            $updated = $conversion->update_pending_conversions();
            if (count($updated)) {
                // Update cache.
                $awshandler = new \mod_videolesson\aws_handler('output');
                $awshandler->list_all_prefixes_array(true); // All in the bucket.
            }
            mtrace('mod_videolesson: Total number of updated conversions: ' . count($updated));
        } else if ($pendingsubtitles == 0) {
            mtrace('mod_videolesson: No pending conversions or subtitles. No need to process.');
        }
    }
}
