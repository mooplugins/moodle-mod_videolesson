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
 * Delete input files in s3 input bucket
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson\task;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/videolesson/lib.php");
require_once("$CFG->dirroot/mod/videolesson/classes/conversion.php");
require_once("$CFG->dirroot/mod/videolesson/classes/ffprobe.php");

class delete_input_files extends \core\task\scheduled_task {

    public function get_name() {
        return 'Delete input files'; // use lang string
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
        mtrace('Check conversions...\n\n');

        $sources = $DB->get_records('videolesson_conv', ['uploaded' => 200, 'status' => 200, 'input_deleted' => 0]);

        $deleteobjects = [];
        foreach ($sources as $source) {
            $deleteobjects[] = $source->contenthash;
        }

        if (!$deleteobjects) {
            return;
        }

        $awshandler = new \mod_videolesson\aws_handler('input');
        $responses = $awshandler->delete_objects($deleteobjects);

        foreach ($responses as $prefix => $response) {

            if ($response['success']) {
                $DB->set_field('videolesson_conv', 'input_deleted', 1, ['contenthash' => $prefix]);
                $details = ['input_deleted' => $prefix ];
                $data = [
                    'type' => 'INFO',
                    'name' => 'S3',
                    'other' => json_encode($details),
                    'senttoadmin' => 0,
                ];
            } else {
                $data = [
                    'type' => 'ERROR',
                    'name' => 'S3',
                    'other' => json_encode($response['errors']),
                    'senttoadmin' => 0,
                ];
            }
            $log = new \mod_videolesson\logs(0, (object) $data);
            $log->create();
        }
    }
}
