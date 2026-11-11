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
 * Pure helpers for report chart series.
 *
 * @package     mod_videolesson
 * @author      BitKea Technologies LLP
 * @copyright   2022-2026 BitKea Technologies LLP
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videolesson\local;

/**
 * Builds chart series without reading the database.
 *
 * Views stay in the existing unit: how many watch ranges cover each moment of the
 * video. Buckets report the average of that per-second count, not a sum and not
 * a unique-viewer count.
 *
 * @package     mod_videolesson
 */
class chart_data {
    /** @var int Maximum bars on the views chart. */
    public const MAX_VIEW_BUCKETS = 40;

    /** @var int Country doughnuts switch to a bar chart above this many slices. */
    public const MAX_DOUGHNUT_CATEGORIES = 6;

    /** @var string No categories to plot. */
    public const MODE_EMPTY = 'empty';

    /** @var string One category, shown as a summary instead of a chart. */
    public const MODE_SUMMARY = 'summary';

    /** @var string Doughnut chart. */
    public const MODE_DOUGHNUT = 'doughnut';

    /** @var string Bar chart. */
    public const MODE_BAR = 'bar';

    /** @var string Single-series colour, aligned with the report range marker. */
    public const SERIES_COLOR = '#3f6ab2';

    /** @var string[] Restrained doughnut palette. The first entry matches SERIES_COLOR. */
    public const PALETTE = [
        '#3f6ab2',
        '#5d8a8a',
        '#7a6a4f',
        '#6b7c93',
        '#4f7f62',
        '#7d6b8a',
        '#8a5f5f',
        '#4e6e8a',
    ];

    /**
     * Interval steps in seconds, from finest to coarsest.
     *
     * @return int[]
     */
    private static function interval_steps(): array {
        return [5, 15, 30, 60, 120, 300, 600, 900];
    }

    /**
     * Choose a bucket size so a video stays within {@see self::MAX_VIEW_BUCKETS} bars.
     *
     * Short videos use 5 second buckets. Longer videos step through 15 seconds,
     * 30 seconds, 1 minute, then 2, 5, 10 and 15 minutes. A step that would still
     * draw more than the maximum number of bars moves up to the next step.
     *
     * @param int $duration Video length in seconds.
     * @return int Interval in seconds, or 0 when the duration cannot be charted.
     */
    public static function choose_interval(int $duration): int {
        if ($duration < 1) {
            return 0;
        }

        if ($duration <= 120) {
            $interval = 5;
        } else if ($duration <= 600) {
            $interval = 15;
        } else if ($duration <= 1800) {
            $interval = 30;
        } else if ($duration <= 3600) {
            $interval = 60;
        } else {
            $interval = 120;
        }

        $steps = self::interval_steps();
        $index = array_search($interval, $steps, true);
        if ($index === false) {
            $index = 0;
        }

        $stepcount = count($steps);
        for ($i = $index; $i < $stepcount; $i++) {
            $candidate = $steps[$i];
            if (self::bucket_count($duration, $candidate) <= self::MAX_VIEW_BUCKETS) {
                return $candidate;
            }
        }

        return (int) ceil(($duration + 1) / self::MAX_VIEW_BUCKETS);
    }

    /**
     * Number of buckets needed to cover second 0 through $duration.
     *
     * @param int $duration Video length in seconds.
     * @param int $interval Bucket size in seconds.
     * @return int
     */
    public static function bucket_count(int $duration, int $interval): int {
        if ($duration < 1 || $interval < 1) {
            return 0;
        }
        return (int) ceil(($duration + 1) / $interval);
    }

    /**
     * Format a video position as m:ss or h:mm:ss.
     *
     * @param int $seconds Offset from the start of the video.
     * @return string
     */
    public static function format_time_label(int $seconds): string {
        if ($seconds < 0) {
            $seconds = 0;
        }
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%d:%02d', $minutes, $secs);
    }

    /**
     * Clamp one watch range to the video, or reject it.
     *
     * A range is rejected when it is not a numeric pair, runs backwards, or sits
     * entirely outside 0..$duration. Partial overlaps are clamped. Seconds are
     * rounded the same way the previous per-second chart rounded them.
     *
     * @param mixed $range Candidate [start, end] pair.
     * @param int $duration Video length in seconds.
     * @return int[]|null Start and end, inclusive, or null when the range cannot be used.
     */
    public static function normalize_range($range, int $duration): ?array {
        if ($duration < 1 || !is_array($range) || count($range) < 2) {
            return null;
        }
        if (!is_numeric($range[0]) || !is_numeric($range[1])) {
            return null;
        }

        $start = (float) $range[0];
        $end = (float) $range[1];
        if (!is_finite($start) || !is_finite($end)) {
            return null;
        }

        $start = (int) round($start);
        $end = (int) round($end);
        if ($end < $start || $end < 0 || $start > $duration) {
            return null;
        }

        return [max(0, $start), min($duration, $end)];
    }

