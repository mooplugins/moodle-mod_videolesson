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
 * This is a public endpoint for serving subtitle files via proxy.
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_MOODLE_COOKIES', true);
require_once(__DIR__ . '/../../config.php');

// Get subtitle URL from parameter (decode then enforce URL syntax).
$rawsub = required_param('sub', PARAM_TEXT);
$url = clean_param(urldecode($rawsub), PARAM_URL);
if ($url === '') {
    http_response_code(403);
    exit(get_string('proxy:invalidsource', 'mod_videolesson'));
}

// Validate URL host against configured CloudFront domain.
$parsed = parse_url($url);
if ($parsed === false || empty($parsed['host']) || !empty($parsed['user']) || !empty($parsed['pass'])) {
    http_response_code(403);
    exit(get_string('proxy:invalidsource', 'mod_videolesson'));
}

$hostingtype = get_config('mod_videolesson', 'hosting_type');
if ($hostingtype === 'hosted') {
    $allowedraw = (string) get_config('mod_videolesson', 'cloudfrontdomainhosted');
} else {
    $allowedraw = (string) get_config('mod_videolesson', 'cloudfrontdomain');
}

$allowedraw = trim($allowedraw);
if ($allowedraw === '') {
    http_response_code(403);
    exit(get_string('proxy:invalidsource', 'mod_videolesson'));
}

$expectedhost = '';
if (preg_match('~^https?://~i', $allowedraw)) {
    $allowedparsed = parse_url($allowedraw);
    if (is_array($allowedparsed) && !empty($allowedparsed['host'])) {
        $expectedhost = strtolower($allowedparsed['host']);
    }
} else {
    $expectedhost = strtolower(rtrim($allowedraw, '/'));
}

$path = (string) ($parsed['path'] ?? '');
$lowerpath = strtolower($path);
$allowedpath = str_contains($lowerpath, '/subtitles/')
    && str_ends_with($lowerpath, '.vtt')
    && !str_contains($path, '..');
$portok = empty($parsed['port']) || (int) $parsed['port'] === 443;

if (
    empty($parsed['scheme']) ||
    strtolower($parsed['scheme']) !== 'https' ||
    strtolower($parsed['host']) !== $expectedhost ||
    $expectedhost === '' ||
    !$allowedpath ||
    !$portok
) {
    http_response_code(403);
    exit(get_string('proxy:invalidsource', 'mod_videolesson'));
}

require_once($CFG->libdir . '/filelib.php');
$curl = new \curl();
// String option names are required. Moodle emulates redirects in PHP when
// CURLOPT_FOLLOWLOCATION is left at its default of 1, so it must be 0.
$curl->setopt([
    'CURLOPT_FOLLOWLOCATION' => 0,
    'CURLOPT_MAXREDIRS' => 0,
    'CURLOPT_CONNECTTIMEOUT' => 10,
    'CURLOPT_TIMEOUT' => 20,
]);

try {
    $body = $curl->get($url);
} catch (Throwable $e) {
    http_response_code(404);
    echo get_string('proxy:subtitlenotfound', 'mod_videolesson');
    exit;
}

$info = $curl->get_info();
$httpcode = (int) ($info['http_code'] ?? 0);
$finalhost = strtolower((string) parse_url((string) ($info['url'] ?? ''), PHP_URL_HOST));
if ($curl->get_errno() || $httpcode !== 200 || $finalhost !== $expectedhost || !is_string($body) || $body === '') {
    http_response_code(404);
    echo get_string('proxy:subtitlenotfound', 'mod_videolesson');
    exit;
}

header('Content-Type: text/vtt; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
echo $body;
