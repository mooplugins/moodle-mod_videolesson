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
 * Migration script to migrate data from videoaws plugin to videolesson plugin.
 *
 * This script should be run after installing the videolesson plugin when migrating
 * from the old videoaws plugin. It renames all database tables, updates references,
 * and migrates all related data.
 *
 * Usage:
 *   php mod/videolesson/migrate_from_videoaws.php
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Suppress errors during initial load
$olderror = error_reporting(0);
require_once(__DIR__ . '/../../config.php');
error_reporting($olderror);

if (!isset($CFG)) {
    die('ERROR: Could not load Moodle configuration. Make sure this script is run from within Moodle.');
}

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/accesslib.php');

// Require admin access
require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

// Check if migration has already been completed
$migrated = get_config('mod_videolesson', 'videoaws_migrate_done');
if ($migrated) {
    // Simple HTML page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Migration Already Completed</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 1200px;
                margin: 20px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .message {
                background: #fff;
                padding: 20px;
                border-radius: 5px;
                border-left: 4px solid #28a745;
            }
        </style>
    </head>
    <body>
        <div class="message">
            <h1>Migration Already Completed</h1>
            <p>The migration from videoaws to videolesson has already been completed.</p>
            <p>If you need to run the migration again, please reset the flag first.</p>
            <p><videolesson_migrate_done_already></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Simple HTML page
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Migrate from VideoAWS to Video Lesson</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 10px;
        }
        .migration-output {
            font-family: monospace;
            white-space: pre-wrap;
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 600px;
            overflow-y: auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Migration: VideoAWS to Video Lesson</h1>
    <div class="migration-output">
<?php

// Output function for web
function migration_output($message) {
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "\n";
    flush();
    if (ob_get_level() > 0) {
        ob_flush();
    }
}

migration_output("Starting migration from videoaws to videolesson...\n\n");

global $DB;
$dbman = $DB->get_manager();

// Rename all database tables
if (!get_config('mod_videolesson', 'videoaws_migrate_tables')) {
    $tables = [
    'videoaws' => 'videolesson',
    'videoaws_data' => 'videolesson_data',
    'videoaws_data_external' => 'videolesson_data_external',
    'videoaws_conv' => 'videolesson_conv',
    'videoaws_logs' => 'videolesson_logs',
    'videoaws_queue_msgs' => 'videolesson_queue_msgs',
    'videoaws_usage' => 'videolesson_usage',
    'videoaws_cm_progress' => 'videolesson_cm_progress',
    'videoaws_folders' => 'videolesson_folders',
    'videoaws_folder_items' => 'videolesson_folder_items',
    'videoaws_subtitles' => 'videolesson_subtitles',
];

migration_output("Dropping empty videolesson tables created during installation...\n");
foreach ($tables as $oldname => $newname) {
    $newtable = new xmldb_table($newname);
    if ($dbman->table_exists($newtable)) {
        // Safety check: only drop if table is empty
        $count = $DB->count_records($newname, []);
        if ($count === 0) {
            $dbman->drop_table($newtable);
            migration_output("  Dropped empty table {$newname}\n");
        } else {
            migration_output("  Skipped table {$newname} (not empty, has {$count} records)\n");
        }
    }
}

migration_output("\nRenaming videoaws tables to videolesson...\n");
foreach ($tables as $oldname => $newname) {
    $oldtable = new xmldb_table($oldname);
    $newtable = new xmldb_table($newname);

    if ($dbman->table_exists($oldtable)) {
        // Safety check: only rename if new table doesn't exist
        if ($dbman->table_exists($newtable)) {
            migration_output("  Skipped renaming {$oldname} to {$newname} (target table already exists)\n");
        } else {
            $dbman->rename_table($oldtable, $newname);
            migration_output("  Renamed table {$oldname} to {$newname}\n");
        }
    }
}
    set_config('videoaws_migrate_tables', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping table migration (already completed)\n");
}

// Update foreign key references in videolesson_folder_items
if (!get_config('mod_videolesson', 'videoaws_migrate_folder_items_keys')) {
    migration_output("\nUpdating foreign keys in videolesson_folder_items...\n");
    $table = new xmldb_table('videolesson_folder_items');
    if ($dbman->table_exists($table)) {
    // Rename videoawsid field to videolessonid if it exists
    // Full field specs are required for rename_field
    $field = new xmldb_field('videoawsid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, 'Reference to videolesson_conv table');
    if ($dbman->field_exists($table, $field)) {
        $dbman->rename_field($table, $field, 'videolessonid');
        migration_output("  Renamed field videoawsid to videolessonid\n");
    }
    // Drop old foreign key
    $key = new xmldb_key('fk_videoaws', XMLDB_KEY_FOREIGN, ['videolessonid'], 'videolesson_conv', ['id']);
    if ($dbman->find_key_name($table, $key) !== false) {
        $dbman->drop_key($table, $key);
        migration_output("  Dropped old foreign key fk_videoaws\n");
    }
    // Drop old unique key
    $key = new xmldb_key('videoawsid_unique', XMLDB_KEY_UNIQUE, ['videolessonid']);
    if ($dbman->find_key_name($table, $key) !== false) {
        $dbman->drop_key($table, $key);
        migration_output("  Dropped old unique key videoawsid_unique\n");
    }
    // Add new foreign key
    $key = new xmldb_key('fk_videolesson', XMLDB_KEY_FOREIGN, ['videolessonid'], 'videolesson_conv', ['id']);
    $dbman->add_key($table, $key);
    migration_output("  Added new foreign key fk_videolesson\n");
    // Add new unique key
    $key = new xmldb_key('videolessonid_unique', XMLDB_KEY_UNIQUE, ['videolessonid']);
    $dbman->add_key($table, $key);
    migration_output("  Added new unique key videolessonid_unique\n");
    }
    set_config('videoaws_migrate_folder_items_keys', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping videolesson_folder_items foreign keys (already completed)\n");
}

// Update foreign key references in videolesson_folders (self-reference)
if (!get_config('mod_videolesson', 'videoaws_migrate_folders_keys')) {
    migration_output("\nUpdating foreign keys in videolesson_folders...\n");
    $table = new xmldb_table('videolesson_folders');
    if ($dbman->table_exists($table)) {
    // Drop old foreign key
    $key = new xmldb_key('fk_vawsfolder_parent', XMLDB_KEY_FOREIGN, ['parent'], 'videolesson_folders', ['id']);
    if ($dbman->find_key_name($table, $key) !== false) {
        $dbman->drop_key($table, $key);
        migration_output("  Dropped old foreign key fk_vawsfolder_parent\n");
    }
    // Add new foreign key
    $key = new xmldb_key('fk_vawsfolder_parent', XMLDB_KEY_FOREIGN, ['parent'], 'videolesson_folders', ['id']);
    $dbman->add_key($table, $key);
    migration_output("  Added new foreign key fk_vawsfolder_parent\n");
    }
    set_config('videoaws_migrate_folders_keys', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping videolesson_folders foreign keys (already completed)\n");
}

// Update foreign key references in videolesson_conv
if (!get_config('mod_videolesson', 'videoaws_migrate_conv_keys')) {
    migration_output("\nUpdating foreign keys in videolesson_conv...\n");
    $table = new xmldb_table('videolesson_conv');
    if ($dbman->table_exists($table)) {
    // Drop old foreign key
    $key = new xmldb_key('contenthash', XMLDB_KEY_FOREIGN_UNIQUE, ['contenthash'], 'videolesson_data', ['contenthash']);
    if ($dbman->find_key_name($table, $key) !== false) {
        $dbman->drop_key($table, $key);
        migration_output("  Dropped old foreign key contenthash\n");
    }
    // Add new foreign key
    $key = new xmldb_key('contenthash', XMLDB_KEY_FOREIGN_UNIQUE, ['contenthash'], 'videolesson_data', ['contenthash']);
    $dbman->add_key($table, $key);
    migration_output("  Added new foreign key contenthash\n");
    }
    set_config('videoaws_migrate_conv_keys', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping videolesson_conv foreign keys (already completed)\n");
}

// Update foreign key references in videolesson_subtitles
if (!get_config('mod_videolesson', 'videoaws_migrate_subtitles_keys')) {
    migration_output("\nUpdating foreign keys in videolesson_subtitles...\n");
    $table = new xmldb_table('videolesson_subtitles');
    if ($dbman->table_exists($table)) {
    // Drop old foreign key
    $key = new xmldb_key('fk_subtitle_contenthash', XMLDB_KEY_FOREIGN, ['contenthash'], 'videolesson_data', ['contenthash']);
    if ($dbman->find_key_name($table, $key) !== false) {
        $dbman->drop_key($table, $key);
        migration_output("  Dropped old foreign key fk_subtitle_contenthash\n");
    }
    // Add new foreign key
    $key = new xmldb_key('fk_subtitle_contenthash', XMLDB_KEY_FOREIGN, ['contenthash'], 'videolesson_data', ['contenthash']);
    $dbman->add_key($table, $key);
    migration_output("  Added new foreign key fk_subtitle_contenthash\n");
    }
    set_config('videoaws_migrate_subtitles_keys', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping videolesson_subtitles foreign keys (already completed)\n");
}

// Update mdl_modules table entry
if (!get_config('mod_videolesson', 'videoaws_migrate_modules')) {
    migration_output("\nUpdating module references...\n");
    $oldmoduleid = $DB->get_field('modules', 'id', ['name' => 'videoaws'], IGNORE_MISSING);
$newmoduleid = $DB->get_field('modules', 'id', ['name' => 'videolesson'], IGNORE_MISSING);

if ($oldmoduleid && $newmoduleid) {
    // Both old and new module entries exist
    // Update course_modules to point to the new module ID
    $count = $DB->count_records('course_modules', ['module' => $oldmoduleid]);
    if ($count > 0) {
        $DB->execute("UPDATE {course_modules} SET module = ? WHERE module = ?", [$newmoduleid, $oldmoduleid]);
        migration_output("  Updated {$count} course_modules entries to use new module ID\n");
    }
    // Delete the old module entry
    $DB->delete_records('modules', ['id' => $oldmoduleid]);
    migration_output("  Deleted old module entry (ID: {$oldmoduleid})\n");
} else if ($oldmoduleid && !$newmoduleid) {
    // Only old module exists, rename it
    $DB->execute("UPDATE {modules} SET name = ? WHERE name = ?", ['videolesson', 'videoaws']);
    migration_output("  Renamed module entry from videoaws to videolesson\n");
} else {
    migration_output("  WARNING: Could not find module entries to update\n");
    }
    set_config('videoaws_migrate_modules', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping module references (already completed)\n");
}

// Update mdl_config_plugins entries
if (!get_config('mod_videolesson', 'videoaws_migrate_config')) {
    migration_output("\nUpdating config_plugins entries...\n");
    // Get all config entries for mod_videoaws (excluding version)
    $old_configs = $DB->get_records_select('config_plugins', "plugin = ? AND name != ?", ['mod_videoaws', 'version']);
    $updated_count = 0;
    $deleted_count = 0;

    foreach ($old_configs as $old_config) {
        // Check if mod_videolesson already has an entry with the same name
        $existing_config = $DB->get_record('config_plugins', [
            'plugin' => 'mod_videolesson',
            'name' => $old_config->name
        ], 'id', IGNORE_MISSING);

        if ($existing_config) {
            // Entry already exists in new plugin, delete the old one to avoid duplicate
            $DB->delete_records('config_plugins', ['id' => $old_config->id]);
            $deleted_count++;
        } else {
            // Safe to update - no duplicate will be created
            $DB->execute("UPDATE {config_plugins} SET plugin = ? WHERE id = ?", ['mod_videolesson', $old_config->id]);
            $updated_count++;
        }
    }

    if ($updated_count > 0) {
        migration_output("  Updated {$updated_count} config_plugins entries\n");
    }
    if ($deleted_count > 0) {
        migration_output("  Deleted {$deleted_count} duplicate config_plugins entries (already exist in new plugin)\n");
    }

    // Delete the old version entry if it exists (the new plugin already has its own version)
    $old_version = $DB->get_record('config_plugins', ['plugin' => 'mod_videoaws', 'name' => 'version'], 'id', IGNORE_MISSING);
    if ($old_version) {
        $DB->delete_records('config_plugins', ['id' => $old_version->id]);
        migration_output("  Deleted old mod_videoaws version entry\n");
    }

    set_config('videoaws_migrate_config', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping config_plugins entries (already completed)\n");
}

// Update mdl_files component references
if (!get_config('mod_videolesson', 'videoaws_migrate_files')) {
    migration_output("\nUpdating file component references...\n");
    $count = $DB->count_records('files', ['component' => 'mod_videoaws']);
if ($count > 0) {
    $DB->execute("UPDATE {files} SET component = ? WHERE component = ?", ['mod_videolesson', 'mod_videoaws']);
    migration_output("  Updated {$count} file component references\n");
    }
    set_config('videoaws_migrate_files', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping file component references (already completed)\n");
}

// Update capability definitions in mdl_capabilities table
if (!get_config('mod_videolesson', 'videoaws_migrate_capabilities')) {
    migration_output("\nUpdating capability definitions...\n");
    // Get all old capabilities
    $old_capabilities = $DB->get_records_select('capabilities', "component = ? AND name LIKE ?", ['mod_videoaws', 'mod/videoaws:%']);
    if (!empty($old_capabilities)) {
    $updated_count = 0;
    $skipped_count = 0;
    $deleted_count = 0;

    foreach ($old_capabilities as $old_cap) {
        // Calculate what the new capability name would be
        $new_cap_name = str_replace('mod/videoaws:', 'mod/videolesson:', $old_cap->name);

        // Check if the new capability already exists (created during installation)
        $existing_cap = $DB->get_record('capabilities', ['name' => $new_cap_name], 'id', IGNORE_MISSING);

        if ($existing_cap) {
            // New capability already exists, skip updating and delete the old one
            $DB->delete_records('capabilities', ['id' => $old_cap->id]);
            $deleted_count++;
        } else {
            // Safe to update - no duplicate will be created
            $DB->execute("UPDATE {capabilities} SET name = ?, component = ? WHERE id = ?",
                [$new_cap_name, 'mod_videolesson', $old_cap->id]);
            $updated_count++;
        }
    }

    if ($updated_count > 0) {
        migration_output("  Updated {$updated_count} capability definitions\n");
    }
    if ($deleted_count > 0) {
        migration_output("  Deleted {$deleted_count} duplicate capability definitions (already exist in new plugin)\n");
    }
    if ($skipped_count > 0) {
        migration_output("  Skipped {$skipped_count} capability definitions\n");
    }
    }
    set_config('videoaws_migrate_capabilities', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping capability definitions (already completed)\n");
}

// Update capability assignments in mdl_role_capabilities
if (!get_config('mod_videolesson', 'videoaws_migrate_role_capabilities')) {
    migration_output("\nUpdating capability assignments in role_capabilities...\n");
    // Get all old role capability assignments
    $old_role_caps = $DB->get_records_select('role_capabilities', "capability LIKE ?", ['mod/videoaws:%']);
    if (!empty($old_role_caps)) {
    $updated_count = 0;
    $deleted_count = 0;

    foreach ($old_role_caps as $old_role_cap) {
        // Calculate what the new capability name would be
        $new_cap_name = str_replace('mod/videoaws:', 'mod/videolesson:', $old_role_cap->capability);

        // Check if the new capability assignment already exists for the same role and context
        // Unique constraint is on (roleid, contextid, capability)
        $existing_role_cap = $DB->get_record('role_capabilities', [
            'roleid' => $old_role_cap->roleid,
            'contextid' => $old_role_cap->contextid,
            'capability' => $new_cap_name
        ], 'id', IGNORE_MISSING);

        if ($existing_role_cap) {
            // New capability assignment already exists, delete the old one to avoid duplicate
            $DB->delete_records('role_capabilities', ['id' => $old_role_cap->id]);
            $deleted_count++;
        } else {
            // Safe to update - no duplicate will be created
            $DB->execute("UPDATE {role_capabilities} SET capability = ? WHERE id = ?",
                [$new_cap_name, $old_role_cap->id]);
            $updated_count++;
        }
    }

    if ($updated_count > 0) {
        migration_output("  Updated {$updated_count} capability assignments\n");
    }
    if ($deleted_count > 0) {
        migration_output("  Deleted {$deleted_count} duplicate capability assignments (already exist in new plugin)\n");
    }
    }
    set_config('videoaws_migrate_role_capabilities', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping capability assignments (already completed)\n");
}

// Ensure capabilities are properly registered
migration_output("\nRegistering capabilities...\n");
update_capabilities('mod_videolesson');
migration_output("  Capabilities registered\n");

// Update task_scheduled classname references
if (!get_config('mod_videolesson', 'videoaws_migrate_scheduled_tasks')) {
    migration_output("\nUpdating scheduled tasks...\n");
    // Get all old scheduled tasks
    $old_scheduled_tasks = $DB->get_records_select('task_scheduled', "classname LIKE ?", ['%mod_videoaws%']);
    if (!empty($old_scheduled_tasks)) {
    $updated_count = 0;
    $deleted_count = 0;

    foreach ($old_scheduled_tasks as $old_task) {
        // Calculate what the new classname would be
        $new_classname = str_replace('mod_videoaws', 'mod_videolesson', $old_task->classname);

        // Check if the new classname already exists (unique constraint on classname)
        $existing_task = $DB->get_record('task_scheduled', ['classname' => $new_classname], 'id', IGNORE_MISSING);

        if ($existing_task) {
            // New task already exists, delete the old one to avoid duplicate
            $DB->delete_records('task_scheduled', ['id' => $old_task->id]);
            $deleted_count++;
        } else {
            // Safe to update - no duplicate will be created
            $DB->execute("UPDATE {task_scheduled} SET classname = ? WHERE id = ?",
                [$new_classname, $old_task->id]);
            $updated_count++;
        }
    }

    if ($updated_count > 0) {
        migration_output("  Updated {$updated_count} scheduled task references\n");
    }
    if ($deleted_count > 0) {
        migration_output("  Deleted {$deleted_count} duplicate scheduled tasks (already exist in new plugin)\n");
    }
    }
    set_config('videoaws_migrate_scheduled_tasks', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping scheduled tasks (already completed)\n");
}

// Update adhoc tasks
if (!get_config('mod_videolesson', 'videoaws_migrate_adhoc_tasks')) {
    migration_output("\nUpdating adhoc tasks...\n");
    // Get all old adhoc tasks
    $old_adhoc_tasks = $DB->get_records_select('task_adhoc', "classname LIKE ?", ['%mod_videoaws%']);
    if (!empty($old_adhoc_tasks)) {
    $updated_count = 0;
    $deleted_count = 0;

    foreach ($old_adhoc_tasks as $old_task) {
        // Calculate what the new classname would be
        $new_classname = str_replace('mod_videoaws', 'mod_videolesson', $old_task->classname);

        // Check if a similar task already exists with the new classname
        // Adhoc tasks can have duplicates, but we check for exact matches to avoid unnecessary duplicates
        $existing_task = $DB->get_record('task_adhoc', [
            'classname' => $new_classname,
            'component' => str_replace('mod_videoaws', 'mod_videolesson', $old_task->component),
            'customdata' => $old_task->customdata,
            'userid' => $old_task->userid
        ], 'id', IGNORE_MISSING);

        if ($existing_task) {
            // Similar task already exists, delete the old one to avoid duplicate
            $DB->delete_records('task_adhoc', ['id' => $old_task->id]);
            $deleted_count++;
        } else {
            // Safe to update - no duplicate will be created
            $new_component = str_replace('mod_videoaws', 'mod_videolesson', $old_task->component);
            $DB->execute("UPDATE {task_adhoc} SET classname = ?, component = ? WHERE id = ?",
                [$new_classname, $new_component, $old_task->id]);
            $updated_count++;
        }
    }

    if ($updated_count > 0) {
        migration_output("  Updated {$updated_count} adhoc task references\n");
    }
    if ($deleted_count > 0) {
        migration_output("  Deleted {$deleted_count} duplicate adhoc tasks (already exist in new plugin)\n");
    }
    }
    set_config('videoaws_migrate_adhoc_tasks', 1, 'mod_videolesson');
} else {
    migration_output("\nSkipping adhoc tasks (already completed)\n");
}

migration_output("\n✓ Migration from videoaws to videolesson completed successfully!");
migration_output("\nNext steps:");
migration_output("  1. Clear Moodle caches (Site administration → Development → Purge all caches)");
migration_output("  2. Verify the plugin is working correctly");
migration_output("  3. Test video playback in courses");
migration_output("  <videolesson_migrate_done>");

// Set migration flag in config
set_config('videoaws_migrate_done', 1, 'mod_videolesson');

?>
    </div>
</body>
</html>

