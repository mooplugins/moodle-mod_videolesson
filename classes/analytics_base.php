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
 * Base analytics class for video analytics
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson;

use mod_videolesson\local\chart_data;
use mod_videolesson\local\playback_timeline;
use moodle_url;

/**
 * Base class for analytics functionality shared between CM and video-level analytics
 */
abstract class analytics_base {
    /** @var string $contenthash The content hash. */
    public $contenthash;
    /** @var array $data The data. */
    public $data;
    /** @var array $contacts The contacts. */
    public $contacts = [];
    /** @var \stdClass $user The user. */
    public $user;
    /** @var string $TABLE_USAGE The table usage. */
    const TABLE_USAGE = 'videolesson_usage';

    /** @var int Number of playback events shown before the rest are revealed. */
    private const PLAYBACK_PREVIEW = 5;

    /**
     * Get records from database - must be implemented by child classes
     * @param mixed $param Optional parameter (userid for video, cm flag for cm)
     * @return array
     */
    abstract public function get_records($param = 0);

    /**
     * Get video duration - must be implemented by child classes
     * @return int|false
     */
    abstract protected function get_video_duration();

    /**
     * Get completion data - must be implemented by child classes
     * @return array
     */
    abstract public function completion_data();

    /**
     * Get data - must be implemented by child classes
     * @return array
     */
    public function get_data() {
        $plays = $this->get_plays(true);

        return [
            'impression' => $plays['impression'],
            'totalplay' => $plays['total'],
            'unique' => $plays['unique'],
            'playsdata' => $plays['data'],
            'totaltime' => \mod_videolesson\util::format_watched_time($this->total_time()),
            'avgtimespent' => $this->avg_timespent(),
            'avgcompletion' => $this->avg_completion(),
        ];
    }

    /**
     * Get video data - must be implemented by child classes
     * @return array
     */
    public function get_video_data() {
        $plays = $this->get_plays(true);
        $timespent = $this->timespent_data();
        $completion = $this->completion_data();
        return [
            'impression' => $plays['impression'],
            'totalplay' => $plays['total'],
            'unique' => $plays['unique'],
            'playsdata' => $plays['data'],
            'totaltimeseconds' => $this->total_time(),
            'timespent' => $timespent['totaltime'],
            'sessions' => $timespent['sessions'],
            'completioncount' => $completion['count'],
            'completiontotal' => $completion['total'],
        ];
    }

    /**
     * Get impression rows for the report.
     *
     * @param bool $includebasicinfo When false, return 0 as before.
     * @return array|int
     */
    public function get_impression($includebasicinfo = false) {
        $count = 0;
        if (!$includebasicinfo) {
            return $count;
        }

        $rows = [];
        $userids = [];
        foreach ($this->data as $record) {
            $sessiondata = json_decode($record->data ?? '');
            if (!is_object($sessiondata)) {
                continue;
            }
            $userid = (int) ($sessiondata->userid ?? 0);
            $userids[] = $userid;
            $rows[] = [
                'userid' => $userid,
                'timestamp' => $this->format_event_time($sessiondata->timestamp ?? ''),
                'platform' => trim((string) ($sessiondata->platform ?? '')),
                'os' => trim((string) ($sessiondata->os ?? '')),
                'browser' => playback_timeline::format_browser((string) ($sessiondata->browser ?? '')),
                'ip' => trim((string) ($sessiondata->ip ?? '')),
            ];
        }

        $names = $this->lookup_user_names($userids);
        $data = [];
        foreach ($rows as $row) {
            $userid = $row['userid'];
            $data[] = [
                'user' => $names[$userid] ?? (string) $userid,
                'timestamp' => $row['timestamp'],
                'platform' => $row['platform'],
                'os' => $row['os'],
                'browser' => $row['browser'],
                'ip' => $row['ip'],
            ];
        }

        return [
            'total' => count($data),
            'hasdata' => !empty($data),
            'data' => $data,
        ];
    }

