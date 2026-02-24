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
 * Web services
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_videolesson_monitor' => [
        'classname'   => 'mod_videolesson\external\watchtime',
        'description' => 'Save user watch activities',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_videocheck' => [
        'classname'   => 'mod_videolesson\external\videocheck',
        'description' => 'Check if video is available',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => false,
    ],
    'mod_videolesson_getsubtitles' => [
        'classname'   => 'mod_videolesson\external\getsubtitles',
        'description' => 'Check if video has subtitles',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => false,
    ],
    'mod_videolesson_create_folder' => [
        'classname'   => 'mod_videolesson\external\create_folder',
        'description' => 'Create a new folder',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_update_folder' => [
        'classname'   => 'mod_videolesson\external\update_folder',
        'description' => 'Update folder name or move folder',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_delete_folder' => [
        'classname'   => 'mod_videolesson\external\delete_folder',
        'description' => 'Delete a folder',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_get_folder_tree' => [
        'classname'   => 'mod_videolesson\external\get_folder_tree',
        'description' => 'Get folder tree structure',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_move_video' => [
        'classname'   => 'mod_videolesson\external\move_video',
        'description' => 'Move video to folder',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_get_videos' => [
        'classname'   => 'mod_videolesson\external\get_videos',
        'description' => 'Get paginated list of videos',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_update_sortorder' => [
        'classname'   => 'mod_videolesson\external\update_sortorder',
        'description' => 'Update video sort order in folder',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_bulk_move_videos' => [
        'classname'   => 'mod_videolesson\external\bulk_move_videos',
        'description' => 'Move multiple videos to a folder',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_bulk_delete_videos' => [
        'classname'   => 'mod_videolesson\external\bulk_delete_videos',
        'description' => 'Delete multiple videos',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_trigger_subtitle' => [
        'classname'   => 'mod_videolesson\external\trigger_subtitle',
        'description' => 'Trigger subtitle generation for a video',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_get_subtitle_languages' => [
        'classname'   => 'mod_videolesson\external\get_subtitle_languages',
        'description' => 'Get list of supported subtitle languages',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_generate_license' => [
        'classname'   => 'mod_videolesson\external\generate_license',
        'description' => 'Generate a free license key and register user',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_mark_setup_step_complete' => [
        'classname'   => 'mod_videolesson\external\mark_setup_step_complete',
        'description' => 'Mark a setup step as complete',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_complete_setup' => [
        'classname'   => 'mod_videolesson\external\complete_setup',
        'description' => 'Mark the entire setup as complete',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_save_aws_settings' => [
        'classname'   => 'mod_videolesson\external\save_aws_settings',
        'description' => 'Save AWS settings from setup wizard',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_save_license_key' => [
        'classname'   => 'mod_videolesson\external\save_license_key',
        'description' => 'Save license key from setup wizard',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_save_hosting_type' => [
        'classname'   => 'mod_videolesson\external\save_hosting_type',
        'description' => 'Save hosting type from setup wizard',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_validate_aws_connection' => [
        'classname'   => 'mod_videolesson\external\validate_aws_connection',
        'description' => 'Validate AWS connection credentials',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_videolesson_activate_free_hosting' => [
        'classname'   => 'mod_videolesson\external\activate_free_hosting',
        'description' => 'Activate free hosted hosting plan',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
