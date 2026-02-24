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
 * Proxy for subtitles.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// Get subtitle URL from parameter
$url = required_param('sub', PARAM_RAW_TRIMMED); // Accepts full CloudFront URLs
$url = urldecode($url);

// Set appropriate headers
header('Content-Type: text/vtt; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600'); // Optional caching

// Stream the file
$opts = ['http' => ['method' => 'GET']];
$context = stream_context_create($opts);

$stream = @fopen($url, 'r', false, $context);
if ($stream === false) {
    http_response_code(404);
    echo "Subtitle not found.";
    exit;
}

// Output the file
fpassthru($stream);
fclose($stream);
