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

class send_error_log extends \core\task\scheduled_task {

    public function get_name() {
        return 'Send error log to SLMS Technical'; // use lang string
    }
    /**
     * Execute the task.
    */
    public function execute() {
        global $DB, $CFG, $SITE;
        $sendto = 'technical@scholarlms.com';

        $logs = videolesson_logs::get_records([
            'type' => 'ERROR',
            'senttoadmin' => 0,
        ]);

        $msg = $ids = [];
        foreach ($logs as $log) {
            $ids[] = $log->get('id');
            $msg[] = $log->get('name'). ':' . $log->get('other');
        }

        if (empty($msg)) {
            mtrace('mod_videolesson: No error log.');
            return;
        }

        $messagehtml = implode("<br>", $msg);
        $messagetext = html_to_text($messagehtml);
        $subject = 'VIDEOLESSON ERROR LOGS - '.$CFG->domainid. ' : '. $SITE->fullname;

        $to = new \stdClass();
        $to->email = $sendto;
        $to->firstname = "";
        $to->lastname = "";
        $to->mailformat = 1;
        $to->maildisplay  = true;
        $to->id = -99;
        $to->firstnamephonetic = "";
        $to->lastnamephonetic = "";
        $to->middlename = "";
        $to->alternatename = "";
        $supportuser = \core_user::get_support_user();
        $sent = email_to_user($to, $supportuser, $subject, $messagetext, $messagehtml);

        if ($sent) {
            $ids = implode(", ", $ids);
            $sql = "UPDATE {videolesson_logs} SET senttoadmin = 1 WHERE id IN ($ids)";
            $DB->execute($sql);

            mtrace('mod_videolesson: '.count($msg).' new error/s sent.');
        } else {
            mtrace('mod_videolesson: Failed sending error logs.');
        }
    }
}
