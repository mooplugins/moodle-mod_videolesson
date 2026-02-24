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

use core\task\scheduled_task;
use mod_videolesson\conversion;
use mod_videolesson\logs as videolesson_logs;

class poll_stale_conversions extends scheduled_task {

    public function get_name() {
        return get_string('task:poll_conversions', 'mod_videolesson');
    }

    /**
     * Scheduled task entry point.
     */
    public function execute() {
        $access = new \mod_videolesson\access();
        if ($access->restrict()) {
            mtrace('mod_videolesson: No valid hosted license or no missing config');
            return;
        }
        mtrace('Starting to poll stale conversions...');

        $records = self::get_stale_conversions();
        $count = count($records);
        mtrace("Found $count stale conversions to poll");

        $conversion = new \mod_videolesson\conversion();
        foreach ($records as $record) {
            self::poll_conversion_status($record, $conversion);
        }

        mtrace("Finished polling stale conversions");
    }

    /**
     * Get all conversions eligible for polling.
     *
     * @return array
     */
    private function get_stale_conversions(): array {
        global $DB;

        // Start from the back forwards, and check for pending conversions with no completion or error messages.
        $endstatus = ['SUCCEEDED', 'COMPLETED', 'COMPLETE', 'ERROR'];
        list($in, $inparams) = $DB->get_in_or_equal($endstatus, SQL_PARAMS_NAMED);
        $sql = "SELECT *
                  FROM {videolesson_conv} conv
                 WHERE conv.timecreated < :timeboundary
                   AND conv.status = :status
                   AND (
                    SELECT COUNT(*)
                      FROM {videolesson_queue_msgs} msgs
                     WHERE msgs.objectkey = conv.contenthash
                       AND msgs.status $in
                       ) = 0";

        $params = array_merge($inparams, [
            'timeboundary' => time() - 7 * DAYSECS,
            'status' => conversion::CONVERSION_IN_PROGRESS
        ]);

        return $DB->get_records_sql($sql, $params, 0, 1000);
    }

    /**
     * Attempt to match a given conversion record with files remaining in S3.
     *
     * @param \stdClass $record the conversion record to check.
     * @param conversion $conversion Conversion handler to use.
     * @param $handler Optional AWS handler. Used for mocking in tests.
     */
    private function poll_conversion_status(\stdClass $record, conversion $conversion, $handler = null) {
        // Here we should attempt to pull files, as if we had a completion message from a service.
        if ($record->transcoder_status == conversion::CONVERSION_IN_PROGRESS ||
                $record->transcoder_status == conversion::CONVERSION_ACCEPTED) {

            // Get Elastic Transcoder files. If we found some, this was a win.
            $files = $conversion->get_transcode_files($record, $handler);
            $record->bucket_size = $files['totalsize'];

            $record->transcoder_status = count($files) > 0
                ? conversion::CONVERSION_FINISHED
                : conversion::CONVERSION_ERROR;
        }

        // And now, the status of all pending is completed, mark finished.
        $conversion->update_completion_status($record, $handler);

        mtrace("Finished polling stale conversion {$record->contenthash}");
    }

}
