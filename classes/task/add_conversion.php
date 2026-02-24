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
 * Adhoc task to send files to aws for transcoding
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

class add_conversion extends \core\task\adhoc_task {

    /**
     * Execute the task.
    */
    public function execute() {
        global $DB, $CFG;

        $conversion = new \mod_videolesson\conversion();

        $data = $this->get_custom_data();
        $fs = get_file_storage();

        $pathhash = $data->pathhash;
        $file = $fs->get_file_by_hash($pathhash);

        if (!$file) {
            mtrace('No file! exiting...');
            return;
        }

        $contenthash = $file->get_contenthash();

        $access = new \mod_videolesson\access();
        if ($access->restrict()) {
            $this->markfailed($conversion, $file);
            mtrace('mod_videolesson: No valid hosted license or no missing config');
            return;
        }
        mtrace('Add file to aws for conversion...');

        // Check.
        $prefix = $contenthash;

        mtrace('Checking bucket for object...');
        $awsoutput = new \mod_videolesson\aws_handler('output');

        $canupload = $awsoutput->canupload();
        if (!$canupload['can_upload']) {
            $this->markfailed($conversion, $file);
            mtrace('mod_videolesson: '. get_string('canupload:'.$canupload['code'], 'mod_videolesson'));
            return;
        }

        $result = $awsoutput->list_objects($prefix, '', '', true);

        if (!empty($result['Contents'])) {
            $uploaded = true;
            mtrace('Object found! no need to upload...');
        } else {
            $uploaded = false;
            mtrace('Object not found! need to upload...');
        }

        if(!$uploaded) {

            // Create conversion record.
            mtrace('Create conversion record.');
            $conversionrecord = $DB->get_record('videolesson_conv', ['contenthash' => $file->get_contenthash()]);
            $settings = $conversion->get_conversion_settings($conversionrecord); // Get convession settings.

            mtrace('Uploading file...');

            // Upload!
            mtrace('metadata:'.$file->get_contenthash());
            $awsinput = new \mod_videolesson\aws_handler('input');

            $put = $awsinput->put_object($contenthash, $file, ['Metadata' => $settings], true);

            if ($put['success']) {
                mtrace('File uploaded!');
                $uploaded = true;
            } else {
                $this->markfailed($conversion, $file);
                mtrace('Upload failed!'. $put['error_message']);
            }

        }

        if ($uploaded) {
            $conversionrecord->uploaded = $conversion::CONVERSION_FINISHED;
            $conversionrecord->status = $conversion::CONVERSION_IN_PROGRESS;
            $conversionrecord->transcoder_status = $conversion::CONVERSION_IN_PROGRESS;
            $DB->update_record('videolesson_conv', $conversionrecord);
            $file->delete();
        }
    }

    private function markfailed($classconversion, $file) {
        global $DB;
        $conversionrecord = $DB->get_record('videolesson_conv', ['contenthash' => $file->get_contenthash()]);
        $conversionrecord->uploaded = $classconversion::CONVERSION_UPLOAD_ERROR;
        $DB->update_record('videolesson_conv', $conversionrecord);
    }
}