    /**
     * Aggregate watch ranges into average coverage per time bucket.
     *
     * Each returned value is the mean number of ranges covering the seconds in
     * that bucket. Flat coverage therefore stays the same number the per-second
     * chart plotted. Empty, missing, and invalid ranges do not create points.
     *
     * @param array $ranges Watch ranges, each a [start, end] pair in seconds.
     * @param int $duration Video length in seconds.
     * @return array{series: float[], labels: string[], empty: bool}
     */
    public static function views_over_duration(array $ranges, int $duration): array {
        $empty = [
            'series' => [],
            'labels' => [],
            'empty' => true,
        ];
        if ($duration < 1) {
            return $empty;
        }

        $interval = self::choose_interval($duration);
        $numbuckets = self::bucket_count($duration, $interval);
        if ($interval < 1 || $numbuckets < 1) {
            return $empty;
        }

        $coverage = array_fill(0, $numbuckets, 0);
        $usable = 0;
        foreach ($ranges as $range) {
            $normalized = self::normalize_range($range, $duration);
            if ($normalized === null) {
                continue;
            }
            [$start, $end] = $normalized;
            self::add_range_coverage($coverage, $start, $end, $interval, $duration);
            $usable++;
        }

        if ($usable === 0) {
            return $empty;
        }

        $series = [];
        $labels = [];
        for ($bucket = 0; $bucket < $numbuckets; $bucket++) {
            $start = $bucket * $interval;
            $end = min(($bucket + 1) * $interval - 1, $duration);
            $width = $end - $start + 1;
            $series[] = $width > 0 ? round($coverage[$bucket] / $width, 1) : 0;
            $labels[] = self::format_time_label($start);
        }

        return [
            'series' => $series,
            'labels' => $labels,
            'empty' => false,
        ];
    }

    /**
     * Add one inclusive range into per-bucket covered-second totals.
     *
     * @param int[] $coverage Covered-second totals, updated in place.
     * @param int $start Inclusive start second.
     * @param int $end Inclusive end second.
     * @param int $interval Bucket size in seconds.
     * @param int $duration Video length in seconds.
     */
    private static function add_range_coverage(
        array &$coverage,
        int $start,
        int $end,
        int $interval,
        int $duration
    ): void {
        $first = intdiv($start, $interval);
        $last = intdiv($end, $interval);
        if ($first === $last) {
            $coverage[$first] += ($end - $start + 1);
            return;
        }

        $firstend = min(($first + 1) * $interval - 1, $duration);
        $coverage[$first] += ($firstend - $start + 1);
        for ($bucket = $first + 1; $bucket < $last; $bucket++) {
            $bucketstart = $bucket * $interval;
            $bucketend = min(($bucket + 1) * $interval - 1, $duration);
            $coverage[$bucket] += ($bucketend - $bucketstart + 1);
        }
        $coverage[$last] += ($end - ($last * $interval) + 1);
    }

    /**
     * Format a percentage without a trailing decimal when the value is whole.
     *
     * @param float $percent Percentage from 0 to 100.
     * @return string
     */
    public static function format_percent(float $percent): string {
        $rounded = round($percent, 1);
        if (abs($rounded - round($rounded)) < 0.05) {
            return (string) (int) round($rounded);
        }
        return (string) $rounded;
    }

    /**
     * Sort category counts and build labels that include the percentage.
     *
     * @param array $counts Category name => count.
     * @return array{labels: string[], series: int[], names: string[], percents: string[], empty: bool}
     */
    public static function prepare_distribution(array $counts): array {
        $empty = [
            'labels' => [],
            'series' => [],
            'names' => [],
            'percents' => [],
            'empty' => true,
        ];
        if (!$counts) {
            return $empty;
        }

        arsort($counts, SORT_NUMERIC);
        $total = array_sum($counts);
        $labels = [];
        $series = [];
        $names = [];
        $percents = [];
        foreach ($counts as $name => $count) {
            $count = (int) $count;
            $percent = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;
            $percentlabel = self::format_percent($percent);
            $names[] = (string) $name;
            $series[] = $count;
            $percents[] = $percentlabel;
            $labels[] = $name . ' (' . $percentlabel . '%)';
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'names' => $names,
            'percents' => $percents,
            'empty' => false,
        ];
    }

    /**
     * Decide how a category distribution should be presented.
     *
     * @param int $count Number of categories.
     * @param string $prefer Preferred chart for two or more categories. Use MODE_BAR to
     *        force a bar chart, or MODE_DOUGHNUT to allow a doughnut.
     * @param int $doughnutlimit When greater than zero, a doughnut preference switches
     *        to a bar chart once the category count exceeds this limit.
     * @return string One of the MODE_* constants.
     */
    public static function distribution_mode(int $count, string $prefer = self::MODE_DOUGHNUT, int $doughnutlimit = 0): string {
        if ($count < 1) {
            return self::MODE_EMPTY;
        }
        if ($count === 1) {
            return self::MODE_SUMMARY;
        }
        if ($prefer === self::MODE_BAR) {
            return self::MODE_BAR;
        }
        if ($doughnutlimit > 0 && $count > $doughnutlimit) {
            return self::MODE_BAR;
        }
        return self::MODE_DOUGHNUT;
    }

    /**
     * Colours for a doughnut with $count slices.
     *
     * @param int $count Number of slices.
     * @return string[]
     */
    public static function palette(int $count): array {
        if ($count < 1) {
            return [];
        }
        $colors = [];
        $size = count(self::PALETTE);
        for ($i = 0; $i < $count; $i++) {
            $colors[] = self::PALETTE[$i % $size];
        }
        return $colors;
    }
}
