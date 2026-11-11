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
 * Playback timeline segments for one watch session.
 *
 * @package     mod_videolesson
 * @author      BitKea Technologies LLP
 * @copyright   2022-2026 BitKea Technologies LLP
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videolesson\local;

/**
 * Turns stored playback and seek events into video-time bands.
 *
 * @package     mod_videolesson
 */
class playback_timeline {
    /** @var string Playhead is moving forward. */
    public const PLAYING = 'playing';

    /** @var string Playhead stays forward of a pause until playback resumes. */
    public const PAUSED = 'paused';

    /** @var string Playhead jumps. */
    public const SEEKING = 'seeking';

    /** @var string Remainder of the video after the furthest position reached. */
    public const INACTIVE = 'inactive';

    /** @var string Visibility bar while the page is active. */
    public const ACTIVE = 'active';

    /** @var float Seconds of movement that count as a seek rather than the same position. */
    private const SEEK_MOVE = 1.0;

    /** @var float Playback events this close to a zero seek are the landing echo, not the origin. */
    private const SEEK_CLUSTER = 2.0;

    /**
     * Build video-time segments and axis labels.
     *
     * Playing runs from play or resume forward to the next pause, end, or seek.
     * A pause followed by a later play, with no seek between them, is paused.
     * Each seek covers the span between its start and destination. The tail
     * after the furthest position is inactive. No events produce no segments.
     *
     * @param array $playback Playback events with event_type, timestamp, and position.
     * @param array $seeks Seek events with timestamp, start, and position.
     * @param float $duration Video length in seconds.
     * @return array{segments: array, labels: array}
     */
    public static function segments(array $playback, array $seeks, float $duration): array {
        $events = self::collect_events($playback, $seeks);
        $duration = max(0, $duration);
        if (!$events) {
            return [
                'segments' => [],
                'labels' => self::axis_labels($duration),
            ];
        }

        $bands = [];
        $mode = 'idle';
        $anchor = null;
        $pauseat = null;
        $seeksincepause = false;
        $furthest = 0.0;

        foreach ($events as $event) {
            $position = self::clamp_position($event['position'], $duration);
            $furthest = max($furthest, $position);

            if ($event['kind'] === 'seek') {
                $start = self::clamp_position($event['start'], $duration);
                $furthest = max($furthest, $start);
                if ($mode === 'playing' && $anchor !== null && $start >= $anchor) {
                    self::add_band($bands, self::PLAYING, $anchor, $start);
                }
                self::add_band($bands, self::SEEKING, min($start, $position), max($start, $position));
                if ($mode === 'playing') {
                    $anchor = $position;
                }
                $pauseat = null;
                $seeksincepause = true;
                continue;
            }

            if ($event['kind'] === 'play' || $event['kind'] === 'resume') {
                if ($pauseat !== null && !$seeksincepause && $position > $pauseat) {
                    self::add_band($bands, self::PAUSED, $pauseat, $position);
                }
                $mode = 'playing';
                $anchor = $position;
                $pauseat = null;
                $seeksincepause = false;
                continue;
            }

            if ($event['kind'] === 'pause' || $event['kind'] === 'end') {
                if ($mode === 'playing' && $anchor !== null && $position > $anchor) {
                    self::add_band($bands, self::PLAYING, $anchor, $position);
                }
                $mode = 'idle';
                $anchor = null;
                $pauseat = $position;
                $seeksincepause = false;
            }
        }

        if ($duration > $furthest) {
            self::add_band($bands, self::INACTIVE, $furthest, $duration);
        }

        return [
            'segments' => self::decorate($bands, $duration),
            'labels' => self::axis_labels($duration),
        ];
    }

