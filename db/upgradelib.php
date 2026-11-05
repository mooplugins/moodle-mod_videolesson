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
 * Upgrade / install helpers for mod_videolesson.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Ensure BBB folder scope column + videolesson_bbb_items match install.xml (idempotent).
 *
 * Needed after videoaws → videolesson table rename: old videoaws_folders has no
 * scope column, and renaming replaces the fresh install.xml table.
 *
 * @param database_manager|null $dbman
 * @param callable|null $output Optional logger; receives a single string message.
 */
function mod_videolesson_ensure_bbb_folder_schema($dbman = null, ?callable $output = null): void {
    global $DB;

    if ($dbman === null) {
        $dbman = $DB->get_manager();
    }

    $log = static function (string $message) use ($output): void {
        if ($output !== null) {
            $output($message);
            return;
        }
        if (function_exists('mtrace')) {
            mtrace(rtrim($message, "\n"));
        }
    };

    // Videolesson_folders.scope column and index.
    $folders = new xmldb_table('videolesson_folders');
    if (!$dbman->table_exists($folders)) {
        $dbman->install_one_table_from_xmldb_file(__DIR__ . '/install.xml', 'videolesson_folders');
        $log("  Recreated missing table videolesson_folders from install.xml\n");
    }

    $scope = new xmldb_field('scope', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'library', 'sortorder');
    if (!$dbman->field_exists($folders, $scope)) {
        $dbman->add_field($folders, $scope);
        $log("  Added videolesson_folders.scope\n");
    }

    $scopeparent = new xmldb_index('scope_parent', XMLDB_INDEX_NOTUNIQUE, ['scope', 'parent']);
    if (!$dbman->index_exists($folders, $scopeparent)) {
        $dbman->add_index($folders, $scopeparent);
        $log("  Added index scope_parent on videolesson_folders\n");
    }

    // Videolesson_bbb_items table.
    $bbbitems = new xmldb_table('videolesson_bbb_items');
    $bbbitems->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $bbbitems->add_field('recordingid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
    $bbbitems->add_field('folderid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $bbbitems->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $bbbitems->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $bbbitems->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $bbbitems->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $bbbitems->add_key('recordingid_unique', XMLDB_KEY_UNIQUE, ['recordingid']);
    $bbbitems->add_key('fk_bbb_folder', XMLDB_KEY_FOREIGN, ['folderid'], 'videolesson_folders', ['id']);
    $bbbitems->add_index('folderid_sortorder', XMLDB_INDEX_NOTUNIQUE, ['folderid', 'sortorder']);

    if (!$dbman->table_exists($bbbitems)) {
        $dbman->create_table($bbbitems);
        $log("  Created table videolesson_bbb_items\n");
        return;
    }

    // Table already exists (partial install/upgrade) — ensure each column/key/index.
    $bbbfields = [
        new xmldb_field('recordingid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id'),
        new xmldb_field('folderid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'recordingid'),
        new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'folderid'),
        new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'sortorder'),
        new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated'),
    ];
    foreach ($bbbfields as $field) {
        if (!$dbman->field_exists($bbbitems, $field)) {
            $dbman->add_field($bbbitems, $field);
            $log("  Added videolesson_bbb_items.{$field->getName()}\n");
        }
    }

    $recordingidunique = new xmldb_key('recordingid_unique', XMLDB_KEY_UNIQUE, ['recordingid']);
    if (!$dbman->find_key_name($bbbitems, $recordingidunique)) {
        $dbman->add_key($bbbitems, $recordingidunique);
        $log("  Added key recordingid_unique on videolesson_bbb_items\n");
    }

    $fkbbbfolder = new xmldb_key('fk_bbb_folder', XMLDB_KEY_FOREIGN, ['folderid'], 'videolesson_folders', ['id']);
    if (!$dbman->find_key_name($bbbitems, $fkbbbfolder)) {
        $dbman->add_key($bbbitems, $fkbbbfolder);
        $log("  Added key fk_bbb_folder on videolesson_bbb_items\n");
    }

    $folderidsort = new xmldb_index('folderid_sortorder', XMLDB_INDEX_NOTUNIQUE, ['folderid', 'sortorder']);
    if (!$dbman->index_exists($bbbitems, $folderidsort)) {
        $dbman->add_index($bbbitems, $folderidsort);
        $log("  Added index folderid_sortorder on videolesson_bbb_items\n");
    }
}
