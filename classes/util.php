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
 * Utility functions
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');
/**
 * Utility class
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /**
     * Format the duration.
     *
     * @param int $seconds The duration in seconds.
     * @param string $format The format of the duration.
     * @return string The formatted duration.
     */
    public static function durationformat($seconds, $format = "%02d:%02d:%02d") {
        // Round the seconds for accuracy.
        $seconds = round($seconds);

        // Calculate hours, minutes, and remaining seconds.
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        // Format the duration using the provided format.
        return sprintf($format, $hours, $minutes, $seconds);
    }

    /**
     * Check if the URL is a YouTube URL.
     *
     * @param string $url The URL to check.
     * @return bool True if the URL is a YouTube URL, false otherwise.
     */
    public static function is_youtube_url($url) {
        // YouTube URL pattern.
        $pattern = '/^(https?:\/\/)?(www\.)?'
            . '(youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|'
            . 'youtu\.be\/)'
            . '([a-zA-Z0-9_-]{11})/';

        // Check if the URL matches the pattern.
        return (bool) preg_match($pattern, $url);
    }
    /**
     * Check if the URL is a Vimeo URL.
     *
     * @param string $url The URL to check.
     * @return bool True if the URL is a Vimeo URL, false otherwise.
     */
    public static function is_vimeo_url($url) {
        // Vimeo URL pattern.
        $pattern = '/^(https?:\/\/)?(www\.)?(vimeo\.com\/)([0-9]{8,})/';

        // Check if the URL matches the pattern.
        return (bool) preg_match($pattern, $url);
    }

    /**
     * Known video and streaming file extensions used for local URL validation.
     *
     * @return string[]
     */
    private static function video_file_extensions(): array {
        return ['mp4', 'm4v', 'webm', 'mkv', 'm3u8'];
    }

    /**
     * Check if the URL is a video URL.
     *
     * Validation is local only: YouTube/Vimeo host patterns, or http(s) URLs with a known
     * video/stream file extension. This must not issue network requests.
     *
     * @param string $url The URL to check.
     * @return bool True if the URL is a video URL, false otherwise.
     */
    public static function is_video_url($url) {
        if (!is_string($url) || trim($url) === '') {
            return false;
        }

        // Check for YouTube URLs (including youtu.be short URLs).
        if (self::is_youtube_url($url)) {
            return true;
        }

        // Check for Vimeo URLs.
        if (self::is_vimeo_url($url)) {
            return true;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::video_file_extensions(), true);
    }

    /**
     * Check if the code is an embed code.
     *
     * @param string $code The code to check.
     * @return bool True if the code is an embed code, false otherwise.
     */
    public static function is_embed_code($code) {
        // Check for the presence of an iframe tag.
        if (stripos($code, '<iframe') !== false) {
            return true;
        }

        // Check if it's a YouTube URL.
        if (self::is_youtube_url($code) || self::is_youtube_embed_url($code)) {
            return true;
        }

        // Check if it's a Vimeo URL.
        if (self::is_vimeo_url($code) || self::is_vimeo_embed_url($code)) {
            return true;
        }

        return false;
    }

    /**
     * Extract the YouTube video ID from the URL.
     *
     * @param string $url The URL to extract the video ID from.
     * @return string|false The video ID or false if not found.
     */
    public static function extract_youtube_video_id($url) {
        $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        preg_match($pattern, $url, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Extract the Vimeo video ID from the URL.
     *
     * @param string $url The URL to extract the video ID from.
     * @return string|false The video ID or false if not found.
     */
    public static function extract_vimeo_video_id($url) {
        $pattern = '/(?:vimeo\.com\/|video\/)(\d+)/';
        preg_match($pattern, $url, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Build a YouTube embed URL for the given video ID.
     *
     * @param string $videoid
     * @param string $origin
     * @return string
     */
    public static function get_youtube_embed_url(string $videoid, string $origin = ''): string {
        $params = [
            'enablejsapi' => 1,
            'rel' => 0,
            'playsinline' => 1,
        ];
        if (!empty($origin)) {
            $params['origin'] = $origin;
        }
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        return 'https://www.youtube.com/embed/' . $videoid . '?' . $query;
    }

    /**
     * Build a Vimeo embed URL for the given video ID.
     *
     * @param string $videoid
     * @return string
     */
    public static function get_vimeo_embed_url(string $videoid): string {
        return 'https://player.vimeo.com/video/' . $videoid;
    }

    /**
     * Retrieve a YouTube thumbnail URL for the given video ID.
     *
     * @param string $videoid
     * @return string
     */
    public static function get_youtube_thumbnail(string $videoid): string {
        return 'https://img.youtube.com/vi/' . $videoid . '/hqdefault.jpg';
    }

    /**
     * Extract URL from embed code (iframe src).
     *
     * @param string $embedcode The embed code (HTML with iframe)
     * @return string|false The extracted URL or false if not found
     */
    public static function extract_url_from_embed_code($embedcode) {
        if (!is_string($embedcode) || trim($embedcode) === '') {
            return false;
        }

        // Extract URL from iframe embed code before treating input as a direct URL.
        if (stripos($embedcode, '<iframe') !== false) {
            if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/', $embedcode, $matches)) {
                return $matches[1];
            }
            return false;
        }

        // Direct URL inputs (YouTube/Vimeo/direct video).
        if (
            self::is_youtube_url($embedcode) || self::is_youtube_embed_url($embedcode) ||
            self::is_vimeo_url($embedcode) || self::is_vimeo_embed_url($embedcode) ||
            self::is_video_url($embedcode)
        ) {
            return $embedcode;
        }

        return false;
    }

    /**
     * Detect the type of external source from input.
     *
     * @param string $input The input (URL or embed code)
     * @return string|false one of youtube, vimeo, direct_video, unsupported_embed, or false.
     */
    public static function detect_external_source_type($input) {
        if (!is_string($input) || trim($input) === '') {
            return false;
        }

        $url = self::extract_url_from_embed_code($input);
        if (!$url) {
            return false; // Invalid input.
        }

        if (self::is_youtube_url($url) || self::is_youtube_embed_url($url)) {
            return 'youtube';
        }
        if (self::is_vimeo_url($url) || self::is_vimeo_embed_url($url)) {
            return 'vimeo';
        }
        if (self::is_video_url($url)) {
            return 'direct_video';
        }
        // If it's an iframe embed but not YouTube/Vimeo, it's unsupported.
        if (stripos($input, '<iframe') !== false) {
            return 'unsupported_embed';
        }
        return false;
    }

    /**
     * Check if URL is a YouTube embed URL.
     *
     * @param string $url
     * @return bool
     */
    public static function is_youtube_embed_url($url) {
        return strpos($url, 'youtube.com/embed/') !== false || strpos($url, 'youtu.be/') !== false;
    }

    /**
     * Check if URL is a Vimeo embed URL.
     *
     * @param string $url
     * @return bool
     */
    public static function is_vimeo_embed_url($url) {
        return strpos($url, 'player.vimeo.com/video/') !== false;
    }

    /**
     * Infer a media type from the URL path extension.
     *
     * Does not fetch remote headers.
     *
     * @param string $url The URL to extract the media type from.
     * @return string The media type, or video/mp4 when the extension is unknown.
     */
    public static function extract_media_type($url) {
        $path = (string) parse_url((string) $url, PHP_URL_PATH);
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        $types = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'flv' => 'video/x-flv',
            'wmv' => 'video/x-ms-wmv',
            'ogg' => 'video/ogg',
            'm3u8' => 'application/x-mpegURL',
            'mpd' => 'application/dash+xml',
        ];

        return $types[$extension] ?? 'video/mp4';
    }

    /**
     * Check if the URL is a MPEG-DASH manifest.
     *
     * @param string $url The URL to check.
     * @return bool True if the URL is a MPEG-DASH manifest, false otherwise.
     */
    public static function is_mpegdash($url) {
        // Extract the file extension from the URL.
        $fileextension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

        // Check if the file extension is "mpd" (MPEG-DASH manifest).
        return strtolower($fileextension) === 'mpd';
    }

    /**
     * Check if the URL is a HLS manifest.
     *
     * @param string $url The URL to check.
     * @return bool True if the URL is a HLS manifest, false otherwise.
     */
    public static function is_hls($url) {
        // Extract the file extension from the URL.
        $fileextension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

        // Check if the file extension is "m3u8".
        return strtolower($fileextension) === 'm3u8';
    }

    /**
     * Empty geo result (same keys as {@see self::geoinfo()}).
     *
     * @return \stdClass
     */
    private static function geoinfo_empty_result(): \stdClass {
        return (object) [
            'city' => '',
            'region' => '',
            'region_code' => '',
            'region_name' => '',
            'country_code' => '',
            'country_name' => '',
            'continent_code' => '',
            'continent_name' => '',
            'timezone' => '',
            'currency' => '',
        ];
    }

    /**
     * Get geo information from an IP using the site's MaxMind GeoIP2 database only.
     *
     * Requires $CFG->geoip2file (same as core IP lookup). If not configured or lookup fails,
     * returns an object with the same keys as before but empty string values. Currency is
     * always empty (not provided by GeoIP2 City).
     * not using iplookup_find_location() because it does not return country code and geoplugin.net does not work.
     * @param string $ip The IP address to look up.
     * @return \stdClass Geo fields: city, region, region_code, region_name, country_code, country_name,
     *                   continent_code, continent_name, timezone, currency.
     */
    public static function geoinfo($ip): \stdClass {
        global $CFG;

        $empty = self::geoinfo_empty_result();
        $ip = trim((string) $ip);
        if ($ip === '') {
            return $empty;
        }

        if (empty($CFG->geoip2file) || !is_readable($CFG->geoip2file)) {
            return $empty;
        }

        try {
            $reader = new \GeoIp2\Database\Reader($CFG->geoip2file);
            $record = $reader->city($ip);
            $reader->close();
        } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
            return $empty;
        } catch (\InvalidArgumentException $e) {
            return $empty;
        } catch (\MaxMind\Db\Reader\InvalidDatabaseException $e) {
            debugging('mod_videolesson: geoinfo invalid GeoIP2 database: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $empty;
        } catch (\Exception $e) {
            debugging('mod_videolesson: geoinfo lookup failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $empty;
        }

        $city = '';
        if ($record->city->name !== null && $record->city->name !== '') {
            $city = \core_text::convert($record->city->name, 'iso-8859-1', 'utf-8');
        }

        $countrycode = $record->country->isoCode ?? '';
        $countryname = '';
        if ($countrycode !== '') {
            $countries = get_string_manager()->get_list_of_countries(true);
            if (isset($countries[$countrycode])) {
                $countryname = $countries[$countrycode];
            } else if ($record->country->name !== null && $record->country->name !== '') {
                $countryname = $record->country->name;
            }
        }

        $sub = $record->mostSpecificSubdivision;
        $regionname = $sub->name ?? '';
        $regionshort = $sub->isoCode ?? '';
        $regioncode = '';
        if ($countrycode !== '' && $regionshort !== '') {
            $regioncode = $countrycode . '-' . $regionshort;
        } else {
            $regioncode = $regionshort;
        }

        return (object) [
            'city' => $city,
            'region' => $regionname,
            'region_code' => $regioncode,
            'region_name' => $regionname,
            'country_code' => $countrycode,
            'country_name' => $countryname,
            'continent_code' => (string) ($record->continent->code ?? ''),
            'continent_name' => (string) ($record->continent->name ?? ''),
            'timezone' => (string) ($record->location->timeZone ?? ''),
            'currency' => '',
        ];
    }

    /**
     * Calculate the percentage of a number.
     *
     * @param int $number The number to calculate the percentage of.
     * @param int $total The total to calculate the percentage from.
     * @return float The percentage.
     */
    public static function calculate_percentage($number, $total) {
        if ($total == 0) {
            // Avoid division by zero.
            return 0;
        }

        $percentage = ($number * 100) / $total;

        return round($percentage, 2);
    }

    /**
     * Get normalized hash for sourcedata (for YouTube/Vimeo videos, uses provider:videoid)
     * This ensures consistent hashing across different video source types.
     *
     * @param string $source The video source type (MOD_VIDEOLESSON_SRC_GALLERY, MOD_VIDEOLESSON_SRC_EXTERNAL)
     * @param string $sourcedata The sourcedata value (contenthash, normalized format, or URL)
     * @return string The normalized/hashed sourcedata
     */
    public static function normalize_sourcedata_hash($source, $sourcedata) {
        if ($source == MOD_VIDEOLESSON_SRC_GALLERY) {
            return $sourcedata;
        }

        if ($source == MOD_VIDEOLESSON_SRC_BBB) {
            return $sourcedata;
        }

        // For MOD_VIDEOLESSON_SRC_EXTERNAL, check if sourcedata is in normalized format (YouTube/Vimeo).
        if ($source == MOD_VIDEOLESSON_SRC_EXTERNAL) {
            // Parse sourcedata as normalized format: "youtube:VIDEO_ID" or "vimeo:VIDEO_ID".
            if (preg_match('/^(youtube|vimeo):([a-zA-Z0-9_-]+)$/i', $sourcedata, $matches)) {
                $externaltype = strtolower($matches[1]);
                $externalvideoid = $matches[2];
                $normalized = $externaltype . ':' . $externalvideoid;
                return md5($normalized);
            }
        }

        // For external URLs and other sources, use MD5 of sourcedata.
        return md5($sourcedata);
    }

    /** Maximum events retained per telemetry collection. */
    private const WATCHTIME_MAX_EVENTS = 500;

    /** Maximum length for short telemetry strings. */
    private const WATCHTIME_MAX_STRING = 255;

    /**
     * Rebuild client watchtime JSON into an allowlisted telemetry structure.
     *
     * Drops unknown keys and coerces scalars so stored session data cannot carry
     * arbitrary markup into report rendering. Source and sourcedata must be supplied
     * from the activity record, not the client.
     *
     * @param array $input Decoded client payload.
     * @param int $userid Authenticated user id.
     * @param int $cmid Course module id.
     * @param string $source Activity source type.
     * @param string $sourcedata Activity sourcedata (may still be raw URL before normalize).
     * @return array Sanitized telemetry for persistence and progress updates.
     */
    public static function sanitize_watchtime_telemetry(
        array $input,
        int $userid,
        int $cmid,
        string $source,
        string $sourcedata
    ): array {
        return [
            'userid' => $userid,
            'cm' => $cmid,
            'source' => $source,
            'sourcedata' => $sourcedata,
            'session' => self::sanitize_watchtime_string($input['session'] ?? '', 40),
            'timestamp' => self::sanitize_watchtime_string($input['timestamp'] ?? '', 40),
            'duration' => self::sanitize_watchtime_float($input['duration'] ?? 0),
            'watchduration' => self::sanitize_watchtime_float($input['watchduration'] ?? 0),
            'totalprogess' => self::sanitize_watchtime_float($input['totalprogess'] ?? 0),
            'leftOff' => self::sanitize_watchtime_float($input['leftOff'] ?? 0),
            'max' => self::sanitize_watchtime_float($input['max'] ?? 0),
            'notified' => !empty($input['notified']),
            'student' => !empty($input['student']),
            'city' => self::sanitize_watchtime_string($input['city'] ?? '', 120),
            'country' => self::sanitize_watchtime_string($input['country'] ?? '', 40),
            'ip' => self::sanitize_watchtime_string($input['ip'] ?? '', 45),
            'platform' => self::sanitize_watchtime_string($input['platform'] ?? '', 40),
            'browser' => self::sanitize_watchtime_string($input['browser'] ?? '', 40),
            'os' => self::sanitize_watchtime_string($input['os'] ?? '', 40),
            'ranges' => self::sanitize_watchtime_ranges($input['ranges'] ?? []),
            'playbackEvents' => self::sanitize_watchtime_event_list(
                $input['playbackEvents'] ?? [],
                ['event_type' => 'string', 'timestamp' => 'string', 'position' => 'float']
            ),
            'seekEvents' => self::sanitize_watchtime_event_list(
                $input['seekEvents'] ?? [],
                [
                    'timestamp' => 'string',
                    'start' => 'float',
                    'position' => 'float',
                    'progress' => 'float',
                ]
            ),
            'visibility' => self::sanitize_watchtime_event_list(
                $input['visibility'] ?? [],
                ['timestamp' => 'string', 'position' => 'float', 'active' => 'bool']
            ),
            'volumeChanges' => self::sanitize_watchtime_event_list(
                $input['volumeChanges'] ?? [],
                ['timestamp' => 'string', 'position' => 'float', 'volume_level' => 'float']
            ),
            'qualityChanges' => self::sanitize_watchtime_event_list(
                $input['qualityChanges'] ?? [],
                ['timestamp' => 'string', 'position' => 'float', 'quality' => 'string']
            ),
            'errors' => self::sanitize_watchtime_event_list(
                $input['errors'] ?? [],
                [
                    'timestamp' => 'string',
                    'error_type' => 'string',
                    'error_description' => 'string',
                ]
            ),
        ];
    }

    /**
     * Sanitize a bounded plain string for watchtime storage.
     *
     * @param mixed $value Raw value.
     * @param int $maxlen Maximum length.
     * @return string
     */
    private static function sanitize_watchtime_string($value, int $maxlen = self::WATCHTIME_MAX_STRING): string {
        if (is_bool($value) || is_array($value) || is_object($value)) {
            return '';
        }
        $value = trim(strip_tags((string) $value));
        if (\core_text::strlen($value) > $maxlen) {
            $value = \core_text::substr($value, 0, $maxlen);
        }
        return $value;
    }

    /**
     * Sanitize a finite numeric value for watchtime storage.
     *
     * @param mixed $value Raw value.
     * @return float
     */
    private static function sanitize_watchtime_float($value): float {
        if (!is_numeric($value)) {
            return 0.0;
        }
        $number = (float) $value;
        if (!is_finite($number)) {
            return 0.0;
        }
        return $number;
    }

    /**
     * Sanitize playback ranges: nested arrays of [start, end] floats.
     *
     * @param mixed $ranges Raw ranges payload.
     * @return array
     */
    private static function sanitize_watchtime_ranges($ranges): array {
        if (!is_array($ranges)) {
            return [];
        }

        $safe = [];
        $outercount = 0;
        foreach ($ranges as $group) {
            if ($outercount >= self::WATCHTIME_MAX_EVENTS || !is_array($group)) {
                break;
            }
            $safegroup = [];
            $innercount = 0;
            foreach ($group as $range) {
                if ($innercount >= self::WATCHTIME_MAX_EVENTS || !is_array($range) || count($range) < 2) {
                    break;
                }
                $safegroup[] = [
                    self::sanitize_watchtime_float($range[0] ?? 0),
                    self::sanitize_watchtime_float($range[1] ?? 0),
                ];
                $innercount++;
            }
            $safe[] = $safegroup;
            $outercount++;
        }

        return $safe;
    }

    /**
     * Clamp nested playback ranges to [0, duration].
     *
     * @param array $ranges Nested ranges from sanitize_watchtime_ranges().
     * @param float $duration Authoritative media duration in seconds.
     * @return array
     */
    public static function clamp_watchtime_ranges(array $ranges, float $duration): array {
        if ($duration <= 0) {
            return [];
        }

        $safe = [];
        foreach ($ranges as $group) {
            if (!is_array($group)) {
                continue;
            }
            $safegroup = [];
            foreach ($group as $range) {
                if (!is_array($range) || count($range) < 2) {
                    continue;
                }
                $start = max(0.0, min((float) $range[0], $duration));
                $end = max(0.0, min((float) $range[1], $duration));
                if ($end < $start) {
                    $tmp = $start;
                    $start = $end;
                    $end = $tmp;
                }
                $safegroup[] = [$start, $end];
            }
            if ($safegroup) {
                $safe[] = $safegroup;
            }
        }

        return $safe;
    }

    /**
     * Derive completion progress from furthest range endpoint (same model as get_watch_data).
     *
     * Does not use client totalprogess. Progress is furthest_point / duration * 100, clamped to 0–100.
     *
     * @param array $ranges Nested [start, end] ranges (preferably already clamped).
     * @param float $duration Media duration in seconds.
     * @return float Progress percentage in [0, 100].
     */
    public static function derive_progress_from_ranges(array $ranges, float $duration): float {
        if ($duration <= 0) {
            return 0.0;
        }

        $furthest = 0.0;
        foreach ($ranges as $group) {
            if (!is_array($group)) {
                continue;
            }
            foreach ($group as $range) {
                if (!is_array($range) || count($range) < 2) {
                    continue;
                }
                $furthest = max($furthest, (float) $range[0], (float) $range[1]);
            }
        }

        $furthest = max(0.0, min($furthest, $duration));
        $progress = self::calculate_percentage($furthest, $duration);
        return max(0.0, min(100.0, (float) $progress));
    }

    /**
     * Sanitize a list of event objects using an allowlisted field schema.
     *
     * @param mixed $events Raw event list.
     * @param array $schema Map of field name => string|float|bool.
     * @return array
     */
    private static function sanitize_watchtime_event_list($events, array $schema): array {
        if (!is_array($events)) {
            return [];
        }

        $safe = [];
        $count = 0;
        foreach ($events as $event) {
            if ($count >= self::WATCHTIME_MAX_EVENTS) {
                break;
            }
            if (!is_array($event)) {
                continue;
            }
            $row = [];
            foreach ($schema as $field => $type) {
                $raw = $event[$field] ?? null;
                if ($type === 'float') {
                    $row[$field] = self::sanitize_watchtime_float($raw);
                } else if ($type === 'bool') {
                    $row[$field] = !empty($raw);
                } else {
                    $row[$field] = self::sanitize_watchtime_string($raw, self::WATCHTIME_MAX_STRING);
                }
            }
            $safe[] = $row;
            $count++;
        }

        return $safe;
    }

    /**
     * Normalize sourcedata for storage in videolesson_usage and videolesson_cm_progress tables.
     * For YouTube/Vimeo videos, returns normalized format directly. For external URLs, returns hash.
     * For gallery videos, returns contenthash as-is.
     *
     * @param int $source The video source type (MOD_VIDEOLESSON_SRC_GALLERY, MOD_VIDEOLESSON_SRC_EXTERNAL)
     * @param string $sourcedata The sourcedata value (contenthash, normalized format, or URL)
     * @return string The normalized sourcedata for usage tables
     */
    public static function normalize_sourcedata_for_usage($source, $sourcedata) {
        if ($source == MOD_VIDEOLESSON_SRC_GALLERY) {
            return $sourcedata; // Contenthash as-is.
        } else if ($source == MOD_VIDEOLESSON_SRC_EXTERNAL) {
            // Check if sourcedata is in normalized format (e.g., "youtube:VIDEO_ID").
            if (preg_match('/^(youtube|vimeo):([a-zA-Z0-9_-]+)$/i', $sourcedata, $matches)) {
                return $sourcedata; // Return normalized format directly.
            }
            // For direct video URLs or unsupported embeds, hash the URL.
            return md5($sourcedata);
        } else if ($source == MOD_VIDEOLESSON_SRC_BBB) {
            // BBB usage key is the normalized sourcedata (bbb:{recordingid}).
            return $sourcedata;
        }

        // Fallback for any other source type.
        return md5($sourcedata);
    }

    /**
     * Get the video duration from the sourcedata.
     *
     * @param int $source The video source type (MOD_VIDEOLESSON_SRC_GALLERY, MOD_VIDEOLESSON_SRC_EXTERNAL).
     * @param string $sourcedata The sourcedata value (contenthash, normalized format, or URL).
     * @return int|false The video duration or false if not found.
     */
    public static function get_video_duration($source, $sourcedata) {
        global $DB;

        if ($source == MOD_VIDEOLESSON_SRC_BBB) {
            $seconds = \mod_videolesson\bbb_recordings::get_duration_seconds_for_sourcedata($sourcedata);
            return $seconds > 0 ? $seconds : false;
        }

        if ($source == MOD_VIDEOLESSON_SRC_GALLERY) {
            $record = $DB->get_record(
                'videolesson_data',
                [
                    'contenthash' => $sourcedata,
                ]
            );
        } else {
            // For external sources, normalize the hash.
            $sourcehash = null;

            // For MOD_VIDEOLESSON_SRC_EXTERNAL, check if sourcedata is in normalized format (YouTube/Vimeo).
            if ($source == MOD_VIDEOLESSON_SRC_EXTERNAL) {
                // Parse sourcedata as normalized format: "youtube:VIDEO_ID" or "vimeo:VIDEO_ID".
                if (preg_match('/^(youtube|vimeo):([a-zA-Z0-9_-]+)$/i', $sourcedata, $matches)) {
                    $externaltype = strtolower($matches[1]);
                    $externalvideoid = $matches[2];
                    $normalized = $externaltype . ':' . $externalvideoid;
                    $sourcehash = md5($normalized);
                } else {
                    // For direct video URLs or unsupported embeds, use MD5 hash.
                    $sourcehash = md5($sourcedata);
                }
            }

            // Fallback: hash the sourcedata directly (for external URLs or if normalization failed).
            if (!$sourcehash) {
                $sourcehash = md5($sourcedata);
            }

            $record = $DB->get_record(
                'videolesson_data_external',
                [
                    'sourcehash' => $sourcehash,
                ]
            );
        }

        if (!$record) {
            return false;
        }

        return round($record->duration);
    }

    /**
     * Format the watched time.
     *
     * @param int $durationinseconds The duration in seconds.
     * @param bool $includehours Whether to include hours.
     * @return string The formatted watched time.
     */
    public static function format_watched_time($durationinseconds, $includehours = false) {

        if ($includehours) {
            if ($durationinseconds < 60) {
                return $durationinseconds . " seconds";
            } else if ($durationinseconds < 3600) {
                $minutes = floor($durationinseconds / 60);
                return $minutes . " minute" . ($minutes > 1 ? "s" : "");
            } else {
                $hours = floor($durationinseconds / 3600);
                return $hours . " hour" . ($hours > 1 ? "s" : "");
            }
        }

        if ($durationinseconds < 60) {
            return $durationinseconds . " seconds";
        } else {
            $minutes = floor($durationinseconds / 60);
            return $minutes . " minute" . ($minutes > 1 ? "s" : "");
        }
    }

    /**
     * Convert a date string to a timestamp.
     *
     * @param string $datestring The date string to convert.
     * @return int The timestamp.
     */
    public static function convert_to_timestamp($datestring) {
        // Create a DateTime object from the string.
        $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $datestring);

        // Convert DateTime object to a Unix timestamp.
        $timestamp = $datetime->getTimestamp();

        // Output the timestamp.
        return $timestamp;
    }

    /**
     * Format the bytes.
     *
     * @param int $bytes The bytes to format.
     * @param int $precision The precision to format the bytes to.
     * @return string The formatted bytes.
     */
    public static function formatbytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Check if the string is a MD5 hash.
     *
     * @param string $string The string to check.
     * @return bool True if the string is a MD5 hash, false otherwise.
     */
    public static function is_md5($string) {
        return (bool) preg_match('/^[0-9a-f]{32}$/', $string);
    }

    /**
     * Sends a notification to all site administrators.
     *
     * @param string $subject The subject of the message.
     * @param string $message The content of the message.
     */
    public static function send_notification_to_admins($subject, $message) {

        // Get all site administrators.
        $admins = get_admins();
        // Iterate over each admin and send a message.
        foreach ($admins as $admin) {
            $eventdata = new \core\message\message();
            $eventdata->component         = 'mod_videolesson';
            $eventdata->name              = 'notification';
            $eventdata->userfrom          = \core_user::get_noreply_user();
            $eventdata->userto            = $admin;
            $eventdata->subject           = $subject;
            $eventdata->fullmessage       = $message;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml   = ''; // HTML version if needed.
            $eventdata->smallmessage      = $subject;

            // Send the message.
            message_send($eventdata);
        }
    }

    /**
     * Check if the add conversion task exists.
     *
     * @param string $pathhash The path hash.
     * @return bool True if the add conversion task exists, false otherwise.
     */
    public static function adhoc_addconv_check($pathhash) {
        global $DB;

        $data = [
            'pathhash' => $pathhash,
        ];

        $taskname = '\mod_videolesson\task\add_conversion';
        $customdata = json_encode($data);

        $sql = "SELECT COUNT(1)
                FROM {task_adhoc}
                WHERE classname = :classname";

        $params = ['classname' => $taskname];
        if ($customdata) {
            $sql .= " AND customdata = :customdata";
            $params['customdata'] = $customdata;
        }

        $taskexists = $DB->count_records_sql($sql, $params);
        return $taskexists;
    }

    /**
     * Get the plugin version.
     *
     * @return string The plugin version.
     */
    public static function get_plugin_version() {
        global $CFG;
        // Get installed plugin version.
        // Use the installed version from the database.
        $pluginman = \core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugin_info('mod_videolesson');
        // Get the versiondb (installed version) for the installed version.
        $pluginversion = null;
        if ($plugininfo && !empty($plugininfo->versiondb)) {
            $pluginversion = (string)$plugininfo->versiondb;
        }

        // Fallback: read from version.php if plugin info doesn't have version.
        if (empty($pluginversion)) {
            $versionfile = $CFG->dirroot . '/mod/videolesson/version.php';
            if (file_exists($versionfile)) {
                $plugin = new \stdClass();
                $plugin->version = null;
                include($versionfile);
                if (!empty($plugin->version)) {
                    $pluginversion = (string)$plugin->version;
                }
            }
        }

        // Final fallback.
        if (empty($pluginversion)) {
            $pluginversion = '2025121104';
        }

        return $pluginversion;
    }

    /**
     * Validate self-managed AWS credentials and S3 bucket access (same checks as setup wizard step 2).
     *
     * @param \stdClass $testconfig Object with api_key, api_secret, api_region, s3_input_bucket, s3_output_bucket.
     * @return string Empty string if OK; otherwise a translated error message for display.
     */
    public static function validate_self_managed_aws(\stdClass $testconfig): string {
        if (
            empty($testconfig->api_key) || empty($testconfig->api_secret) ||
            empty($testconfig->s3_input_bucket) || empty($testconfig->s3_output_bucket)
        ) {
            return get_string('error:aws:required:fields', 'mod_videolesson');
        }
        if (empty($testconfig->api_region)) {
            $testconfig->api_region = 'ap-southeast-2';
        }
        try {
            $awss3 = new aws_s3($testconfig);
            $awss3->create_client();

            $inputresult = $awss3->is_bucket_accessible($testconfig->s3_input_bucket);
            if (!$inputresult->success) {
                $msg = get_string('setup:step2:self:validation:input_bucket_failed', 'mod_videolesson');
                $msg .= ' ' . $inputresult->message;
                $msg .= ' ' . get_string('setup:step2:self:validation:check_info', 'mod_videolesson');
                return $msg;
            }

            $outputresult = $awss3->is_bucket_accessible($testconfig->s3_output_bucket);
            if (!$outputresult->success) {
                $msg = get_string('setup:step2:self:validation:output_bucket_failed', 'mod_videolesson');
                $msg .= ' ' . $outputresult->message;
                $msg .= ' ' . get_string('setup:step2:self:validation:check_info', 'mod_videolesson');
                return $msg;
            }
        } catch (\Exception $e) {
            $msg = get_string('error:aws:connection:failed', 'mod_videolesson');
            $msg .= ': ' . $e->getMessage();
            $msg .= ' ' . get_string('setup:step2:self:validation:check_info', 'mod_videolesson');
            return $msg;
        }
        return '';
    }

    /**
     * Executes a cURL POST request to the hosted API.
     *
     * @param string $apiurl The API endpoint URL
     * @param array $postdata POST data to send
     * @param array $options Optional settings:
     *   - 'check_http_code' (bool): Check HTTP status code (default: true)
     *   - 'throw_on_error' (bool): Throw exception on error (default: true)
     *   - 'return_null_on_error' (bool): Return null on error instead of throwing (default: false)
     *   - 'timeout' (int): Request timeout in seconds (default: 30)
     * @return array|null Decoded JSON response or null on error (if return_null_on_error is true)
     * @throws \Exception If request fails and throw_on_error is true
     */
    public static function execute_hosted_api_request(string $apiurl, array $postdata, array $options = []): ?array {
        $checkhttpcode = $options['check_http_code'] ?? true;
        $throwonerror = $options['throw_on_error'] ?? true;
        $returnnullonerror = $options['return_null_on_error'] ?? false;
        $timeout = $options['timeout'] ?? 30;

        $curl = new \curl();
        $curl->setopt([
            'connecttimeout' => $timeout,
            'timeout' => $timeout,
        ]);
        $response = $curl->post($apiurl, $postdata);
        $info = $curl->get_info();
        $httpcode = (int) ($info['http_code'] ?? 0);
        $curlerrno = $curl->get_errno();
        $curlerror = $curlerrno ? ($curl->error ?: 'cURL error') : null;

        // Handle cURL errors.
        if ($curlerrno) {
            $error = 'cURL Error: ' . $curlerror;
            if ($returnnullonerror) {
                debugging('mod_videolesson: ' . $error, DEBUG_NORMAL);
                return null;
            }
            if ($throwonerror) {
                throw new \Exception($error);
            }
            return null;
        }

        // Check if response is empty.
        if (empty($response)) {
            $error = 'API returned empty response';
            if ($returnnullonerror) {
                debugging('mod_videolesson: ' . $error, DEBUG_NORMAL);
                return null;
            }
            if ($throwonerror) {
                throw new \Exception($error);
            }
            return null;
        }

        // Decode JSON response first (before checking HTTP status).
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $responsepreview = substr($response, 0, 200);
            $jsonerror = json_last_error_msg();
            $error = "Failed to decode JSON response (JSON error: {$jsonerror}). Response preview: " . $responsepreview;
            if ($returnnullonerror) {
                debugging('mod_videolesson: ' . $error, DEBUG_NORMAL);
                return null;
            }
            if ($throwonerror) {
                throw new \Exception($error);
            }
            return null;
        }

        // Check HTTP status code if requested.
        if ($checkhttpcode && ($httpcode < 200 || $httpcode >= 300)) {
            // Non-200 response - extract error message if available.
            if (isset($data['code']) && isset($data['message'])) {
                $error = "API Error: {$data['message']} (code: {$data['code']})";
            } else {
                $responsepreview = substr($response, 0, 200);
                $error = "API returned HTTP {$httpcode}. Response: " . $responsepreview;
            }
            if ($returnnullonerror) {
                debugging('mod_videolesson: ' . $error, DEBUG_NORMAL);
                return null;
            }
            if ($throwonerror) {
                throw new \Exception($error);
            }
            return null;
        }

        // HTTP 200 - return decoded JSON directly (no error check needed).
        // HTTP 200 responses should never have 'code' and 'message' error fields according to API docs.
        return $data;
    }
}