    /**
     * Restore seek origins when the stored start and destination are the same.
     *
     * The player used to snapshot the playhead after it had already moved, so
     * start, position, and progress were the landing time. A stored span is kept.
     * A zero span uses the previous playhead, plus time spent playing across a
     * longer gap. A landing pause or play beside that zero span is ignored so it
     * does not erase the origin. Spans that playback itself can explain are dropped.
     *
     * @param array $playback Playback events with event_type, timestamp, and position.
     * @param array $seeks Seek events with timestamp, start, and position.
     * @return array Seek events with timestamp, start, position, and progress.
     */
    public static function recover_seeks(array $playback, array $seeks): array {
        $events = [];
        foreach ($playback as $event) {
            $kind = strtolower((string) self::field($event, 'event_type'));
            $position = self::field($event, 'position');
            $time = self::event_seconds(self::field($event, 'timestamp'));
            if (!in_array($kind, ['play', 'resume', 'pause', 'end'], true) || !is_numeric($position) || $time === null) {
                continue;
            }
            $events[] = [
                'kind' => $kind,
                'time' => $time,
                'position' => (float) $position,
                'start' => 0.0,
                'timestamp' => (string) self::field($event, 'timestamp'),
            ];
        }
        foreach ($seeks as $event) {
            $start = self::field($event, 'start');
            $position = self::field($event, 'position');
            $time = self::event_seconds(self::field($event, 'timestamp'));
            if (!is_numeric($start) || !is_numeric($position) || $time === null) {
                continue;
            }
            $events[] = [
                'kind' => 'seek',
                'time' => $time,
                'position' => (float) $position,
                'start' => (float) $start,
                'timestamp' => (string) self::field($event, 'timestamp'),
            ];
        }

        usort($events, function ($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        $landings = [];
        foreach ($events as $index => $event) {
            if ($event['kind'] !== 'seek' || abs($event['position'] - $event['start']) >= self::SEEK_MOVE) {
                continue;
            }
            foreach ($events as $otherindex => $other) {
                if ($other['kind'] === 'seek') {
                    continue;
                }
                $near = abs($other['time'] - $event['time']) <= self::SEEK_CLUSTER;
                $sameplace = abs($other['position'] - $event['position']) < self::SEEK_MOVE;
                if ($near && $sameplace) {
                    $landings[$otherindex] = true;
                }
            }
        }

        $playhead = null;
        $playheadat = null;
        $playing = false;
        $recovered = [];
        foreach ($events as $index => $event) {
            if ($event['kind'] !== 'seek') {
                if (!empty($landings[$index])) {
                    continue;
                }
                $playhead = $event['position'];
                $playheadat = $event['time'];
                $playing = $event['kind'] === 'play' || $event['kind'] === 'resume';
                continue;
            }

            $destination = $event['position'];
            $origin = $event['start'];
            if (abs($destination - $origin) < self::SEEK_MOVE && $playhead !== null && $playheadat !== null) {
                $elapsed = max(0.0, $event['time'] - $playheadat);
                if (!$playing || $elapsed <= self::SEEK_CLUSTER) {
                    $origin = $playhead;
                } else if ($destination >= $playhead) {
                    $origin = min($playhead + $elapsed, $destination);
                } else {
                    $origin = $playhead + $elapsed;
                }
            }

            if (abs($destination - $origin) >= self::SEEK_MOVE) {
                $recovered[] = [
                    'timestamp' => $event['timestamp'],
                    'start' => $origin,
                    'position' => $destination,
                    'progress' => $destination - $origin,
                ];
            }
            $playhead = $destination;
            $playheadat = $event['time'];
            $playing = true;
        }

        return $recovered;
    }

    /**
     * Visibility state across the session clock.
     *
     * The bar starts active. Each event holds its state until the next one.
     *
     * @param array $events Items with time (unix) and active (bool), in any order.
     * @param int $start Session start timestamp.
     * @param int $end Session end timestamp.
     * @return array<int, array{type: string, left: float, width: float}>
     */
    public static function visibility_bar(array $events, int $start, int $end): array {
        if (!$events) {
            return [];
        }

        usort($events, function ($a, $b) {
            return ($a['time'] ?? 0) <=> ($b['time'] ?? 0);
        });

        if ($end <= $start) {
            $last = end($events);
            $active = !empty($last['active']);
            return [[
                'type' => $active ? self::ACTIVE : self::INACTIVE,
                'left' => 0.0,
                'width' => 100.0,
            ]];
        }

        $span = $end - $start;
        $state = self::ACTIVE;
        $cursor = $start;
        $bands = [];
        foreach ($events as $event) {
            $time = (int) ($event['time'] ?? $start);
            $time = max($start, min($end, $time));
            if ($time > $cursor) {
                $bands[] = self::clock_band($state, $cursor, $time, $start, $span);
            }
            $state = !empty($event['active']) ? self::ACTIVE : self::INACTIVE;
            $cursor = $time;
        }
        if ($cursor < $end) {
            $bands[] = self::clock_band($state, $cursor, $end, $start, $span);
        }

        return array_values(array_filter($bands));
    }

    /**
     * Format a signed offset as +mm:ss or -h:mm:ss.
     *
     * @param float $seconds Difference in seconds.
     * @return string
     */
    public static function format_change(float $seconds): string {
        $rounded = (int) round($seconds);
        $sign = $rounded < 0 ? '-' : '+';
        $absolute = abs($rounded);
        $hours = intdiv($absolute, 3600);
        $minutes = intdiv($absolute % 3600, 60);
        $secs = $absolute % 60;
        if ($hours > 0) {
            return sprintf('%s%d:%02d:%02d', $sign, $hours, $minutes, $secs);
        }
        return sprintf('%s%02d:%02d', $sign, $minutes, $secs);
    }

    /**
     * Format a stored browser value such as Chrome|v156.0.0.0 as Chrome 156.
     *
     * @param string $browser Stored browser string.
     * @return string
     */
    public static function format_browser(string $browser): string {
        $parts = explode('|', $browser, 2);
        $name = trim($parts[0]);
        if ($name === '') {
            return '';
        }
        if (!isset($parts[1]) || trim($parts[1]) === '') {
            return $name;
        }
        $version = ltrim(trim($parts[1]), 'vV');
        $major = explode('.', $version)[0];
        if ($major === '' || !ctype_digit($major)) {
            return $name;
        }
        return $name . ' ' . $major;
    }

    /**
     * Merge playback and seek events into timestamp order.
     *
     * @param array $playback Playback events.
     * @param array $seeks Seek events.
     * @return array
     */
    private static function collect_events(array $playback, array $seeks): array {
        $events = [];
        $index = 0;
        foreach ($playback as $event) {
            $kind = strtolower((string) self::field($event, 'event_type'));
            if (!in_array($kind, ['play', 'resume', 'pause', 'end'], true)) {
                continue;
            }
            $position = self::field($event, 'position');
            if (!is_numeric($position)) {
                continue;
            }
            $events[] = [
                'kind' => $kind,
                'time' => (string) self::field($event, 'timestamp'),
                'position' => (float) $position,
                'start' => 0.0,
                'index' => $index,
            ];
            $index++;
        }
        foreach ($seeks as $event) {
            $start = self::field($event, 'start');
            $position = self::field($event, 'position');
            if (!is_numeric($start) || !is_numeric($position)) {
                continue;
            }
            $events[] = [
                'kind' => 'seek',
                'time' => (string) self::field($event, 'timestamp'),
                'position' => (float) $position,
                'start' => (float) $start,
                'index' => $index,
            ];
            $index++;
        }

        usort($events, function ($a, $b) {
            $time = strcmp($a['time'], $b['time']);
            if ($time !== 0) {
                return $time;
            }
            return $a['index'] <=> $b['index'];
        });

        return $events;
    }

    /**
     * Unix time with fractional seconds, or null when the timestamp cannot be read.
     *
     * @param mixed $value Client ISO timestamp.
     * @return float|null
     */
    private static function event_seconds($value): ?float {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $value);
        if (!$datetime) {
            $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $value);
        }
        if (!$datetime) {
            return null;
        }
        return (float) $datetime->format('U.u');
    }

