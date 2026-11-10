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

namespace mod_videolesson;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/videolesson/locallib.php');

/**
 * Helpers for listing BBB recordings and building CDN m4v playback URLs.
 *
 * Form contract: teachers pick BBB in the Gallery UI (source=aws + bbbrecordingid).
 * Storage remains source=bbb + sourcedata=bbb:{recordingid}. Use
 * apply_stored_source_to_form() / apply_form_selection_to_data() for that mapping.
 *
 * Safe when mod_bigbluebuttonbn is missing, disabled, or partially uninstalled.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bbb_recordings {
    /** Default S3 object basename for synced video files. */
    public const VIDEO_BASENAME = 'video-0.m4v';

    /** Virtual library folder identifier for BBB recordings. */
    public const FOLDER_IDENTIFIER = 'bbb';

    /** Library / form date display format (matches uploaded library videos). */
    public const DATE_FORMAT = '%m/%d/%y %I:%M %p';

    /**
     * Whether the BBB source option can be offered.
     *
     * Requires mod_bigbluebuttonbn installed and enabled, recordings tables present,
     * and CDN playback config.
     *
     * @return bool
     */
    public static function is_available(): bool {
        try {
            return self::plugin_ready() && self::cdn_configured();
        } catch (\Throwable $e) {
            debugging('videolesson BBB is_available failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Whether BBB plugin is installed, enabled, and has usable DB tables.
     *
     * Safe to call when mod_bigbluebuttonbn is missing or disabled.
     *
     * @return bool
     */
    public static function plugin_ready(): bool {
        return self::plugin_installed() && self::plugin_enabled() && self::tables_available();
    }

    /**
     * Whether mod_bigbluebuttonbn class files are present.
     *
     * @return bool
     */
    public static function plugin_installed(): bool {
        try {
            // Class_exists autoloads and returns false when the plugin is absent.
            return class_exists(\mod_bigbluebuttonbn\recording::class, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Whether mod_bigbluebuttonbn is enabled site-wide (mdl_modules.visible = 1).
     *
     * @return bool
     */
    public static function plugin_enabled(): bool {
        global $DB;

        try {
            return $DB->record_exists('modules', ['name' => 'bigbluebuttonbn', 'visible' => 1]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Whether BBB recordings tables exist.
     *
     * @return bool
     */
    public static function tables_available(): bool {
        global $DB;

        try {
            $dbman = $DB->get_manager();
            return $dbman->table_exists('bigbluebuttonbn_recordings');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Whether CDN settings required for playback are set.
     *
     * @return bool
     */
    public static function cdn_configured(): bool {
        global $CFG;

        $cdn = get_config('mod_videolesson', 'bbb_cdn_url');

        return trim((string) $cdn) !== '';
    }

    /**
     * Count of usable BBB recordings.
     *
     * @return int
     */
    public static function get_count(): int {
        global $DB;

        if (!self::plugin_ready()) {
            return 0;
        }

        try {
            $statuses = self::usable_recording_statuses();
            [$insql, $params] = $DB->get_in_or_equal($statuses, SQL_PARAMS_NAMED);

            return (int) $DB->count_records_select(
                'bigbluebuttonbn_recordings',
                "status {$insql} AND recordingid <> ''",
                $params
            );
        } catch (\Throwable $e) {
            debugging('videolesson BBB get_count failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0;
        }
    }

    /**
     * Icon URL for BBB folder / gallery rows.
     *
     * Falls back to videolesson monologo when BBB plugin files are unavailable.
     *
     * @return string
     */
    public static function get_icon_url(): string {
        if (self::plugin_installed()) {
            return (new \moodle_url('/mod/bigbluebuttonbn/pix/monologo.svg'))->out(false);
        }
        return (new \moodle_url('/mod/videolesson/pix/monologo.svg'))->out(false);
    }

    /**
     * Map recordings into gallery picker rows for the activity form.
     *
     * @param string|null $selectedrecordingid Currently selected recording id.
     * @return array
     */
    public static function get_gallery_items(?string $selectedrecordingid = null): array {
        if (!self::is_available()) {
            return [];
        }

        $foldername = get_string('folder:bbb_recordings', 'mod_videolesson');
        $placeholder = self::get_icon_url();
        $items = [];

        foreach (self::get_items($selectedrecordingid) as $item) {
            $subtitle = trim($item['coursename'] . ' / ' . $item['activityname'], ' /');
            $displayname = $item['name'];
            if ($subtitle !== '') {
                $displayname = $item['name'] . ' (' . $subtitle . ')';
            }

            $items[] = [
                'selected' => !empty($item['selected']),
                'name' => $displayname,
                'contenthash' => '',
                'recordingid' => $item['recordingid'],
                'is_bbb' => true,
                'foldername' => $foldername,
                'thumbnail' => $placeholder,
                'placeholder' => $placeholder,
                'missing' => false,
                'duration' => $item['duration'],
                'timeadded' => $item['timecreated'],
            ];
        }

        return $items;
    }

    /**
     * Build CloudFront playback URL for a recording id.
     *
     * Pattern: {cdn}/{bucket}/{path}/{recordingid}/video-0.m4v
     *
     * Does not require the BBB plugin (CDN-only playback).
     *
     * @param string $recordingid BBB recording id (published folder id).
     * @return string Absolute URL, or empty string if not configured / invalid id.
     */
    public static function get_playback_url(string $recordingid): string {
        $recordingid = trim($recordingid);
        if ($recordingid === '' || !self::cdn_configured()) {
            return '';
        }

        global $CFG;
        $cdn = get_config('mod_videolesson', 'bbb_cdn_url');
        $cdn = rtrim(trim((string) $cdn), '/');
        $bucket = trim((string) get_config('mod_videolesson', 'bbb_s3_bucket'), '/');
        if ($bucket === '') {
            $bucket = trim((string) ($CFG->forced_plugin_settings['mod_videolesson']['bbb_s3_bucket'] ?? ''), '/');
        }
        $path = trim((string) get_config('mod_videolesson', 'bbb_cdn_path'), '/');

        $url = $cdn;
        if ($bucket !== '') {
            $url .= '/' . $bucket;
        }
        if ($path !== '') {
            $url .= '/' . $path;
        }
        $url .= '/' . $recordingid . '/' . self::VIDEO_BASENAME;

        return $url;
    }

    /**
     * Parse normalized sourcedata "bbb:{recordingid}".
     *
     * @param string $sourcedata
     * @return string|null Recording id or null if invalid.
     */
    public static function parse_sourcedata(string $sourcedata): ?string {
        if (preg_match('/^bbb:(.+)$/i', trim($sourcedata), $matches)) {
            $id = trim($matches[1]);
            return $id !== '' ? $id : null;
        }
        return null;
    }

    /**
     * Normalize a recording id for storage.
     *
     * @param string $recordingid
     * @return string
     */
    public static function format_sourcedata(string $recordingid): string {
        return 'bbb:' . trim($recordingid);
    }

    /**
     * Whether a recording can be used for playback / activity binding.
     *
     * Prefers a local BBB recording row; falls back to a resolvable CDN URL when
     * the BBB plugin is not ready (e.g. CDN-only deployments).
     *
     * @param string $recordingid
     * @return bool
     */
    public static function can_use_recording(string $recordingid): bool {
        $recordingid = trim($recordingid);
        if ($recordingid === '') {
            return false;
        }

        if (self::recording_exists($recordingid)) {
            return true;
        }

        return !self::plugin_ready() && self::get_playback_url($recordingid) !== '';
    }

    /**
     * Recording id selected on a stored activity instance, if any.
     *
     * @param \stdClass $instance Videolesson DB row (needs source, sourcedata).
     * @return string|null
     */
    public static function recording_id_from_instance(\stdClass $instance): ?string {
        if (($instance->source ?? '') !== MOD_VIDEOLESSON_SRC_BBB) {
            return null;
        }

        return self::parse_sourcedata((string) ($instance->sourcedata ?? ''));
    }

    /**
     * Load path: map stored BBB source fields onto mod_form defaults.
     *
     * The activity form never exposes source=bbb. BBB is chosen in Gallery, so
     * edit loads as Gallery + bbbrecordingid (storage stays source=bbb).
     *
     * @param array $defaults Form default values (source, sourcedata, ...).
     */
    public static function apply_stored_source_to_form(array &$defaults): void {
        if (($defaults['source'] ?? '') !== MOD_VIDEOLESSON_SRC_BBB) {
            return;
        }

        $defaults['source'] = MOD_VIDEOLESSON_SRC_GALLERY;
        $recordingid = self::parse_sourcedata((string) ($defaults['sourcedata'] ?? ''));
        if ($recordingid !== null) {
            $defaults['bbbrecordingid'] = $recordingid;
        }
    }

    /**
     * Save path: Gallery + bbbrecordingid → stored source=bbb.
     *
     * @param \stdClass $data Form data (must be source=Gallery with bbbrecordingid).
     * @return bool True when BBB fields were applied.
     */
    public static function apply_form_selection_to_data(\stdClass $data): bool {
        if (($data->source ?? '') !== MOD_VIDEOLESSON_SRC_GALLERY) {
            return false;
        }

        $recordingid = trim((string) ($data->bbbrecordingid ?? ''));
        if ($recordingid === '') {
            return false;
        }

        if (!self::can_use_recording($recordingid)) {
            throw new \moodle_exception('error:invalidbbbrecording', 'mod_videolesson');
        }

        $data->source = MOD_VIDEOLESSON_SRC_BBB;
        $data->sourcedata = self::format_sourcedata($recordingid);
        return true;
    }

    /**
     * Whether a recording id exists locally and is in a usable status.
     *
     * @param string $recordingid
     * @return bool
     */
    public static function recording_exists(string $recordingid): bool {
        global $DB;

        if (!self::plugin_ready() || trim($recordingid) === '') {
            return false;
        }

        try {
            $statuses = self::usable_recording_statuses();
            [$insql, $params] = $DB->get_in_or_equal($statuses, SQL_PARAMS_NAMED);
            $params['recordingid'] = $recordingid;

            return $DB->record_exists_select(
                'bigbluebuttonbn_recordings',
                "recordingid = :recordingid AND status {$insql}",
                $params
            );
        } catch (\Throwable $e) {
            debugging('videolesson BBB recording_exists failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * List published/processed recordings across the whole LMS.
     *
     * Uses local DB for course/activity, then batch-fetches BBB metadata for
     * recording name and duration (cached by mod_bigbluebuttonbn).
     *
     * @param string|null $selectedrecordingid Currently selected recording id.
     * @return array List of template-ready recording rows.
     */
    public static function get_items(?string $selectedrecordingid = null): array {
        global $DB;

        if (!self::plugin_ready()) {
            return [];
        }

        try {
            $statuses = self::usable_recording_statuses();
            [$insql, $params] = $DB->get_in_or_equal($statuses, SQL_PARAMS_NAMED);

            $sql = "SELECT r.id, r.recordingid, r.courseid, r.bigbluebuttonbnid, r.timecreated, r.importeddata,
                           COALESCE(b.name, '') AS activityname,
                           COALESCE(c.fullname, '') AS coursename,
                           bi.folderid AS libraryfolderid
                      FROM {bigbluebuttonbn_recordings} r
                 LEFT JOIN {bigbluebuttonbn} b ON b.id = r.bigbluebuttonbnid
                 LEFT JOIN {course} c ON c.id = r.courseid
                 LEFT JOIN {videolesson_bbb_items} bi ON bi.recordingid = r.recordingid
                     WHERE r.status {$insql}
                       AND r.recordingid <> ''
                  ORDER BY r.timecreated DESC";

            $records = $DB->get_records_sql($sql, $params);
        } catch (\Throwable $e) {
            debugging('videolesson BBB get_items failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }

        if (!$records) {
            return [];
        }

        $recordingids = array_values(array_map(static function ($record): string {
            return $record->recordingid;
        }, $records));

        $metadatas = [];
        try {
            if (class_exists(\mod_bigbluebuttonbn\local\proxy\recording_proxy::class, true)) {
                $metadatas = \mod_bigbluebuttonbn\local\proxy\recording_proxy::fetch_recordings($recordingids);
            }
        } catch (\Throwable $e) {
            // Keep listing usable even if the BBB server is unreachable.
            debugging('videolesson BBB metadata fetch failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        $items = [];
        foreach ($records as $record) {
            $meta = $metadatas[$record->recordingid] ?? null;
            if ($meta === null && !empty($record->importeddata)) {
                $decoded = json_decode($record->importeddata, true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }

            $recordingname = self::extract_recording_name($meta);
            $activityname = trim((string) $record->activityname);
            if ($recordingname === '') {
                $recordingname = $activityname !== '' ? $activityname : $record->recordingid;
            }

            $libraryfolderid = null;
            if (isset($record->libraryfolderid) && $record->libraryfolderid !== null && $record->libraryfolderid !== '') {
                $libraryfolderid = (int) $record->libraryfolderid;
            }

            $items[] = [
                'selected' => $selectedrecordingid !== null && $selectedrecordingid === $record->recordingid,
                'localid' => (int) $record->id,
                'recordingid' => $record->recordingid,
                'courseid' => (int) $record->courseid,
                'bigbluebuttonbnid' => (int) $record->bigbluebuttonbnid,
                'coursename' => $record->coursename,
                'activityname' => $activityname !== '' ? $activityname : get_string('form:bbb:unknownactivity', 'mod_videolesson'),
                'name' => $recordingname,
                'duration' => self::format_recording_duration($meta),
                'durationseconds' => self::get_duration_seconds($meta),
                'published' => self::is_published($meta),
                'folderid' => $libraryfolderid,
                'timecreated' => userdate((int) $record->timecreated, self::DATE_FORMAT),
                'timecreatedraw' => (int) $record->timecreated,
            ];
        }

        return $items;
    }

    /**
     * Rename a BBB recording on the BBB server (same path as BBB UI).
     *
     * @param string $recordingid
     * @param string $newname
     * @return string New display name
     * @throws \moodle_exception
     */
    public static function rename_recording(string $recordingid, string $newname): string {
        $recordingid = trim($recordingid);
        $newname = trim($newname);
        if ($recordingid === '' || $newname === '') {
            throw new \moodle_exception('error:invalidbbbrecording', 'mod_videolesson');
        }
        if (!self::plugin_ready()) {
            throw new \moodle_exception('error:invalidbbbrecording', 'mod_videolesson');
        }

        $recording = \mod_bigbluebuttonbn\recording::get_record(['recordingid' => $recordingid]);
        if (!$recording) {
            throw new \moodle_exception('error:invalidbbbrecording', 'mod_videolesson');
        }

        $recording->set('name', $newname);
        \mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_action::edit($recording);

        return $newname;
    }

    /**
     * Move a BBB recording within the BBB folder tree.
     *
     * @param string $recordingid
     * @param int|string|null $folderid Numeric BBB folder id, null/'bbb'/0 for BBB root
     * @return bool
     */
    public static function move_recording(string $recordingid, $folderid = null): bool {
        global $DB;

        $recordingid = trim($recordingid);
        if ($recordingid === '' || !self::recording_exists($recordingid)) {
            // Allow move when CDN-only: still require id non-empty.
            if ($recordingid === '') {
                return false;
            }
        }

        if ($folderid === null || $folderid === '' || $folderid === 'bbb' || $folderid === 0 || $folderid === '0') {
            $targetfolderid = null;
        } else {
            $targetfolderid = (int) $folderid;
            $folder = \mod_videolesson\folder_manager::get_folder($targetfolderid);
            if (
                !$folder
                || \mod_videolesson\folder_manager::normalise_scope($folder->scope ?? '')
                    !== \mod_videolesson\folder_manager::SCOPE_BBB
            ) {
                return false;
            }
        }

        $item = $DB->get_record('videolesson_bbb_items', ['recordingid' => $recordingid]);
        $time = time();
        $sortorder = self::get_next_bbb_item_sortorder($targetfolderid);

        if ($item) {
            $item->folderid = $targetfolderid;
            $item->sortorder = $sortorder;
            $item->timemodified = $time;
            $DB->update_record('videolesson_bbb_items', $item);
        } else {
            $DB->insert_record('videolesson_bbb_items', (object) [
                'recordingid' => $recordingid,
                'folderid' => $targetfolderid,
                'sortorder' => $sortorder,
                'timecreated' => $time,
                'timemodified' => $time,
            ]);
        }

        return true;
    }

    /**
     * Move many BBB recordings with one folder check and sequential sortorders.
     *
     * @param array $recordingids
     * @param int|string|null $folderid Numeric BBB folder id, null/'bbb'/0 for BBB root
     * @return array{moved:int,failed:int}
     */
    public static function bulk_move_recordings(array $recordingids, $folderid = null): array {
        global $DB;

        $ids = [];
        foreach ($recordingids as $recordingid) {
            $recordingid = trim((string) $recordingid);
            if ($recordingid !== '') {
                $ids[$recordingid] = $recordingid;
            }
        }
        $ids = array_values($ids);
        if (empty($ids)) {
            return ['moved' => 0, 'failed' => 0];
        }

        if ($folderid === null || $folderid === '' || $folderid === 'bbb' || $folderid === 0 || $folderid === '0') {
            $targetfolderid = null;
        } else {
            $targetfolderid = (int) $folderid;
            $folder = \mod_videolesson\folder_manager::get_folder($targetfolderid);
            if (
                !$folder
                || \mod_videolesson\folder_manager::normalise_scope($folder->scope ?? '')
                    !== \mod_videolesson\folder_manager::SCOPE_BBB
            ) {
                return ['moved' => 0, 'failed' => count($ids)];
            }
        }

        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $items = $DB->get_records_select('videolesson_bbb_items', "recordingid {$insql}", $params);
        $byrecordingid = [];
        foreach ($items as $item) {
            $byrecordingid[$item->recordingid] = $item;
        }

        $sortorder = self::get_next_bbb_item_sortorder($targetfolderid);
        $time = time();
        $moved = 0;
        $failed = 0;

        foreach ($ids as $recordingid) {
            // Match move_recording: empty already filtered; CDN-only ids without BBB row are allowed.
            if (isset($byrecordingid[$recordingid])) {
                $item = $byrecordingid[$recordingid];
                $item->folderid = $targetfolderid;
                $item->sortorder = $sortorder++;
                $item->timemodified = $time;
                $DB->update_record('videolesson_bbb_items', $item);
            } else {
                $DB->insert_record('videolesson_bbb_items', (object) [
                    'recordingid' => $recordingid,
                    'folderid' => $targetfolderid,
                    'sortorder' => $sortorder++,
                    'timecreated' => $time,
                    'timemodified' => $time,
                ]);
            }
            $moved++;
        }

        return ['moved' => $moved, 'failed' => $failed];
    }

    /**
     * Next sortorder for videolesson_bbb_items in a folder.
     *
     * @param int|null $folderid
     * @return int
     */
    private static function get_next_bbb_item_sortorder(?int $folderid): int {
        global $DB;

        if ($folderid === null) {
            $max = $DB->get_field_sql("SELECT MAX(sortorder) FROM {videolesson_bbb_items} WHERE folderid IS NULL");
        } else {
            $max = $DB->get_field_sql(
                "SELECT MAX(sortorder) FROM {videolesson_bbb_items} WHERE folderid = ?",
                [$folderid]
            );
        }

        return ($max !== false && $max !== null) ? ((int) $max) + 1 : 0;
    }

    /**
     * Build a library table row for one BBB recording.
     *
     * @param array $item Row from get_items().
     * @return array Template-ready video row.
     */
    public static function format_library_row(array $item): array {
        global $DB;

        $recordingid = (string) ($item['recordingid'] ?? '');
        $sourcedata = self::format_sourcedata($recordingid);
        $playbackurl = self::get_playback_url($recordingid);
        $placeholder = self::get_icon_url();
        $title = format_string((string) ($item['name'] ?? $recordingid));
        $coursename = format_string((string) ($item['coursename'] ?? ''));
        $activityname = format_string((string) ($item['activityname'] ?? ''));

        $instances = (int) $DB->count_records('videolesson', [
            'source' => MOD_VIDEOLESSON_SRC_BBB,
            'sourcedata' => $sourcedata,
        ]);

        $published = !empty($item['published']);
        $status = get_string($published ? 'status:bbb:published' : 'status:bbb:notpublished', 'mod_videolesson');
        $statusbadge = $published ? 'success' : 'secondary';

        $libraryfolderid = $item['folderid'] ?? null;
        $foldername = get_string('folder:bbb_recordings', 'mod_videolesson');
        $folderidvalue = self::FOLDER_IDENTIFIER;
        if ($libraryfolderid) {
            $folder = \mod_videolesson\folder_manager::get_folder((int) $libraryfolderid);
            if ($folder) {
                $foldername = format_string($folder->name);
                $folderidvalue = (string) (int) $folder->id;
            }
        }

        $localid = (int) ($item['localid'] ?? 0);
        $displayname = $title;
        $edithint = get_string('inplace:edit:name', 'mod_videolesson');
        $editlabel = get_string('inplace:edit:newname', 'mod_videolesson', $displayname);
        $namehtml = '<span class="inplaceeditable inplaceeditable-text"'
            . ' data-inplaceeditable="1"'
            . ' data-component="mod_videolesson"'
            . ' data-itemtype="bbbrecordingname"'
            . ' data-itemid="' . $localid . '"'
            . ' data-value="' . s((string) ($item['name'] ?? $title)) . '"'
            . ' data-editlabel="' . s($editlabel) . '"'
            . ' data-type="text"'
            . ' data-options="">'
            . '<span class="inplaceeditable-displayvalue">' . $displayname . '</span>'
            . '<a href="#" class="quickeditlink aalink" data-inplaceeditablelink="1"'
            . ' title="' . s($edithint) . '">'
            . '<span class="quickediticon visibleifjs icon-size-3">'
            . '<i class="icon fa fa-pencil-alt" aria-hidden="true"></i>'
            . '</span>'
            . '</a>'
            . '</span>';

        $row = [
            'id' => $localid,
            'contenthash' => $sourcedata,
            'recordingid' => $recordingid,
            'namehtml' => $namehtml,
            'title' => $title,
            'recordingname' => $title,
            'coursename' => $coursename,
            'activityname' => $activityname,
            'thumbnail' => $placeholder,
            'placeholder' => $placeholder,
            'duration' => $item['duration'] ?? '—',
            'foldername' => $foldername,
            'folderid' => $folderidvalue,
            'status' => $status,
            'statusbadge' => $statusbadge,
            'published' => $published,
            'instances' => $instances,
            'size' => '',
            'sourcesize' => '',
            'timecreated' => $item['timecreated']
                ?? userdate((int) ($item['timecreatedraw'] ?? 0), self::DATE_FORMAT),
            'timecreatedtimestamp' => (int) ($item['timecreatedraw'] ?? 0),
            'timecreatedlong' => !empty($item['timecreatedraw'])
                ? userdate((int) $item['timecreatedraw'], get_string('strftimedate', 'langconfig'))
                : '',
            'durationlabel' => \mod_videolesson\util::durationlabel($item['durationseconds'] ?? 0),
            'showstatus' => ($status !== '' && $statusbadge !== 'success'),
            'uniqueviews' => 0,
            'avgcompletion' => '0%',
            'avgwatch' => '0s',
            'assignable' => true,
            'cansubtitle' => false,
            'is_bbb' => true,
            'readonly' => false,
            'checkbox_disabled' => false,
            'reporturl' => (new \moodle_url('/mod/videolesson/report.php', [
                'action' => 'video',
                'contenthash' => $sourcedata,
            ]))->out(false),
        ];

        if ($instances > 0) {
            $row['instancesurl'] = (new \moodle_url('/mod/videolesson/library.php', [
                'action' => 'instances',
                'contenthash' => $sourcedata,
            ]))->out(false);
        }

        if ($playbackurl !== '') {
            $row['viewaction'] = [
                'contenthash' => $sourcedata,
                'src' => $playbackurl,
                'title' => $title,
                'ishls' => false,
                'type' => 'video/mp4',
            ];
            $row['mp4action'] = [
                'url' => $playbackurl,
                'label' => get_string('manage:video:mp4', 'mod_videolesson'),
            ];
        }

        $zipdownload = self::get_zip_download_action($item);
        if ($zipdownload !== null) {
            $row['zipdownloadaction'] = $zipdownload;
        }

        return $row;
    }

    /**
     * ZIP download action for the videolesson library (tool_bbb_download).
     *
     * @param array $item Row from get_items().
     * @return array|null
     */
    private static function get_zip_download_action(array $item): ?array {
        global $CFG;

        if (empty($item['published'])) {
            return null;
        }

        $libpath = $CFG->dirroot . '/admin/tool/bbb_download/lib.php';
        if (!is_readable($libpath)) {
            return null;
        }
        require_once($libpath);
        if (!function_exists('tool_bbb_download_is_available') || !tool_bbb_download_is_available()) {
            return null;
        }

        $courseid = (int) ($item['courseid'] ?? 0);
        if (!$courseid || !tool_bbb_download_user_can_download($courseid)) {
            return null;
        }

        $localid = (int) ($item['localid'] ?? 0);
        $bbbid = (int) ($item['bigbluebuttonbnid'] ?? 0);
        if (!$localid || !$bbbid) {
            return null;
        }

        return [
            'url' => (new \moodle_url('/admin/tool/bbb_download/download.php', [
                'rid' => $localid,
                'bn' => $bbbid,
            ]))->out(false),
            'label' => get_string('downloadrecording', 'tool_bbb_download'),
        ];
    }

    /**
     * Display name for a stored BBB sourcedata value (report headings, etc.).
     *
     * @param string $sourcedata
     * @return string|null
     */
    public static function get_display_name_for_sourcedata(string $sourcedata): ?string {
        $recordingid = self::parse_sourcedata($sourcedata);
        if ($recordingid === null) {
            return null;
        }

        foreach (self::get_items() as $item) {
            if (($item['recordingid'] ?? '') === $recordingid) {
                return (string) ($item['name'] ?? $recordingid);
            }
        }

        return $recordingid;
    }

    /**
     * Duration in seconds for a stored BBB sourcedata value.
     *
     * @param string $sourcedata
     * @return int
     */
    public static function get_duration_seconds_for_sourcedata(string $sourcedata): int {
        $recordingid = self::parse_sourcedata($sourcedata);
        if ($recordingid === null) {
            return 0;
        }

        foreach (self::get_items() as $item) {
            if (($item['recordingid'] ?? '') === $recordingid) {
                return (int) ($item['durationseconds'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Usable BBB recording status values (requires plugin classes).
     *
     * @return int[]
     */
    protected static function usable_recording_statuses(): array {
        return [
            \mod_bigbluebuttonbn\recording::RECORDING_STATUS_PROCESSED,
            \mod_bigbluebuttonbn\recording::RECORDING_STATUS_NOTIFIED,
        ];
    }

    /**
     * Extract recording display name from BBB metadata.
     *
     * @param array|null $meta
     * @return string
     */
    protected static function extract_recording_name(?array $meta): string {
        if (empty($meta)) {
            return '';
        }
        foreach (['meta_bbb-recording-name', 'meta_contextactivity', 'meetingName', 'name'] as $key) {
            if (!empty($meta[$key]) && is_string($meta[$key])) {
                return trim($meta[$key]);
            }
        }
        return '';
    }

    /**
     * Whether BBB metadata marks the recording as published.
     *
     * @param array|null $meta
     * @return bool
     */
    protected static function is_published(?array $meta): bool {
        if (empty($meta) || !array_key_exists('published', $meta)) {
            return false;
        }

        $value = $meta['published'];
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['true', '1', 'yes'], true);
    }

    /**
     * Duration in seconds from BBB metadata.
     *
     * @param array|null $meta
     * @return int
     */
    protected static function get_duration_seconds(?array $meta): int {
        if (empty($meta)) {
            return 0;
        }

        $minutes = 0;
        if (!empty($meta['playbacks']) && is_array($meta['playbacks'])) {
            foreach ($meta['playbacks'] as $playback) {
                if (!is_array($playback)) {
                    continue;
                }
                if (array_key_exists('restricted', $playback) && strtolower((string) $playback['restricted']) === 'true') {
                    continue;
                }
                if (!empty($playback['length'])) {
                    $minutes = (int) $playback['length'];
                    break;
                }
            }
        }

        if ($minutes <= 0 && !empty($meta['startTime']) && !empty($meta['endTime'])) {
            $start = (int) $meta['startTime'];
            $end = (int) $meta['endTime'];
            if ($end > $start) {
                // BBB timestamps are milliseconds.
                return (int) round(($end - $start) / 1000);
            }
        }

        return max(0, $minutes * 60);
    }

    /**
     * Format recording duration from BBB metadata for display.
     *
     * Prefers playback length; falls back to start/end timestamps.
     * Uses the same HH:MM:SS style as uploaded library videos.
     *
     * @param array|null $meta
     * @return string
     */
    protected static function format_recording_duration(?array $meta): string {
        $seconds = self::get_duration_seconds($meta);
        if ($seconds <= 0) {
            return '—';
        }

        return \mod_videolesson\util::durationformat($seconds);
    }
}
