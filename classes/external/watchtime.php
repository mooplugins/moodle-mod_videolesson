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
 * Watch time class
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson\external;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/videolesson/locallib.php');
require_once($CFG->dirroot . '/mod/videolesson/classes/util.php');
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use context_module;
use stdClass;

/**
 * Watch time external API.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class watchtime extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'data' => new external_value(PARAM_TEXT, 'The data to save, encoded as a json array', VALUE_REQUIRED),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'progress' => new external_value(PARAM_FLOAT, 'Progress'),
            'usageid' => new external_value(PARAM_INT, 'Usage record id for later heartbeats', VALUE_OPTIONAL),
            'notify' => new external_single_structure([
                'type' => new external_value(PARAM_TEXT, 'type', VALUE_OPTIONAL),
                'message' => new external_value(PARAM_TEXT, 'message', VALUE_OPTIONAL),
            ], 'notification', VALUE_OPTIONAL),
            'activity_info' => new external_value(PARAM_RAW, 'completion', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Executes the external function to save watch time.
     *
     * @param string $data JSON-encoded payload including cm and userid (see vplyr.js).
     */
    public static function execute(string $data) {
        global $USER;

        $params = external_api::validate_parameters(self::execute_parameters(), [
            'data' => $data,
        ]);

        $jsondata = json_decode($params['data'], true);
        if (!is_array($jsondata)) {
            throw new \invalid_parameter_exception('Invalid JSON in data parameter');
        }
        if (empty($jsondata['cm']) || !is_numeric($jsondata['cm'])) {
            throw new \invalid_parameter_exception('Missing or invalid course module id (cm)');
        }
        if (empty($jsondata['userid']) || (int) $jsondata['userid'] !== (int) $USER->id) {
            throw new \invalid_parameter_exception('Invalid user id in tracking payload');
        }

        $context = context_module::instance((int) $jsondata['cm']);
        self::validate_context($context);
        require_capability('mod/videolesson:view', $context);

        return self::user_usage($params['data']);
    }

    /**
     * Session cache of one viewing so later heartbeats can skip repeated lookups.
     *
     * @return \cache_session
     */
    private static function watch_save_cache(): \cache_session {
        return \cache::make('mod_videolesson', 'watch_save');
    }

    /**
     * Load a cached viewing that belongs to the current user and course module.
     *
     * @param int $usageid Client-supplied usage row id.
     * @param int $userid Authenticated user id.
     * @param int $cmid Course module id from the payload.
     * @return array|null
     */
    private static function recall_watch_save(int $usageid, int $userid, int $cmid): ?array {
        if ($usageid <= 0) {
            return null;
        }

        $entry = self::watch_save_cache()->get((string) $usageid);
        if (!is_array($entry)) {
            return null;
        }

        $required = ['cm', 'userid', 'source', 'sourcedata', 'usagesourcedata', 'duration', 'ranges', 'progress', 'progressid'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $entry)) {
                return null;
            }
        }

        if ((int) $entry['userid'] !== $userid || (int) $entry['cm'] !== $cmid) {
            return null;
        }
        if ((int) $entry['progressid'] <= 0 || (float) $entry['duration'] <= 0) {
            return null;
        }
        if (!is_array($entry['ranges'])) {
            return null;
        }

        return $entry;
    }

    /**
     * Remember the viewing state for later heartbeats.
     *
     * @param int $usageid Usage row id.
     * @param array $entry Cache payload.
     */
    private static function remember_watch_save(int $usageid, array $entry): void {
        if ($usageid <= 0) {
            return;
        }
        self::watch_save_cache()->set((string) $usageid, $entry);
    }

    /**
     * Saves the watch time data to the database.
     *
     * @param string $data The data to save.
     * @return array The result of the watch time save.
     */
    private static function user_usage($data) {
        global $DB;

        $jsondata = json_decode($data, true);
        if (!is_array($jsondata)) {
            throw new \invalid_parameter_exception('Invalid JSON in data parameter');
        }

        $cachedresult = self::save_usage_from_cache($jsondata);
        if ($cachedresult !== null) {
            return $cachedresult;
        }

        $cm = get_coursemodule_from_id('videolesson', (int) $jsondata['cm'], 0, false, MUST_EXIST);
        $instance = $DB->get_record('videolesson', ['id' => $cm->instance], '*', MUST_EXIST);
        $instancesource = (string) $instance->source;
        $instancesourcedata = (string) $instance->sourcedata;

        // Rebuild telemetry from an allowlist. Bind source/sourcedata to the activity record.
        $jsondata = \mod_videolesson\util::sanitize_watchtime_telemetry(
            $jsondata,
            (int) $jsondata['userid'],
            (int) $jsondata['cm'],
            (string) $instance->source,
            (string) $instance->sourcedata
        );

        if (!empty($jsondata['duration']) && $jsondata['source'] != MOD_VIDEOLESSON_SRC_GALLERY) {
            $externaltype = null;
            $externalvideoid = null;
            $sourceurl = null;
            $sourcehash = null;

            // For MOD_VIDEOLESSON_SRC_EXTERNAL, check if sourcedata is in normalized format (youtube:ID or vimeo:ID).
            if ($jsondata['source'] == MOD_VIDEOLESSON_SRC_EXTERNAL) {
                $sourcedata = $jsondata['sourcedata'];

                // Check if sourcedata is in normalized format (e.g., "youtube:VIDEO_ID").
                if (preg_match('/^(youtube|vimeo):([a-zA-Z0-9_-]+)$/i', $sourcedata, $matches)) {
                    // Create a normalized hash: "provider:videoid".
                    $externaltype = strtolower($matches[1]);
                    $externalvideoid = $matches[2];
                    $normalized = $externaltype . ':' . $externalvideoid;
                    $sourcehash = md5($normalized);
                    $sourceurl = null; // No source URL for normalized format.
                } else {
                    $sourceurl = $sourcedata;
                    $sourcehash = md5($sourceurl);

                    // Classify stored direct video URLs locally (no network).
                    if (\mod_videolesson\util::is_video_url($sourceurl)) {
                        $externaltype = 'direct_url';
                        $externalvideoid = null;
                    } else {
                        // Unsupported embed or other - no tracking.
                        $externaltype = null;
                        $externalvideoid = null;
                    }
                }
            } else {
                $sourceurl = $jsondata['sourcedata'];
                $sourcehash = md5($sourceurl);
                $externaltype = null;
                $externalvideoid = null;
            }

            $sourceinfo = $DB->get_record('videolesson_data_external', [
                'sourcehash' => $sourcehash,
            ]);

            if (!$sourceinfo) {
                $externalobj = new stdClass();
                $externalobj->sourcehash = $sourcehash;
                $externalobj->duration = $jsondata['duration'];
                $externalobj->externaltype = $externaltype;
                $externalobj->externalvideoid = $externalvideoid;
                $externalobj->sourceurl = $sourceurl;
                $externalobj->timecreated = time();
                $externalobj->timemodified = time();
                $DB->insert_record('videolesson_data_external', $externalobj);
            } else {
                // Update existing record with new fields if they're empty.
                $needsupdate = false;
                if (empty($sourceinfo->externaltype) && $externaltype) {
                    $sourceinfo->externaltype = $externaltype;
                    $needsupdate = true;
                }
                if (empty($sourceinfo->externalvideoid) && $externalvideoid) {
                    $sourceinfo->externalvideoid = $externalvideoid;
                    $needsupdate = true;
                }
                if (empty($sourceinfo->sourceurl) && $sourceurl) {
                    $sourceinfo->sourceurl = $sourceurl;
                    $needsupdate = true;
                }
                if (empty($sourceinfo->timecreated) || $sourceinfo->timecreated == 0) {
                    $sourceinfo->timecreated = time();
                    $needsupdate = true;
                }
                // Always update timemodified when duration is updated.
                $sourceinfo->timemodified = time();
                $sourceinfo->duration = $jsondata['duration'];
                $needsupdate = true;

                if ($needsupdate) {
                    $DB->update_record('videolesson_data_external', $sourceinfo);
                }
            }
        }

        // Authoritative duration from activity media metadata (not client totalprogess).
        $mediaduration = \mod_videolesson\util::get_video_duration(
            (string) $instance->source,
            (string) $instance->sourcedata
        );
        if ($mediaduration === false || $mediaduration <= 0) {
            // Fallback when metadata is not yet stored (e.g. first external play).
            $mediaduration = $jsondata['duration'] > 0 ? $jsondata['duration'] : 0.0;
        } else {
            $mediaduration = (float) $mediaduration;
        }

        $jsondata['ranges'] = \mod_videolesson\util::clamp_watchtime_ranges(
            $jsondata['ranges'],
            $mediaduration
        );
        $derivedprogress = \mod_videolesson\util::derive_progress_from_ranges(
            $jsondata['ranges'],
            $mediaduration
        );
        // Keep totalprogess in stored telemetry as server-derived UI value only.
        $jsondata['totalprogess'] = $derivedprogress;

        // Normalize sourcedata for storage: YouTube/Vimeo use normalized format, external URLs use hash.
        $sourcedataforquery = \mod_videolesson\util::normalize_sourcedata_for_usage(
            $jsondata['source'],
            $jsondata['sourcedata']
        );
        $jsondata['sourcedata'] = $sourcedataforquery;

        $safedatajson = json_encode($jsondata);

        $record = $DB->get_record(
            'videolesson_usage',
            [
                'userid' => $jsondata['userid'],
                'cm' => $jsondata['cm'],
                'session' => $jsondata['session'],
                'timestamp' => $jsondata['timestamp'],
                'source' => $jsondata['source'],
                'sourcedata' => $sourcedataforquery,
            ]
        );

        if ($record) {
            $record->watchduration = (int) round($jsondata['watchduration']);
            $record->timemodified = time();
            $record->data = $safedatajson;
            $DB->update_record('videolesson_usage', $record);
            $usageid = (int) $record->id;
        } else {
            $usagerecord = new stdClass();
            $usagerecord->userid = $jsondata['userid'];
            $usagerecord->cm = $jsondata['cm'];
            $usagerecord->session = $jsondata['session'];
            $usagerecord->source = $jsondata['source'];
            $usagerecord->sourcedata = $sourcedataforquery;
            $usagerecord->watchduration = (int) round($jsondata['watchduration']);
            $usagerecord->ip = $jsondata['ip'];
            $usagerecord->platform = $jsondata['platform'];
            $usagerecord->browser = $jsondata['browser'];
            $usagerecord->os = $jsondata['os'];
            $usagerecord->city = $jsondata['city'];
            $usagerecord->country = $jsondata['country'];
            $usagerecord->timestamp = $jsondata['timestamp'];
            $usagerecord->data = $safedatajson;
            $usagerecord->timecreated = time();
            $usagerecord->timemodified = time();
            $usageid = (int) $DB->insert_record('videolesson_usage', $usagerecord);
        }

        $progressinfo = self::user_save_progress($jsondata, $derivedprogress);
        $storedprogress = $progressinfo['progress'];

        self::remember_watch_save($usageid, [
            'cm' => (int) $jsondata['cm'],
            'userid' => (int) $jsondata['userid'],
            'source' => $instancesource,
            'sourcedata' => $instancesourcedata,
            'usagesourcedata' => $sourcedataforquery,
            'duration' => $mediaduration,
            'ranges' => $jsondata['ranges'],
            'progress' => $storedprogress,
            'progressid' => $progressinfo['id'],
            'externalwritten' => true,
        ]);

        return self::finish_usage_result($jsondata, $storedprogress, $usageid);
    }

    /**
     * Build activity completion HTML for AJAX refresh after watch progress.
     *
     * Uses activity_completion + activity_dates (Moodle 4.3+, required in 5.2).
     * Falls back to activity_information on Moodle 4.1–4.2.
     *
     * @param \cm_info $cm Course module info for the activity.
     * @param int $userid User id for completion/dates.
     * @param \stdClass $course Course record.
     * @param \renderer_base $outputrenderer Course renderer for template export.
     * @return string Rendered HTML for core_course/activity_info.
     */
    private static function render_activity_info_html(
        \cm_info $cm,
        int $userid,
        \stdClass $course,
        \renderer_base $outputrenderer,
    ): string {
        global $OUTPUT, $PAGE;

        if (!$PAGE->cm || $PAGE->cm->id != $cm->id) {
            $PAGE->set_cm($cm, $course);
        }

        $completion = \core_completion\cm_completion_details::get_instance($cm, $userid);
        $moduledates = \core\activity_dates::get_dates_for_module($cm, $userid);

        if (class_exists(\core_course\output\activity_completion::class)) {
            $activitycompletion = new \core_course\output\activity_completion($cm, $completion, false);
            $activitycompletiondata = (array) $activitycompletion->export_for_template($outputrenderer);

            $activitydatesoutput = new \core_course\output\activity_dates($moduledates);
            $activitydatesdata = (array) $activitydatesoutput->export_for_template($outputrenderer);

            $data = (object) array_merge($activitycompletiondata, $activitydatesdata);
        } else {
            $activityinfo = new \core_course\output\activity_information($cm, $completion, $moduledates);
            $data = $activityinfo->export_for_template($outputrenderer);
            $data->hascompletion = true;
        }

        return $OUTPUT->render_from_template('core_course/activity_info', $data);
    }

    /**
     * Persist server-derived completion progress (never client totalprogess).
     *
     * Stores max(existing, newly derived) so progress cannot be wiped by a lower forge.
     *
     * @param array $data Sanitized watchtime payload (sourcedata already normalized).
     * @param float $derivedprogress Progress from ranges / duration in [0, 100].
     * @return array{progress: float, id: int} Stored progress and the progress row id.
     */
    private static function user_save_progress(array $data, float $derivedprogress): array {
        global $DB;
        $time = time();

        $derivedprogress = max(0.0, min(100.0, round($derivedprogress, 2)));

        $userprogress = $DB->get_record(
            'videolesson_cm_progress',
            [
                'cmid' => $data['cm'],
                'userid' => $data['userid'],
                'sourcedata' => $data['sourcedata'],
            ]
        );

        $existing = $userprogress ? (float) $userprogress->progress : 0.0;
        $progress = max($existing, $derivedprogress);

        $userprogressobj = (object) [
            'cmid' => $data['cm'],
            'userid' => $data['userid'],
            'progress' => $progress,
            'sourcedata' => $data['sourcedata'],
            'timecreated' => $time,
            'timemodified' => $time,
        ];

        if ($userprogress) {
            $userprogressobj->id = $userprogress->id;
            $userprogressobj->timecreated = $userprogress->timecreated ?: $time;
            $DB->update_record('videolesson_cm_progress', $userprogressobj);
            $progressid = (int) $userprogress->id;
        } else {
            $progressid = (int) $DB->insert_record('videolesson_cm_progress', $userprogressobj);
        }

        return [
            'progress' => $progress,
            'id' => $progressid,
        ];
    }

    /**
     * Update an existing viewing from the session cache.
     *
     * Returns null when this request must use the full lookup path.
     *
     * @param array $rawjson Decoded client payload, before sanitizing.
     * @return array|null
     */
    private static function save_usage_from_cache(array $rawjson): ?array {
        global $DB, $USER;

        $usageid = $rawjson['usageid'] ?? 0;
        if (!is_numeric($usageid) || (int) $usageid <= 0) {
            return null;
        }
        if (empty($rawjson['cm']) || !is_numeric($rawjson['cm'])) {
            return null;
        }

        $usageid = (int) $usageid;
        $userid = (int) $USER->id;
        $cmid = (int) $rawjson['cm'];
        $entry = self::recall_watch_save($usageid, $userid, $cmid);
        if ($entry === null) {
            return null;
        }

        $jsondata = \mod_videolesson\util::sanitize_watchtime_telemetry(
            $rawjson,
            $userid,
            $cmid,
            (string) $entry['source'],
            (string) $entry['sourcedata']
        );

        $duration = (float) $entry['duration'];
        $incoming = \mod_videolesson\util::clamp_watchtime_ranges($jsondata['ranges'], $duration);
        $merged = \mod_videolesson\util::merge_watchtime_ranges($incoming, $entry['ranges']);
        $sourcedata = \mod_videolesson\util::normalize_sourcedata_for_usage(
            $jsondata['source'],
            $jsondata['sourcedata']
        );
        if ((string) $sourcedata !== (string) $entry['usagesourcedata']) {
            return null;
        }

        $jsondata['ranges'] = $merged;
        $jsondata['sourcedata'] = $sourcedata;
        $jsondata['watchduration'] = (int) round(\mod_videolesson\util::watchduration_from_ranges($merged));
        $derivedprogress = \mod_videolesson\util::derive_progress_from_ranges($merged, $duration);
        $derivedprogress = max(0.0, min(100.0, round($derivedprogress, 2)));
        $jsondata['totalprogess'] = $derivedprogress;

        $DB->execute(
            "UPDATE {videolesson_usage}
                SET watchduration = :watchduration,
                    timemodified = :timemodified,
                    data = :data
              WHERE id = :id
                AND userid = :userid
                AND cm = :cm",
            [
                'watchduration' => (int) $jsondata['watchduration'],
                'timemodified' => time(),
                'data' => json_encode($jsondata),
                'id' => $usageid,
                'userid' => (string) $userid,
                'cm' => (string) $cmid,
            ]
        );

        $storedprogress = (float) $entry['progress'];
        $progressid = (int) $entry['progressid'];
        if ($derivedprogress > $storedprogress) {
            try {
                // Only raise progress. A second tab must not overwrite a higher value already stored.
                $DB->execute(
                    "UPDATE {videolesson_cm_progress}
                        SET progress = :progress,
                            timemodified = :timemodified
                      WHERE id = :id
                        AND userid = :userid
                        AND cmid = :cmid
                        AND CAST(progress AS DECIMAL(10,2)) < :progresstarget",
                    [
                        'progress' => $derivedprogress,
                        'timemodified' => time(),
                        'id' => $progressid,
                        'userid' => $userid,
                        'cmid' => $cmid,
                        'progresstarget' => $derivedprogress,
                    ]
                );
                $storedprogress = $derivedprogress;
            } catch (\dml_exception) {
                $progressinfo = self::user_save_progress($jsondata, $derivedprogress);
                $storedprogress = $progressinfo['progress'];
                $progressid = $progressinfo['id'];
            }
        }

        $entry['ranges'] = $merged;
        $entry['progress'] = $storedprogress;
        $entry['progressid'] = $progressid;
        self::remember_watch_save($usageid, $entry);

        return self::finish_usage_result($jsondata, $storedprogress, $usageid);
    }

    /**
     * Build the web service result, including completion UI when the activity is newly complete.
     *
     * @param array $jsondata Sanitized watch payload.
     * @param float $storedprogress Progress stored for this user.
     * @param int $usageid Usage row id.
     * @return array
     */
    private static function finish_usage_result(array $jsondata, float $storedprogress, int $usageid): array {
        global $PAGE;

        $result = [
            'progress' => $storedprogress,
            'usageid' => $usageid,
        ];

        if (!$jsondata['notified']) {
            $activity = new \mod_videolesson\activity((int) $jsondata['cm'], (int) $jsondata['userid']);
            if ($activity->possible_mark_complete(false)) {
                $result['notify'] = [
                    'message' => get_string('ws:notify:reqcomplete', 'mod_videolesson'),
                    'type' => 'success',
                ];

                $outputrenderer = $PAGE->get_renderer('core', 'course');
                $modinfo = get_fast_modinfo($activity->course->id, $jsondata['userid']);
                $cm = $modinfo->get_cm($jsondata['cm']);
                $result['activity_info'] = self::render_activity_info_html(
                    $cm,
                    (int) $jsondata['userid'],
                    $activity->course,
                    $outputrenderer
                );
            }
        }

        return $result;
    }
}
