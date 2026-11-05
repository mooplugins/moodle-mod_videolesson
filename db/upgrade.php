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
 * Plugin upgrade steps are defined here.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute mod_videolesson upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_videolesson_upgrade($oldversion) {
    global $DB;

    require_once(__DIR__ . '/upgradelib.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2026081800) {
        mod_videolesson_ensure_bbb_folder_schema($dbman);
        upgrade_mod_savepoint(true, 2026081800, 'videolesson');
    }

    if ($oldversion < 2026090901) {
        // Preserve vendor error-log digests for existing hosted sites after making
        // the recipient setting opt-in empty by default for other hosting modes.
        $hostingtype = get_config('mod_videolesson', 'hosting_type');
        $errorlogemail = get_config('mod_videolesson', 'errorlogemail');
        if ($hostingtype === 'hosted' && ($errorlogemail === false || trim((string) $errorlogemail) === '')) {
            set_config('errorlogemail', \mod_videolesson\logs::DEFAULT_HOSTED_ERRORLOG_EMAIL, 'mod_videolesson');
        }
        upgrade_mod_savepoint(true, 2026090901, 'videolesson');
    }

    if ($oldversion < 2026110301) {
        $usagetable = new xmldb_table('videolesson_usage');
        $usageindex = new xmldb_index('userid_cm_session_ts', XMLDB_INDEX_NOTUNIQUE, ['userid', 'cm', 'session', 'timestamp']);
        if (!$dbman->index_exists($usagetable, $usageindex)) {
            $dbman->add_index($usagetable, $usageindex);
        }

        $progresstable = new xmldb_table('videolesson_cm_progress');
        $progressindex = new xmldb_index('cmid_userid', XMLDB_INDEX_NOTUNIQUE, ['cmid', 'userid']);
        if (!$dbman->index_exists($progresstable, $progressindex)) {
            $dbman->add_index($progresstable, $progressindex);
        }

        upgrade_mod_savepoint(true, 2026110301, 'videolesson');
    }

    return true;
}
