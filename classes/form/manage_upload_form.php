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
 * Upload video form
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/mod/videolesson/lib.php");

class manage_upload_video extends moodleform {

    public function definition() {
        global $PAGE;
        $mform = $this->_form;
        $currentmaxbytes = get_config('moodlecourse', 'maxbytes');
        $fmrestrictions = get_string('maxsizeandattachmentsandareasize', 'moodle', ['size' => display_size($currentmaxbytes), 'attachments' => 10, 'areasize' => display_size($currentmaxbytes * 10)]);
        $mform->addElement('static', 'fprestriction', '', $fmrestrictions);

        $PAGE->requires->css('/mod/videolesson/upload.css');
        $mform->addElement(
            'filemanager',
            'videos',
            get_string('manage:upload:form:element:filemanager', 'mod_videolesson'),
            null,
            [
                'subdirs' => 0,
                'maxbytes' => $currentmaxbytes,
                'areamaxbytes' => $currentmaxbytes * 10,
                'maxfiles' => 10,
                'accepted_types' => ['.mp4', '.ts', '.webm', '.flv', '.avi', '.mpeg', '.mov'],
            ]
        );

        $customdata = $this->_customdata ?? [];
        $folderoptions = $customdata['folderoptions'] ?? [];
        $defaultfolder = $customdata['defaultfolder'] ?? 'uncategorized';
        $canmanagefolders = $customdata['canmanagefolders'] ?? false;

        if (!array_key_exists('uncategorized', $folderoptions)) {
            $folderoptions = ['uncategorized' => get_string('folder:uncategorized', 'mod_videolesson')] + $folderoptions;
        }

        if ($canmanagefolders) {
            $mform->addElement('select', 'targetfolder', get_string('folder:select', 'mod_videolesson'), $folderoptions);
            if (!array_key_exists($defaultfolder, $folderoptions)) {
                $defaultfolder = 'uncategorized';
            }
            $mform->setDefault('targetfolder', $defaultfolder);
            $mform->setType('targetfolder', PARAM_RAW);
        } else {
            $mform->addElement('hidden', 'targetfolder', 'uncategorized');
            $mform->setType('targetfolder', PARAM_RAW);
        }

        $mform->addElement('checkbox', 'subtitle', '', get_string('manage:upload:form:element:subtitle', 'mod_videolesson'));
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('cancel');
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('manage:upload:form:element:uploadbtn', 'mod_videolesson'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->setType('buttonar', PARAM_RAW);
    }

    function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);

        $usercontext = \context_user::instance($USER->id);
        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['videos'], 'id', false);

        $targetfolder = $data['targetfolder'] ?? 'uncategorized';
        if ($targetfolder !== 'uncategorized') {
            if (!is_numeric($targetfolder) || !\mod_videolesson\folder_manager::folder_exists((int)$targetfolder)) {
                $errors['targetfolder'] = get_string('error:folder:invalid', 'mod_videolesson');
            }
        }

        if (!$draftfiles) {
            $errors['videos'] = get_string('required');
        } else {
            $error = [];
            $awshandler = new \mod_videolesson\aws_handler('output');
            $existingprefixes = $awshandler->list_all_prefixes_array(); // all in the bucket

            foreach ($draftfiles as $file) {
                if (!in_array($file->get_contenthash(), $existingprefixes)) {
                    $ffprobe = videolesson_validation_ffprobe($file);
                    if ($ffprobe['error'] == true) {
                        $error[] = $file->get_filename() . ' : ' . $ffprobe['reason'] . print_r($ffprobe, true);
                    }
                }
            }

            if ($error) {
                $errors['videos'] = implode("</br>", $error);
            }
        }

        return $errors;
    }
}