    /**
     * Get plays for the report.
     *
     * @param bool $includebasicinfo Include one row per session that started playback.
     * @return array
     */
    public function get_plays($includebasicinfo = false) {
        $plays = [];
        $count = 0;
        $unique = [];
        $impressions = 0;
        $userids = [];
        $playurl = new moodle_url('/mod/videolesson/report.php', [
            'id' => 0,
            'action' => 'play',
            'playid' => 0,
        ]);

        foreach ($this->data as $record) {
            $sessiondata = json_decode($record->data ?? '');
            if (!is_object($sessiondata)) {
                continue;
            }

            if (in_array($sessiondata->userid ?? 0, $this->contacts)) {
                continue;
            }

            $impressions++;
            if (!empty($sessiondata->playbackEvents)) {
                $userid = (int) ($sessiondata->userid ?? 0);
                if (!in_array($userid, $unique)) {
                    $unique[] = $userid;
                }

                $count++;
                if ($includebasicinfo) {
                    $userids[] = $userid;
                    $ranges = $this->flatten_ranges($sessiondata->ranges ?? []);
                    $progress = \mod_videolesson\util::calculate_percentage(
                        round($sessiondata->watchduration ?? 0),
                        $sessiondata->duration ?? 0
                    );
                    $progresswidth = max(0, min(100, (float) $progress));
                    $plays[] = [
                        'userid' => $userid,
                        'url' => $playurl->out(false, [
                            'id' => $sessiondata->cm ?? 0,
                            'playid' => $record->id,
                        ]),
                        'userdate' => $this->format_event_time($sessiondata->timestamp ?? ''),
                        'watched' => chart_data::format_time_label((int) round($sessiondata->watchduration ?? 0)),
                        'progress' => $progress,
                        'progresswidth' => $progresswidth,
                        'coverage' => $this->coverage_segments($ranges, $sessiondata->duration ?? 0),
                    ];
                }
            }
        }

        if ($includebasicinfo && $plays) {
            $names = $this->lookup_user_names($userids);
            foreach ($plays as $index => $play) {
                $userid = $play['userid'];
                $plays[$index]['user'] = $names[$userid] ?? (string) $userid;
            }
        }

        return [
            'impression' => $impressions,
            'total' => $count,
            'hasdata' => !empty($plays),
            'data' => $plays,
            'unique' => count($unique),
        ];
    }

    /**
     * Whether a usage row belongs to this report's content scope.
     *
     * Video-level reports may span course modules that share the same sourcedata.
     * Activity reports override this to also require a matching course module.
     *
     * @param \stdClass $playrecord Usage row.
     * @return bool
     */
    protected function play_record_in_scope(\stdClass $playrecord): bool {
        return (string) $playrecord->sourcedata === (string) $this->contenthash;
    }

    /**
     * Get play data for a usage record that belongs to this report scope.
     *
     * @param int $playid Usage table id.
     * @return array Empty when the record is missing or outside this report context.
     */
    public function get_play_data($playid) {
        global $DB;
        $return = [];
        $playrecord = $DB->get_record(self::TABLE_USAGE, ['id' => $playid]);
        if (!$playrecord || !$this->play_record_in_scope($playrecord)) {
            return $return;
        }

        $sessiondata = json_decode($playrecord->data);
        if (!is_object($sessiondata)) {
            return $return;
        }

        $playback = $this->event_list($sessiondata->playbackEvents ?? []);
        $seeks = playback_timeline::recover_seeks(
            $playback,
            $this->event_list($sessiondata->seekEvents ?? [])
        );
        $visibility = $this->event_list($sessiondata->visibility ?? []);
        $volume = $this->event_list($sessiondata->volumeChanges ?? []);
        $quality = $this->event_list($sessiondata->qualityChanges ?? []);
        $duration = (float) ($sessiondata->duration ?? 0);

        $timeline = playback_timeline::segments($playback, $seeks, $duration);
        [$preview, $more] = $this->split_preview($this->format_playback_rows($playback));
        [$seekpreview, $seekmore] = $this->split_preview($this->format_seek_rows($seeks));
        [$visibilitypreview, $visibilitymore] = $this->split_preview($this->format_visibility_rows($visibility));
        [$volumepreview, $volumemore] = $this->split_preview($this->format_media_rows($volume, 'volume_level'));
        [$qualitypreview, $qualitymore] = $this->split_preview($this->format_media_rows($quality, 'quality'));

        $userid = (int) ($sessiondata->userid ?? $playrecord->userid ?? 0);
        $names = $this->lookup_user_names([$userid]);
        $fullname = $names[$userid] ?? ($userid > 0 ? (string) $userid : '');

        $return['found'] = true;
        $return['user'] = [
            'fullname' => $fullname,
            'id' => $userid,
        ];
        $return['meta'] = $this->session_meta($sessiondata);
        $return['counts'] = [
            'playback' => count($playback),
            'seek' => count($seeks),
            'visibility' => count($visibility),
            'media' => count($volume) + count($quality),
        ];
        $return['hastimeline'] = !empty($timeline['segments']);
        $return['timeline'] = $timeline['segments'];
        $return['labels'] = $timeline['labels'];
        $return['hasplayback'] = !empty($preview);
        $return['playback'] = $preview;
        $return['playbackmore'] = $more;
        $return['hasmoreplayback'] = !empty($more);
        $return['hasseek'] = !empty($seekpreview);
        $return['seek'] = $seekpreview;
        $return['seekmore'] = $seekmore;
        $return['hasmoreseek'] = !empty($seekmore);
        $return['hasvisibility'] = !empty($visibilitypreview);
        $return['visibility'] = $visibilitypreview;
        $return['visibilitymore'] = $visibilitymore;
        $return['hasmorevisibility'] = !empty($visibilitymore);
        $return['visibilitybar'] = $this->visibility_bar($playback, $seeks, $visibility, $volume, $quality);
        $return['hasvisibilitybar'] = !empty($return['visibilitybar']);
        $return['hasvolume'] = !empty($volumepreview);
        $return['volume'] = $volumepreview;
        $return['volumemore'] = $volumemore;
        $return['hasmorevolume'] = !empty($volumemore);
        $return['hasquality'] = !empty($qualitypreview);
        $return['quality'] = $qualitypreview;
        $return['qualitymore'] = $qualitymore;
        $return['hasmorequality'] = !empty($qualitymore);

        return $return;
    }

