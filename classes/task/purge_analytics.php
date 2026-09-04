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
 * Purge aged playback analytics.
 *
 * @package    mod_videolesson
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson\task;

/**
 * Deletes aged videolesson_usage rows according to the retention setting.
 *
 * Progress rows are not purged here; they remain for resume/completion until
 * activity deletion or Privacy API erasure.
 *
 * @package    mod_videolesson
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purge_analytics extends \core\task\scheduled_task {
    /** @var int Maximum usage rows deleted per batch. */
    public const BATCH_SIZE = 10000;

    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:purgeanalytics', 'mod_videolesson');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $retentiondays = (int) get_config('mod_videolesson', 'analyticsretentiondays');
        if ($retentiondays <= 0) {
            mtrace('mod_videolesson: analytics retention disabled (analyticsretentiondays=0).');
            return;
        }

        $cutoff = time() - ($retentiondays * DAYSECS);
        mtrace('mod_videolesson: purging videolesson_usage with timemodified < ' . $cutoff
            . ' (retention ' . $retentiondays . ' days).');

        $total = 0;
        do {
            $records = $DB->get_records_select(
                'videolesson_usage',
                'timemodified < :cutoff',
                ['cutoff' => $cutoff],
                'id ASC',
                'id',
                0,
                self::BATCH_SIZE
            );
            if (empty($records)) {
                break;
            }

            $ids = array_keys($records);
            [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('videolesson_usage', "id $insql", $inparams);
            $batchcount = count($ids);
            $total += $batchcount;
            mtrace('mod_videolesson: deleted ' . $batchcount . ' usage row(s) (running total ' . $total . ').');
        } while ($batchcount === self::BATCH_SIZE);

        mtrace('mod_videolesson: purge_analytics finished; deleted ' . $total . ' usage row(s).');
    }
}