    /**
     * Read one field from an event array or object.
     *
     * @param mixed $event Event payload.
     * @param string $name Field name.
     * @return mixed
     */
    private static function field($event, string $name) {
        if (is_array($event) && array_key_exists($name, $event)) {
            return $event[$name];
        }
        if (is_object($event) && isset($event->{$name})) {
            return $event->{$name};
        }
        return null;
    }

    /**
     * Keep a position inside the video when the duration is known.
     *
     * @param float $position Position in seconds.
     * @param float $duration Video length in seconds.
     * @return float
     */
    private static function clamp_position(float $position, float $duration): float {
        if ($position < 0) {
            $position = 0;
        }
        if ($duration > 0 && $position > $duration) {
            return $duration;
        }
        return $position;
    }

    /**
     * Append a band when it has a forward length.
     *
     * @param array $bands Band list, updated in place.
     * @param string $type Band type.
     * @param float $start Start second.
     * @param float $end End second.
     */
    private static function add_band(array &$bands, string $type, float $start, float $end): void {
        if ($end - $start <= 0.001) {
            return;
        }
        $bands[] = [
            'type' => $type,
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Add percentage placement for the template.
     *
     * @param array $bands Bands in seconds.
     * @param float $duration Video length in seconds.
     * @return array
     */
    private static function decorate(array $bands, float $duration): array {
        $segments = [];
        foreach ($bands as $band) {
            $left = 0.0;
            $width = 0.0;
            if ($duration > 0) {
                $left = round(($band['start'] / $duration) * 100, 2);
                $width = round((($band['end'] - $band['start']) / $duration) * 100, 2);
            }
            if ($width <= 0) {
                continue;
            }
            $segments[] = [
                'type' => $band['type'],
                'start' => $band['start'],
                'end' => $band['end'],
                'left' => $left,
                'width' => $width,
            ];
        }
        return $segments;
    }

    /**
     * Even axis marks from the start through the duration.
     *
     * @param float $duration Video length in seconds.
     * @return array<int, array{label: string, left: float, start: bool, end: bool}>
     */
    private static function axis_labels(float $duration): array {
        $seconds = (int) round($duration);
        if ($seconds < 1) {
            return [];
        }

        $points = [0, $seconds];
        if ($seconds >= 4) {
            $points[] = (int) round($seconds / 4);
            $points[] = (int) round($seconds / 2);
            $points[] = (int) round($seconds * 3 / 4);
        } else if ($seconds >= 2) {
            $points[] = (int) round($seconds / 2);
        }
        $points = array_values(array_unique($points));
        sort($points);

        $labels = [];
        $last = count($points) - 1;
        foreach ($points as $index => $point) {
            $labels[] = [
                'label' => chart_data::format_time_label($point),
                'left' => round(($point / $seconds) * 100, 2),
                'start' => $index === 0,
                'end' => $index === $last,
            ];
        }
        return $labels;
    }

    /**
     * One visibility band as a percentage of the session clock.
     *
     * @param string $type active or inactive.
     * @param int $from Start timestamp.
     * @param int $to End timestamp.
     * @param int $origin Session start timestamp.
     * @param int $span Session length in seconds.
     * @return array|null
     */
    private static function clock_band(string $type, int $from, int $to, int $origin, int $span): ?array {
        if ($to <= $from || $span < 1) {
            return null;
        }
        return [
            'type' => $type,
            'left' => round((($from - $origin) / $span) * 100, 2),
            'width' => round((($to - $from) / $span) * 100, 2),
        ];
    }
}