    /**
     * Map user ids to full names in one query.
     *
     * @param int[] $userids User ids.
     * @return array<int, string>
     */
    private function lookup_user_names(array $userids): array {
        global $DB;
        $ids = [];
        foreach ($userids as $userid) {
            $userid = (int) $userid;
            if ($userid > 0) {
                $ids[$userid] = $userid;
            }
        }
        if (!$ids) {
            return [];
        }

        $names = [];
        $users = $DB->get_records_list('user', 'id', array_values($ids));
        foreach ($users as $user) {
            $names[(int) $user->id] = fullname($user);
        }
        return $names;
    }

    /**
     * Flatten nested watch ranges to [start, end] pairs.
     *
     * @param mixed $allranges Stored ranges.
     * @return array<int, array{0: float, 1: float}>
     */
    private function flatten_ranges($allranges): array {
        if (!is_array($allranges)) {
            return [];
        }
        $ranges = [];
        foreach ($allranges as $group) {
            if (!is_array($group)) {
                continue;
            }
            foreach ($group as $range) {
                if (!is_array($range) || count($range) < 2 || !is_numeric($range[0]) || !is_numeric($range[1])) {
                    continue;
                }
                $ranges[] = [(float) $range[0], (float) $range[1]];
            }
        }
        return $ranges;
    }

    /**
     * Coverage bars as percentages of the video.
     *
     * @param array $ranges Flattened [start, end] pairs.
     * @param float|int $duration Video length in seconds.
     * @return array<int, array{left: float, width: float}>
     */
    private function coverage_segments(array $ranges, $duration): array {
        $duration = (float) $duration;
        if ($duration <= 0) {
            return [];
        }
        $segments = [];
        foreach ($ranges as $range) {
            $start = max(0, min((float) $range[0], $duration));
            $end = max(0, min((float) $range[1], $duration));
            if ($end <= $start) {
                continue;
            }
            $segments[] = [
                'left' => round(($start / $duration) * 100, 2),
                'width' => round((($end - $start) / $duration) * 100, 2),
            ];
        }
        return $segments;
    }

    /**
     * Coerce a stored event list to an array.
     *
     * @param mixed $value Decoded JSON value.
     * @return array
     */
    private function event_list($value): array {
        if (is_array($value)) {
            return $value;
        }
        return [];
    }

    /**
     * Read one field from an event array or object.
     *
     * @param mixed $event Event payload.
     * @param string $name Field name.
     * @return mixed
     */
    private function event_field($event, string $name) {
        if (is_array($event) && array_key_exists($name, $event)) {
            return $event[$name];
        }
        if (is_object($event) && isset($event->{$name})) {
            return $event->{$name};
        }
        return null;
    }

