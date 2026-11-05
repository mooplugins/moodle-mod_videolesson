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
 * Send error log.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson\task;

use mod_videolesson\logs as videolesson_logs;

/**
 * Send error log task
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_error_log extends \core\task\scheduled_task {
    /**
     * Get the name of the task
     * @return string The name of the task
     */
    public function get_name() {
        return get_string('task:senderrorlog', 'mod_videolesson');
    }

    /**
     * Execute the task.
     *
     * Emails unsent ERROR logs only when mod_videolesson/errorlogemail is set.
     * Rows are not marked sent when no recipient is configured.
     */
    public function execute() {
        global $DB, $CFG, $SITE;

        $sendto = trim((string) get_config('mod_videolesson', 'errorlogemail'));
        if ($sendto === '' || !validate_email($sendto)) {
            mtrace('mod_videolesson: error log email skipped (errorlogemail empty or invalid).');
            return;
        }

        $logs = videolesson_logs::get_records([
            'type' => 'ERROR',
            'senttoadmin' => 0,
        ]);

        $ids = [];
        $htmlparts = [];
        $textparts = [];
        foreach ($logs as $log) {
            $ids[] = $log->get('id');
            $line = $log->get('name') . ':' . $log->get('other');
            $textparts[] = $line;
            $htmlparts[] = s($line);
        }

        if (empty($textparts)) {
            mtrace('mod_videolesson: No error log.');
            return;
        }

        $messagehtml = implode('<br>', $htmlparts);
        $messagetext = implode("\n", $textparts);
        $subject = 'VIDEOLESSON ERROR LOGS - ' . $CFG->domainid . ' : ' . $SITE->fullname;

        $to = new \stdClass();
        $to->email = $sendto;
        $to->firstname = "";
        $to->lastname = "";
        $to->mailformat = 1;
        $to->maildisplay = true;
        $to->id = -99;
        $to->firstnamephonetic = "";
        $to->lastnamephonetic = "";
        $to->middlename = "";
        $to->alternatename = "";
        $supportuser = \core_user::get_support_user();
        $sent = email_to_user($to, $supportuser, $subject, $messagetext, $messagehtml);

        if ($sent) {
            [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'id');
            $sql = "UPDATE {videolesson_logs}
                       SET senttoadmin = 1
                     WHERE id $insql";
            $DB->execute($sql, $inparams);

            mtrace('mod_videolesson: ' . count($textparts) . ' new error/s sent.');
        } else {
            mtrace('mod_videolesson: Failed sending error logs.');
        }
    }
}
