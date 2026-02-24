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
 * Video
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson\task;

defined('MOODLE_INTERNAL') || die();

/**
 * A scheduled task that runs daily at 1 AM.
 */
class daily_task extends \core\task\scheduled_task {

    /**
     * Returns the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('dailytask', 'mod_videolesson'); // Make sure to add a matching string in your language file.
    }

    /**
     * Executes the task logic.
     */
    public function execute() {
        global $DB;
	return; //disabled
        mtrace("Daily task executed at 1 AM.");

        $license = new \mod_videolesson\license();
        $licensedata = $license->validate($license->get_key());
        $license->save($licensedata);

        $details = $license->get_license_details();

        if (!$details) {
            mtrace("No license details");
            return;
        }

        $current_date = date('Y-m-d');
        $date_expiry = $details['date_expiry'];

        $seven_days_before_expiry = date('Y-m-d', strtotime($date_expiry . ' -7 days'));

        if ($current_date >= $seven_days_before_expiry && $current_date <= $date_expiry) {
            $lastsent = get_config('mod_videolesson', 'expirynotifsent');
            if ($lastsent === false || $lastsent < $current_date) {

                mtrace("send message");

                $subject = get_string('expirynotification:subject', 'mod_videolesson', $date_expiry);
                $message = get_string('expirynotification:message', 'mod_videolesson', $date_expiry);
                \mod_videolesson\util::send_notification_to_admins($subject, $message);
                set_config('expirynotifsent', $current_date, 'mod_videolesson');
            }
        }

        mtrace("exit");
        return;
    }
}