    /**
     * Format a client ISO timestamp in the user's timezone.
     *
     * @param mixed $value Timestamp string.
     * @param bool $timeonly Clock time only when true.
     * @return string
     */
    private function format_event_time($value, bool $timeonly = false): string {
        if (!is_string($value) || $value === '') {
            return '';
        }
        $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $value);
        if (!$datetime) {
            $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $value);
        }
        if (!$datetime) {
            return '';
        }
        if ($timeonly) {
            return userdate($datetime->getTimestamp(), '%H:%M:%S');
        }
        return userdate($datetime->getTimestamp());
    }

    /**
     * Unix time for a client ISO timestamp, or 0 when it cannot be read.
     *
     * @param mixed $value Timestamp string.
     * @return int
     */
    private function event_unix($value): int {
        if (!is_string($value) || $value === '') {
            return 0;
        }
        $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $value);
        if (!$datetime) {
            $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $value);
        }
        if (!$datetime) {
            return 0;
        }
        return $datetime->getTimestamp();
    }

    /**
     * Meta line for the playback activity header.
     *
     * @param \stdClass $sessiondata Stored session.
     * @return array<int, array{text: string}>
     */
    private function session_meta(\stdClass $sessiondata): array {
        $parts = [];
        $opened = $this->format_event_time($sessiondata->timestamp ?? '');
        if ($opened !== '') {
            $parts[] = $opened;
        }
        $place = array_filter([
            trim((string) ($sessiondata->city ?? '')),
            trim((string) ($sessiondata->country ?? '')),
        ]);
        if ($place) {
            $parts[] = implode(', ', $place);
        }
        foreach (
            [
                $sessiondata->ip ?? '',
                $sessiondata->platform ?? '',
                $sessiondata->os ?? '',
                playback_timeline::format_browser((string) ($sessiondata->browser ?? '')),
            ] as $part
        ) {
            $part = trim((string) $part);
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        $meta = [];
        foreach ($parts as $part) {
            $meta[] = ['text' => $part];
        }
        return $meta;
    }

    /**
     * Playback rows with badge classes.
     *
     * @param array $events Playback events.
     * @return array
     */
    private function format_playback_rows(array $events): array {
        $rows = [];
        foreach ($events as $event) {
            $type = strtolower((string) $this->event_field($event, 'event_type'));
            $position = $this->event_field($event, 'position');
            $rows[] = [
                'label' => $this->playback_event_label($type),
                'badge' => $this->playback_event_badge($type),
                'time' => $this->format_event_time($this->event_field($event, 'timestamp'), true),
                'position' => chart_data::format_time_label((int) round(is_numeric($position) ? (float) $position : 0)),
            ];
        }
        return $rows;
    }

    /**
     * Keep the first rows visible and return the rest for the show-all control.
     *
     * @param array $rows Formatted event rows.
     * @return array{0: array, 1: array}
     */
    private function split_preview(array $rows): array {
        return [
            array_slice($rows, 0, self::PLAYBACK_PREVIEW),
            array_slice($rows, self::PLAYBACK_PREVIEW),
        ];
    }

    /**
     * Display label for a playback event type.
     *
     * @param string $type Stored event_type.
     * @return string
     */
    private function playback_event_label(string $type): string {
        $keys = [
            'play' => 'report:play:event:play',
            'resume' => 'report:play:event:resume',
            'pause' => 'report:play:event:pause',
            'end' => 'report:play:event:end',
        ];
        if (isset($keys[$type])) {
            return get_string($keys[$type], 'mod_videolesson');
        }
        return $type;
    }

    /**
     * Badge modifier for a playback event type.
     *
     * @param string $type Stored event_type.
     * @return string
     */
    private function playback_event_badge(string $type): string {
        if ($type === 'play' || $type === 'resume') {
            return 'is-play';
        }
        if ($type === 'pause') {
            return 'is-pause';
        }
        return 'is-end';
    }

    /**
     * Seek rows with a signed change.
     *
     * @param array $events Seek events.
     * @return array
     */
    private function format_seek_rows(array $events): array {
        $rows = [];
        foreach ($events as $event) {
            $start = is_numeric($this->event_field($event, 'start')) ? (float) $this->event_field($event, 'start') : 0;
            $position = is_numeric($this->event_field($event, 'position'))
                ? (float) $this->event_field($event, 'position') : 0;
            $rows[] = [
                'time' => $this->format_event_time($this->event_field($event, 'timestamp'), true),
                'from' => chart_data::format_time_label((int) round($start)),
                'to' => chart_data::format_time_label((int) round($position)),
                'change' => playback_timeline::format_change($position - $start),
            ];
        }
        return $rows;
    }

    /**
     * Visibility rows with Active or Inactive pills.
     *
     * @param array $events Visibility events.
     * @return array
     */
    private function format_visibility_rows(array $events): array {
        $rows = [];
        foreach ($events as $event) {
            $active = !empty($this->event_field($event, 'active'));
            $position = $this->event_field($event, 'position');
            $rows[] = [
                'time' => $this->format_event_time($this->event_field($event, 'timestamp'), true),
                'position' => chart_data::format_time_label((int) round(is_numeric($position) ? (float) $position : 0)),
                'active' => $active,
                'state' => get_string(
                    $active ? 'report:play:state:active' : 'report:play:state:inactive',
                    'mod_videolesson'
                ),
            ];
        }
        return $rows;
    }

    /**
     * Visibility bar across the session clock.
     *
     * @param array $playback Playback events.
     * @param array $seeks Seek events.
     * @param array $visibility Visibility events.
     * @param array $volume Volume events.
     * @param array $quality Quality events.
     * @return array
     */
    private function visibility_bar(
        array $playback,
        array $seeks,
        array $visibility,
        array $volume,
        array $quality
    ): array {
        if (!$visibility) {
            return [];
        }
        $points = [];
        foreach ([$playback, $seeks, $visibility, $volume, $quality] as $list) {
            foreach ($list as $event) {
                $time = $this->event_unix($this->event_field($event, 'timestamp'));
                if ($time) {
                    $points[] = $time;
                }
            }
        }
        $changes = [];
        foreach ($visibility as $event) {
            $time = $this->event_unix($this->event_field($event, 'timestamp'));
            if (!$time) {
                continue;
            }
            $changes[] = [
                'time' => $time,
                'active' => !empty($this->event_field($event, 'active')),
            ];
        }
        if (!$changes || !$points) {
            return [];
        }
        return playback_timeline::visibility_bar($changes, min($points), max($points));
    }

    /**
     * Volume or quality rows.
     *
     * @param array $events Media events.
     * @param string $valuefield Field that holds the level or quality.
     * @return array
     */
    private function format_media_rows(array $events, string $valuefield): array {
        $rows = [];
        foreach ($events as $event) {
            $value = $this->event_field($event, $valuefield);
            if ($valuefield === 'quality' && is_numeric($value) && (float) $value == 0.0) {
                $value = get_string('report:play:quality:auto', 'mod_videolesson');
            } else if (is_numeric($value)) {
                $value = rtrim(rtrim(sprintf('%.2f', (float) $value), '0'), '.');
            }
            $position = $this->event_field($event, 'position');
            $rows[] = [
                'time' => $this->format_event_time($this->event_field($event, 'timestamp'), true),
                'position' => chart_data::format_time_label((int) round(is_numeric($position) ? (float) $position : 0)),
                'value' => (string) $value,
            ];
        }
        return $rows;
    }

    /**
     * Get timespent data - must be implemented by child classes
     * @return array
     */
    public function timespent_data() {
        $duration = [];
        foreach ($this->data as $record) {
            $sessiondata = json_decode($record->data);
            if ($sessiondata->watchduration) {
                $duration[] = $sessiondata->watchduration;
            }
        }

        $totaltime = array_sum($duration);

        $sessions = count($duration);
        if ($duration) {
            return [
                'totaltime' => $totaltime,
                'sessions' => $sessions,
            ];
        }

        return [
            'totaltime' => 0,
            'sessions' => 0,
        ];
    }

    /**
     * Get average timespent - must be implemented by child classes
     * @return array
     */
    public function avg_timespent() {
        $data = $this->timespent_data();

        if ($data && $data['sessions'] > 0) {
            $avg = $data['totaltime'] / $data['sessions'];
            return \mod_videolesson\util::format_watched_time(round($avg, 2));
        }

        return 0;
    }

    /**
     * Get total time - must be implemented by child classes
     * @return array
     */
    public function total_time() {
        $duration = [];
        foreach ($this->data as $record) {
            $sessiondata = json_decode($record->data);
            if ($sessiondata->watchduration) {
                $duration[] = $sessiondata->watchduration;
            }
        }

        return round(array_sum($duration), 2);
    }

    /**
     * Get average completion - must be implemented by child classes
     * @return array
     */
    public function avg_completion() {
        $data = $this->completion_data();
        if (!$data['total']) {
            return 0;
        }

        $avg = ($data['count'] / $data['total']) * 100;

        return round($avg, 2) . '%';
    }

    /**
     * Get unique views - must be implemented by child classes
     * @return array
     */
    public function get_unique_views() {
        global $DB;
        $plays = [];
        $unique = [];
        foreach ($this->data as $record) {
            $sessiondata = json_decode($record->data);
            if (!empty($sessiondata->playbackEvents)) {
                $sessionranges = [];
                $allranges = $sessiondata->ranges;
                foreach ($allranges as $ranges) {
                    foreach ($ranges as $range) {
                        $sessionranges[] = [round($range[0]), round($range[1])];
                    }
                }

                $userdate = userdate(\mod_videolesson\util::convert_to_timestamp($sessiondata->timestamp));
                $progress = \mod_videolesson\util::calculate_percentage(
                    round($sessiondata->watchduration),
                    $sessiondata->duration
                );

                $plays[$sessiondata->userid][] = [
                    'userid' => $sessiondata->userid,
                    'cm' => $sessiondata->cm,
                    'timestamp' => $sessiondata->timestamp,
                    'userdate' => $userdate,
                    'watchduration' => round($sessiondata->watchduration) . ' seconds',
                    'duration' => $sessiondata->duration,
                    'ranges' => json_encode($sessionranges),
                    'progress' => $progress,
                ];

                if (!in_array($sessiondata->userid, $unique)) {
                    $unique[] = $sessiondata->userid;
                }
            }
        }

        $unique = array_values(array_unique($unique));

        $uniqueplays = [];

        if (!empty($unique)) {
            $users = $DB->get_records_list('user', 'id', $unique);

            foreach ($unique as $userid) {
                if (empty($users[$userid])) {
                    continue;
                }
                $user = $users[$userid];
                $uniqueplays[] = [
                    'user' => [
                        'id' => $user->id,
                        'fullname' => fullname($user),
                    ],
                    'data' => $plays[$user->id],
                    'count' => count($plays[$user->id]),
                ];
            }
        }

        return [
            'data' => $uniqueplays,
            'total' => count($unique),
        ];
    }

    /**
     * Get engagement chart data.
     *
     * Views are the average number of watch ranges covering each time bucket.
     * Device, browser, operating system, and country counts still come from
     * sessions that stored watch ranges.
     *
     * @return array
     */
    public function get_engagement_chart_data() {
        $records = $this->get_records();

        $browsers = [];
        $platforms = [];
        $operatingsystems = [];
        $countries = [];
        $watchdata = [];
        $unknown = get_string('report:unknown', 'mod_videolesson');
        foreach ($records as $record) {
            $data = json_decode($record->data ?? '');
            if (!is_object($data) || !isset($data->ranges) || !is_array($data->ranges)) {
                continue;
            }

            $allranges = $data->ranges;
            foreach ($allranges as $ranges) {
                if (!is_array($ranges)) {
                    continue;
                }
                foreach ($ranges as $range) {
                    $watchdata[] = $range;
                }
            }

            if ($allranges) {
                $browser = explode('|', (string) ($record->browser ?? ''));
                $browsername = trim($browser[0]);
                if ($browsername === '') {
                    $browsername = $unknown;
                }
                $this->increment_count($browsers, $browsername);

                $platform = trim((string) ($record->platform ?? ''));
                if ($platform === '') {
                    $platform = $unknown;
                }
                $this->increment_count($platforms, $platform);

                $operatingsystem = trim((string) ($record->os ?? ''));
                if ($operatingsystem === '') {
                    $operatingsystem = $unknown;
                }
                $this->increment_count($operatingsystems, $operatingsystem);

                $country = $record->country ? $record->country : 'n/a';
                $this->increment_count($countries, $country);
            }
        }

        $duration = $this->get_video_duration();
        $duration = is_numeric($duration) ? (int) $duration : 0;

        return [
            'views' => chart_data::views_over_duration($watchdata, $duration),
            'browsers' => chart_data::prepare_distribution($browsers),
            'platforms' => chart_data::prepare_distribution($platforms),
            'operatingsystems' => chart_data::prepare_distribution($operatingsystems),
            'countries' => chart_data::prepare_distribution($countries),
        ];
    }

    /**
     * Get chart data for tabs.
     *
     * @return array
     */
    public function get_chart_data_for_tabs() {
        $chartdata = $this->get_engagement_chart_data();

        $viewchart = $this->chart_panel(
            get_string('report:views:eyebrow', 'mod_videolesson'),
            get_string('report:views:title', 'mod_videolesson'),
            get_string('report:views:subtitle', 'mod_videolesson'),
            $this->generate_views_chart(
                $chartdata['views'],
                get_string('report:views:heading', 'mod_videolesson')
            ),
            empty($chartdata['views']['empty'])
                ? $this->single_legend(get_string('report:views:yaxis', 'mod_videolesson'))
                : []
        );

        $platformchart = $this->chart_panel(
            get_string('report:platforms:eyebrow', 'mod_videolesson'),
            get_string('report:platforms:heading', 'mod_videolesson'),
            get_string('report:platforms:subtitle', 'mod_videolesson'),
            $this->generate_distribution_chart(
                $chartdata['platforms'],
                get_string('report:platforms:heading', 'mod_videolesson'),
                chart_data::MODE_DOUGHNUT
            ),
            $this->distribution_legend($chartdata['platforms'], chart_data::MODE_DOUGHNUT)
        );

        $oschart = $this->chart_panel(
            get_string('report:os:eyebrow', 'mod_videolesson'),
            get_string('report:os:heading', 'mod_videolesson'),
            get_string('report:os:subtitle', 'mod_videolesson'),
            $this->generate_distribution_chart(
                $chartdata['operatingsystems'],
                get_string('report:os:heading', 'mod_videolesson'),
                chart_data::MODE_BAR
            ),
            $this->distribution_legend($chartdata['operatingsystems'], chart_data::MODE_BAR)
        );

        $browserchart = $this->chart_panel(
            get_string('report:browsers:eyebrow', 'mod_videolesson'),
            get_string('report:browsers:heading', 'mod_videolesson'),
            get_string('report:browsers:subtitle', 'mod_videolesson'),
            $this->generate_distribution_chart(
                $chartdata['browsers'],
                get_string('report:browsers:heading', 'mod_videolesson'),
                chart_data::MODE_DOUGHNUT
            ),
            $this->distribution_legend($chartdata['browsers'], chart_data::MODE_DOUGHNUT)
        );

        $countrieschart = $this->chart_panel(
            get_string('report:countries:eyebrow', 'mod_videolesson'),
            get_string('report:countries:heading', 'mod_videolesson'),
            get_string('report:countries:subtitle', 'mod_videolesson'),
            $this->generate_distribution_chart(
                $chartdata['countries'],
                get_string('report:countries:heading', 'mod_videolesson'),
                chart_data::MODE_DOUGHNUT,
                chart_data::MAX_DOUGHNUT_CATEGORIES
            ),
            $this->distribution_legend(
                $chartdata['countries'],
                chart_data::MODE_DOUGHNUT,
                chart_data::MAX_DOUGHNUT_CATEGORIES
            )
        );

        $tablabel = [
            ['id' => 'views', 'label' => get_string('report:views:heading', 'mod_videolesson'), 'active' => true],
            ['id' => 'platforms', 'label' => get_string('report:platforms:heading', 'mod_videolesson')],
            ['id' => 'os', 'label' => get_string('report:os:heading', 'mod_videolesson')],
            ['id' => 'browsers', 'label' => get_string('report:browsers:heading', 'mod_videolesson')],
            ['id' => 'countries', 'label' => get_string('report:countries:heading', 'mod_videolesson')],
        ];

        $tabcontent = [
            ['id' => 'views', 'content' => $viewchart, 'active' => true],
            ['id' => 'platforms', 'content' => $platformchart],
            ['id' => 'os', 'content' => $oschart],
            ['id' => 'browsers', 'content' => $browserchart],
            ['id' => 'countries', 'content' => $countrieschart],
        ];

        return [
            'tablabel' => $tablabel,
            'tabcontent' => $tabcontent,
        ];
    }

    /**
     * Increment a category count.
     *
     * @param array $counts Category name => count.
     * @param string $label Category name.
     */
    private function increment_count(array &$counts, string $label): void {
        if (isset($counts[$label])) {
            $counts[$label]++;
            return;
        }
        $counts[$label] = 1;
    }

    /**
     * Notice shown when a chart has nothing to plot.
     *
     * @return string
     */
    protected function render_empty_chart(): string {
        global $OUTPUT;
        return $OUTPUT->notification(
            get_string('report:nodata', 'mod_videolesson'),
            \core\output\notification::NOTIFY_INFO,
            false
        );
    }

    /**
     * Render the views-over-duration bar chart.
     *
     * One bar is one time bucket. A single bucket stays a bar so it is not drawn
     * as a line through one point.
     *
     * @param array $data Series, labels, and empty flag from chart_data.
     * @param string $heading Series name used in the tooltip.
     * @return string
     */
    protected function generate_views_chart(array $data, string $heading): string {
        global $OUTPUT;
        if (!empty($data['empty']) || empty($data['series'])) {
            return $this->render_empty_chart();
        }

        $chart = new \core\chart_bar();
        $chart->set_legend_options(['display' => false]);
        $series = new \core\chart_series($heading, $data['series']);
        $series->set_color(chart_data::SERIES_COLOR);
        $chart->add_series($series);
        $chart->set_labels($data['labels']);

        $xaxis = $chart->get_xaxis(0, true);
        $xaxis->set_label(get_string('report:views:xaxis', 'mod_videolesson'));
        $yaxis = $chart->get_yaxis(0, true);
        $yaxis->set_label(get_string('report:views:yaxis', 'mod_videolesson'));
        $yaxis->set_min(0);

        return $OUTPUT->render($chart);
    }

    /**
     * Render a category distribution as a summary, doughnut, or horizontal bar.
     *
     * @param array $data Prepared distribution from chart_data.
     * @param string $heading Series name.
     * @param string $prefer chart_data::MODE_DOUGHNUT or chart_data::MODE_BAR.
     * @param int $doughnutlimit Switch a doughnut to a bar above this many categories.
     *        Zero keeps the doughnut for every multi-category set.
     * @return string
     */
    protected function generate_distribution_chart(
        array $data,
        string $heading,
        string $prefer,
        int $doughnutlimit = 0
    ): string {
        global $OUTPUT;

        $count = count($data['series'] ?? []);
        $mode = chart_data::distribution_mode($count, $prefer, $doughnutlimit);
        if ($mode === chart_data::MODE_EMPTY) {
            return $this->render_empty_chart();
        }
        if ($mode === chart_data::MODE_SUMMARY) {
            return $OUTPUT->render_from_template('mod_videolesson/report_chart_summary', [
                'label' => $data['names'][0],
                'summary' => get_string('report:chart:singlesummary', 'mod_videolesson', (object) [
                    'count' => $data['series'][0],
                    'percent' => $data['percents'][0],
                ]),
            ]);
        }

        $labels = $data['labels'];
        $values = $data['series'];
        if ($mode === chart_data::MODE_BAR) {
            // Chart.js draws the first category at the bottom, so reverse the
            // descending list and the largest bar sits at the top.
            $labels = array_reverse($labels);
            $values = array_reverse($values);
            $chart = new \core\chart_bar();
            $chart->set_horizontal(true);
            $chart->set_legend_options(['display' => false]);
            $series = new \core\chart_series($heading, $values);
            $series->set_color(chart_data::SERIES_COLOR);
            $chart->add_series($series);
            $chart->set_labels($labels);
            $chart->get_xaxis(0, true)->set_min(0);
            return $OUTPUT->render($chart);
        }

        $chart = new \core\chart_pie();
        $chart->set_doughnut(true);
        $chart->set_legend_options(['display' => false]);
        $series = new \core\chart_series($heading, $values);
        $series->set_colors(chart_data::palette($count));
        $chart->add_series($series);
        $chart->set_labels($labels);
        return $OUTPUT->render($chart);
    }

    /**
     * Heading card placed above a chart.
     *
     * @param string $eyebrow Small label above the title.
     * @param string $title Chart title.
     * @param string $subtitle One-line description.
     * @param string $chart Rendered chart or empty notice.
     * @param array $legend Legend chips with label and color.
     * @return string
     */
    private function chart_panel(
        string $eyebrow,
        string $title,
        string $subtitle,
        string $chart,
        array $legend
    ): string {
        global $OUTPUT;
        return $OUTPUT->render_from_template('mod_videolesson/report_chart_panel', [
            'eyebrow' => $eyebrow,
            'title' => $title,
            'subtitle' => $subtitle,
            'chart' => $chart,
            'haslegend' => !empty($legend),
            'legend' => $legend,
        ]);
    }

    /**
     * One legend chip for a single-colour series.
     *
     * @param string $label Series name.
     * @return array<int, array{label: string, color: string}>
     */
    private function single_legend(string $label): array {
        return [[
            'label' => $label,
            'color' => chart_data::SERIES_COLOR,
        ]];
    }

    /**
     * Legend chips that match the chart actually drawn for a distribution.
     *
     * @param array $data Prepared distribution.
     * @param string $prefer chart_data::MODE_DOUGHNUT or chart_data::MODE_BAR.
     * @param int $doughnutlimit Categories above this become a bar. Zero keeps the doughnut.
     * @return array<int, array{label: string, color: string}>
     */
    private function distribution_legend(array $data, string $prefer, int $doughnutlimit = 0): array {
        $count = count($data['series'] ?? []);
        $mode = chart_data::distribution_mode($count, $prefer, $doughnutlimit);
        if ($mode === chart_data::MODE_EMPTY) {
            return [];
        }
        if ($mode === chart_data::MODE_BAR) {
            return $this->single_legend(get_string('report:chart:sessions', 'mod_videolesson'));
        }

        $colors = chart_data::palette($count);
        $legend = [];
        foreach ($data['names'] as $index => $name) {
            $legend[] = [
                'label' => $name,
                'color' => $colors[$index],
            ];
        }
        return $legend;
    }
}
