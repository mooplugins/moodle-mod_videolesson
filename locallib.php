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
 * Locallib
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('VIDEO_SRC_UPLOAD', 'upload');
define('VIDEO_SRC_GALLERY', 'aws');
define('VIDEO_SRC_EXTERNAL', 'external');

function videolesson_sources_options() {
    $hostingtype = get_config('mod_videolesson', 'hosting_type');

    $options = [];

    // Only add gallery and upload options if not external hosting type
    if ($hostingtype !== 'none') {
        $options[VIDEO_SRC_UPLOAD] = get_string('video_src_'.VIDEO_SRC_UPLOAD, 'mod_videolesson');
        $options[VIDEO_SRC_GALLERY] = get_string('video_src_'.VIDEO_SRC_GALLERY, 'mod_videolesson');
    }

    $options[VIDEO_SRC_EXTERNAL] = get_string('video_src_'.VIDEO_SRC_EXTERNAL, 'mod_videolesson');

    return $options;
}

function videolesson_player_scripts() {
    global $CFG;

    $cssfiles = [
        $CFG->wwwroot.'/mod/videolesson/resources/plyr/custom.css',
        $CFG->wwwroot.'/mod/videolesson/resources/plyr/plyr.min.css',
    ];

    $jsfiles = [
        $CFG->wwwroot.'/mod/videolesson/resources/plyr/plyr.polyfilled.min.js',
        $CFG->wwwroot.'/mod/videolesson/resources/hls.min.js',
        $CFG->wwwroot.'/mod/videolesson/resources/bowser.min.js',
    ];

    return [
        'cssfiles' => $cssfiles,
        'jsfiles' => $jsfiles,
    ];
}
