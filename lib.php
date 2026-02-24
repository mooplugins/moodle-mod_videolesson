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
 * Library of interface functions and constants.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/videolesson/locallib.php");
require_once("$CFG->libdir/completionlib.php");
require_once("$CFG->dirroot/course/lib.php");

/**
 * Get icon mapping for font-awesome.
 */
function mod_videolesson_get_fontawesome_icon_map() {
    return [
        'mod_videolesson:i/play' => 'fa-play',
        'mod_videolesson:i/error' => 'fa-exclamation-triangle',
        'mod_videolesson:i/mp4' => 'fa-file-video',
        'mod_videolesson:i/retry' => 'fa-arrows-rotate',
        'mod_videolesson:i/missing' => 'fa-exclamation-circle',
        'mod_videolesson:i/chart' => 'fa-chart-simple',
    ];
}

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function videolesson_supports($feature) {
    switch ($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_MOD_INTRO:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Prepares data for use in the videolesson module.
 *
 * This function processes the input data, performing necessary transformations
 * or validations to ensure it is ready for further use within the videolesson module.
 *
 * @param mixed $data The data to be prepared. The format and type of the data
 *                    depend on the specific requirements of the videolesson module.
 * @return mixed The processed and prepared data. The return type depends on
 *               the input data and the transformations applied.
 */
function videolesson_preparedata($data) {
    global $DB;
    $cmid = $data->coursemodule;

    $context = context_module::instance($cmid);
    switch ($data->source) {
        case VIDEO_SRC_GALLERY:
            $data->sourcedata = $data->contenthash;

            break;
        case VIDEO_SRC_EXTERNAL:
            // Auto-detect: can be direct video URL, YouTube/Vimeo URL, or embed code
            $input = trim($data->videourl ?? '');
            $sourcetype = \mod_videolesson\util::detect_external_source_type($input);
            $url = \mod_videolesson\util::extract_url_from_embed_code($input);

            if (!$url || !$sourcetype) {
                throw new \moodle_exception('error:invalidvideourl', 'mod_videolesson');
            }

            switch ($sourcetype) {
                case 'youtube':
                    $videoid = \mod_videolesson\util::extract_youtube_video_id($url);
                    if ($videoid) {
                        $data->sourcedata = 'youtube:' . $videoid;
                    } else {
                        throw new \moodle_exception('error:invalidyoutubeurl', 'mod_videolesson');
                    }
                    break;

                case 'vimeo':
                    $videoid = \mod_videolesson\util::extract_vimeo_video_id($url);
                    if ($videoid) {
                        $data->sourcedata = 'vimeo:' . $videoid;
                    } else {
                        throw new \moodle_exception('error:invalidvimeourl', 'mod_videolesson');
                    }
                    break;

                case 'direct_video':
                    $data->sourcedata = $url;
                    break;

                case 'unsupported_embed':
                    // Store the embed code or URL as-is (no normalization)
                    // Frontend will handle display but disable tracking
                    $data->sourcedata = $input; // Keep original input (could be iframe or URL)
                    break;
            }
            break;
        default:
            // Upload
            $data->source = VIDEO_SRC_GALLERY;

            // Check if there's a submitted draft item for new video
            if ($draftitemid = file_get_submitted_draft_itemid('newvideo')) {
                // Save draft area files
                file_save_draft_area_files($draftitemid, $context->id, 'mod_videolesson', 'toaws', 0, []);

                // Get file storage
                $fs = get_file_storage();

                // Get area files
                $files = $fs->get_area_files($context->id, 'mod_videolesson', 'toaws', 0, 'sortorder DESC, id ASC', false);

                if (count($files)) {
                    // Get the first file
                    $file = reset($files);
                    $data->sourcedata = $file->get_contenthash();

                    // Add the file to sources
                    $opts = [];
                    if ($data->subtitle) {
                        $opts['subtitle'] = 1; // temp. we will add more opts in future like what languages but for now, just a flag. default langs will be used.
                    }
                    videolesson_maybe_addfiletosources($file, $opts);
                }
            }
    }

    // Check if there's a submitted draft item for thumbnail
    if ($draftitemid = file_get_submitted_draft_itemid('thumbnail')) {
        // Save draft area files for thumbnails
        file_save_draft_area_files($draftitemid, $context->id, 'mod_videolesson', 'thumbnail', 0, []);
    }

    if (!$data->addthumbnail) {
        // Get the file information from the database
        $file_record = $DB->get_record(
            'files',
            ['contextid' => $context->id, 'component' => 'mod_videolesson', 'filearea' => 'thumbnail']
        );

        if ($file_record) {
            // Get the file object
            $fs = get_file_storage();
            $file = $fs->get_file(
                $file_record->contextid,
                $file_record->component,
                $file_record->filearea,
                $file_record->itemid,
                $file_record->filepath,
                $file_record->filename
            );

            if ($file) {
                // Delete the file
                $file->delete();
            }
        }
    }

    $options = [
        'player' => [
            'seek'  => (int) ($data->disableseek ?? 0),
            'disablepip' => !empty($data->disablepip) ? 1 : 0,
            'disablespeed' => !empty($data->disablespeed) ? 1 : 0,
        ]
    ];


    $data->options = json_encode($options);

    return $data;
}

/**
 * Saves a new instance of the mod_videolesson into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $data An object from the form.
 * @param mod_videolesson_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function videolesson_add_instance($data, $mform = null) {
    global $DB;
    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    $data = videolesson_preparedata($data);

    // Insert data into the videolesson table
    $id = $DB->insert_record('videolesson', $data);

    return $id;
}

/**
 * Updates an instance of the mod_videolesson in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_videolesson_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */

function videolesson_update_instance($data, $mform = null) {
    global $DB;

    // Update the modification time
    $data->timemodified = time();

    // Set the ID to the instance
    $data->id = $data->instance;

    $data = videolesson_preparedata($data);

    // Update the videolesson record in the database
    return $DB->update_record('videolesson', $data);
}

/**
 * Removes an instance of the mod_videolesson from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function videolesson_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('videolesson', ['id' => $id]);
    if (!$exists) {
        return false;
    }

    $DB->delete_records('videolesson', ['id' => $id]);

    if ($exists->source == VIDEO_SRC_GALLERY) {
        $videosource = new \mod_videolesson\videosource();
        $videosource->output_delete($exists->source);
    }

    return true;
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@see file_browser::get_file_info_context_module()}.
 *
 * @package     mod_videolesson
 * @category    files
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return string[].
 */
function videolesson_get_file_areas($course, $cm, $context) {
    $areas = [];
    $areas['thumbnail'] = 'Thumbnail';
    return $areas;
}

/**
 * File browsing support for mod_videolesson file areas.
 *
 * @package     mod_videolesson
 * @category    files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info Instance or null if not found.
 */
function videolesson_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the mod_videolesson file areas.
 *
 * @package     mod_videolesson
 * @category    files
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The mod_videolesson's context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 */
function videolesson_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = []) {

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    if ($filearea !== 'thumbnail') {
        return false;
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_videolesson', $filearea, $args[0], '/', $args[1]);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Conditionally adds a file to the videolesson sources if it is not already present.
 *
 * This function checks if a file, identified by its content hash, is already present
 * in the `videolesson_conv` table. If the file is not found, it proceeds to add the file
 * to the sources by calling `videolesson_addfiletosources`.
 *
 * @param object $file The file object to be checked and possibly added. It should
 *                     have a method `get_contenthash()` that returns the unique hash
 *                     of the file's content.
 * @return void
 * @throws dml_exception If there is an issue with the database query.
 */
function videolesson_maybe_addfiletosources($file, $opts = []) {
    global $DB;

    $record = $DB->get_record('videolesson_conv', ['contenthash' => $file->get_contenthash()]);
    if (!$record) {
        videolesson_addfiletosources($file, $opts);
    }
}

/**
 * Adds a file to the videolesson sources by creating a conversion record and queuing it for processing.
 *
 * @param object $file The file object to be added. It should have a method `get_contenthash()`
 *                     that returns the unique hash of the file's content.
 * @param array $opts Optional parameters for conversion (e.g., subtitle flags).
 * @return void
 * @throws dml_exception If there is an issue with database operations.
 */
function videolesson_addfiletosources($file, $opts = []) {
    $conversion = new \mod_videolesson\conversion();
    $conversion->create_conversion($file, $opts);

    videolesson_sendfiletoaws($file->get_pathnamehash());
}

/**
 * Sends a file to AWS/Lambda for processing by queuing an ad-hoc task.
 *
 * This function sets up a task to add a file conversion request to AWS/Lambda based on the
 * file's pathname hash and queues it for asynchronous execution.
 *
 * @param string $pathhash The pathname hash of the file to be sent to AWS/Lambda.
 * @return void
 * @throws coding_exception If there is an issue with task creation or queuing.
 */
function videolesson_sendfiletoaws($pathhash) {
    $sendtoawstask = new \mod_videolesson\task\add_conversion();
    $data = ['pathhash' => $pathhash];
    $sendtoawstask->set_custom_data($data);
    \core\task\manager::queue_adhoc_task($sendtoawstask, true);
}

/**
 * Validates and processes a video file's metadata.
 *
 * This function checks if the video file already exists in the database. If not,
 * it extracts metadata from the file using FFProbe/Lambda and saves the metadata to the database.
 *
 * @param object $file The file object to be validated and processed. It should
 *                     have methods `get_contenthash()` and `get_filename()`.
 * @return array An associative array with the following keys:
 *               - 'contenthash': The content hash of the file.
 *               - 'reason': A message indicating the result of the operation (success or error reason).
 *               - 'error': A boolean indicating if there was an error (true if an error occurred, false otherwise).
 * @throws dml_exception If there is an issue with database operations or FFProbe/Lambda.
 */
function videolesson_validation_ffprobe($file) {
    global $DB;

    if ($DB->record_exists('videolesson_data', ['contenthash' => $file->get_contenthash()])) {
        $result = [
            'contenthash' => $file->get_contenthash(),
            'reason' => get_string('error:video:exists', 'mod_videolesson', $file->get_filename()),
            'error' => false,
        ];
        return $result;
    }

    $ffprobe = new \mod_videolesson\ffprobe();
    $filemetadata = $ffprobe->get_media_metadata($file);
    videolesson_save_to_videolesson_data($file, $filemetadata);

    $result = [
        'contenthash' => $file->get_contenthash(),
        'reason' => $filemetadata['reason'],
        'error' => $filemetadata['status'] != 'success',
    ];

    return $result;
}

/**
 * Saves video metadata to the `videolesson_data` table in the database.
 *
 * @param object $file The file object containing the content hash. It should
 *                     have a method `get_contenthash()` to retrieve the content hash.
 * @param array $filemetadata The metadata extracted from the file. Should contain:
 *                            - 'status': The status of metadata extraction ('success' or otherwise).
 *                            - 'data': An array of metadata details if status is 'success'.
 * @return void
 * @throws dml_exception If there is an issue with the database operation.
 */
function videolesson_save_to_videolesson_data($file, $filemetadata) {
    global $DB;

    if ($filemetadata['status'] !== 'success') {
        return;
    }

    $metadatarecord = new \stdClass();
    $metadatarecord->contenthash = $file->get_contenthash();
    $metadatarecord->duration = $filemetadata['data']['duration'];
    $metadatarecord->bitrate = $filemetadata['data']['bitrate'];
    $metadatarecord->size = $filemetadata['data']['size'];
    $metadatarecord->videostreams = $filemetadata['data']['totalvideostreams'];
    $metadatarecord->audiostreams = $filemetadata['data']['totalaudiostreams'];

    // Get width and height from primary video stream if we have one.
    if (!empty($filemetadata['data']['videostreams'])) {
        $metadatarecord->width = $filemetadata['data']['videostreams'][0]['width'];
        $metadatarecord->height = $filemetadata['data']['videostreams'][0]['height'];
    } else {
        $metadatarecord->width = 0;
        $metadatarecord->height = 0;
    }

    $metadatarecord->metadata = json_encode($filemetadata['data']);
    $metadatarecord->timecreated = time();

    $DB->insert_record('videolesson_data', $metadatarecord);
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $node The node to add module settings to
 */
function videolesson_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    global $DB;

    if (has_capability('mod/videolesson:reports', $settings->get_page()->cm->context)) {
        $url = new moodle_url('/mod/videolesson/report.php', ['id' => $settings->get_page()->cm->id]);
        $node->add('Reports', $url);
    }
}

/**
 * Add a get_coursemodule_info function in case any assignment type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function videolesson_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, source, sourcedata, options, intro, introformat, completionprogress';
    if (!$instance = $DB->get_record('videolesson', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $instance->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('videolesson', $instance, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        if ($instance->completionprogress !== null) {
            $result->customdata['customcompletionrules']['completionprogress'] = $instance->completionprogress;
        }
    }

    return $result;
}

/**
 * Hides a course module if the associated video is not ready.
 *
 * This function checks whether the video associated with a specific course module
 * is ready for display. If the video is not ready, it sets the course module to be
 * hidden within the course.
 *
 * @param int $cmid The ID of the course module to check and potentially hide.
 * @return void
 */
function videolesson_possible_hide($cmid) {
    $activity = new \mod_videolesson\activity($cmid);
    if (!$activity->is_video_ready()) {
        set_coursemodule_visible($cmid, 0);
    }
}

/**
 * Unhides course modules associated with a specific source data in the videolesson module.
 *
 * This function searches for all course modules (cms) in the mod_videolesson module that are
 * associated with the given source data. It then sets these course modules to be visible
 * (unhidden) within their respective courses.
 *
 * @param string $sourcedata The source data used to identify the videolesson instances.
 *                           This could be a hash, URL, or any other identifier that
 *                           uniquely ties a videolesson instance to its source.
 * @return void
 */
function videolesson_unhide_cms_using_source($sourcedata) {
    global $DB;
    $module = $DB->get_record('modules', ['name' => 'videolesson']);
    $sql = "
        SELECT cm.id AS cmid
        FROM {videolesson} v
        INNER JOIN {course_modules} cm ON cm.instance = v.id AND cm.course = v.course AND cm.module = :moduleid
        WHERE v.sourcedata = :sourcedata";

    $params = ['moduleid' => $module->id, 'sourcedata' => $sourcedata];
    $instances = $DB->get_records_sql($sql, $params);
    foreach ($instances as $instance) {
        set_coursemodule_visible($instance->cmid, 1);
    }
}

/**
 * Handles inplace editable updates for mod_videolesson module.
 *
 * This function processes the inline editing of specific fields in the
 * mod_videolesson module. It currently supports the editing of video names.
 *
 * @param string $itemtype The type of item being edited. For example, 'videoname'.
 * @param int $itemid The ID of the item being edited.
 * @param string $newvalue The new value that is being set for the item.
 * @return inplace_editable The updated inplace_editable object containing the new value and display text.
 */
function mod_videolesson_inplace_editable($itemtype, $itemid, $newvalue) {
    if ($itemtype === 'videoname') {
        return \mod_videolesson\output\videoname::update($itemid, $newvalue);
    }
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_videolesson_get_completion_active_rule_descriptions() {
    return ['completionprogressenabled' => get_string('modform:completion:progress', 'mod_videolesson')];
}


function videolesson_get_completion_state() {
    $completionclass = \mod_videolesson\completion\custom_completion::class;
    throw new coding_exception(__FUNCTION__ . "() has been removed, please use the '{$completionclass}' class instead");
}
