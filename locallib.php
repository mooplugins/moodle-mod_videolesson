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

define('MOD_VIDEOLESSON_SRC_UPLOAD', 'upload');
define('MOD_VIDEOLESSON_SRC_GALLERY', 'aws');
define('MOD_VIDEOLESSON_SRC_EXTERNAL', 'external');

/**
 * Get the sources options for the videolesson module.
 *
 * @return array The sources options.
 */
function videolesson_sources_options() {
    global $CFG;
    $hostingtype = get_config('mod_videolesson', 'hosting_type');

    $options = [];

    // Only add gallery and upload options if not external hosting type.
    if ($hostingtype !== 'none') {
        $options[MOD_VIDEOLESSON_SRC_UPLOAD] = get_string('video_src_' . MOD_VIDEOLESSON_SRC_UPLOAD, 'mod_videolesson');
        $options[MOD_VIDEOLESSON_SRC_GALLERY] = get_string('video_src_' . MOD_VIDEOLESSON_SRC_GALLERY, 'mod_videolesson');
    }

    $options[MOD_VIDEOLESSON_SRC_EXTERNAL] = get_string('video_src_' . MOD_VIDEOLESSON_SRC_EXTERNAL, 'mod_videolesson');

    return $options;
}

/**
 * Get the player scripts for the videolesson module.
 *
 * @return array The player scripts.
 */
function videolesson_player_scripts() {
    global $CFG;

    $cssfiles = [
        $CFG->wwwroot . '/mod/videolesson/resources/plyr/custom.css',
        $CFG->wwwroot . '/mod/videolesson/resources/plyr/plyr.min.css',
    ];

    $jsfiles = [
        $CFG->wwwroot . '/mod/videolesson/resources/plyr/plyr.polyfilled.min.js',
        $CFG->wwwroot . '/mod/videolesson/resources/hls.min.js',
    ];

    return [
        'cssfiles' => $cssfiles,
        'jsfiles' => $jsfiles,
    ];
}

/**
 * Queue player CSS/JS and optional embed-provider APIs via the page requirements API.
 *
 * Must be called before $OUTPUT->header() so stylesheets are included in the head.
 *
 * @param \moodle_page $page Current page.
 * @param bool $requiresyoutube Whether to load the YouTube iframe API script.
 * @param bool $requiresvimeo Whether to load the Vimeo player API script.
 */
function videolesson_register_player_page_requires(
    \moodle_page $page,
    bool $requiresyoutube = false,
    bool $requiresvimeo = false
): void {
    $assets = videolesson_player_scripts();
    foreach ($assets['cssfiles'] as $url) {
        $page->requires->css(new \moodle_url($url));
    }
    foreach ($assets['jsfiles'] as $url) {
        $page->requires->js(new \moodle_url($url), true);
    }
    if ($requiresyoutube) {
        $page->requires->js(new \moodle_url('https://www.youtube.com/iframe_api'));
    }
    if ($requiresvimeo) {
        $page->requires->js(new \moodle_url('https://player.vimeo.com/api/player.js'));
    }
}
