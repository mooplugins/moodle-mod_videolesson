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
 * Unit tests for mod_videolesson utility functions.
 *
 * @package    mod_videolesson
 * @category   test
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/videolesson/locallib.php');
require_once($CFG->dirroot . '/mod/videolesson/classes/util.php');

/**
 * Unit tests for utility functions.
 *
 * @package    mod_videolesson
 * @category   test
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util_test extends \basic_testcase {

    /**
     * Test durationformat function with various inputs.
     */
    public function test_durationformat() {
        // Test zero seconds
        $this->assertEquals('00:00:00', util::durationformat(0));

        // Test seconds only
        $this->assertEquals('00:00:30', util::durationformat(30));

        // Test minutes
        $this->assertEquals('00:05:00', util::durationformat(300));

        // Test hours
        $this->assertEquals('01:00:00', util::durationformat(3600));

        // Test complex duration
        $this->assertEquals('01:23:45', util::durationformat(5025));

        // Test with decimal input (should round)
        $this->assertEquals('00:00:01', util::durationformat(0.7));
        $this->assertEquals('00:00:02', util::durationformat(1.5));

        // Test custom format
        $this->assertEquals('01h 23m 45s', util::durationformat(5025, '%02dh %02dm %02ds'));
    }

    /**
     * Test is_youtube_url function with valid YouTube URLs.
     */
    public function test_is_youtube_url_valid() {
        // Standard YouTube URLs
        $this->assertTrue(util::is_youtube_url('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertTrue(util::is_youtube_url('http://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertTrue(util::is_youtube_url('https://youtube.com/watch?v=dQw4w9WgXcQ'));

        // Short URLs
        $this->assertTrue(util::is_youtube_url('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertTrue(util::is_youtube_url('http://youtu.be/dQw4w9WgXcQ'));

        // Embed URLs
        $this->assertTrue(util::is_youtube_url('https://www.youtube.com/embed/dQw4w9WgXcQ'));
        $this->assertTrue(util::is_youtube_url('https://www.youtube.com/v/dQw4w9WgXcQ'));

        // URLs without protocol
        $this->assertTrue(util::is_youtube_url('www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertTrue(util::is_youtube_url('youtu.be/dQw4w9WgXcQ'));
    }

    /**
     * Test is_youtube_url function with invalid URLs.
     */
    public function test_is_youtube_url_invalid() {
        $this->assertFalse(util::is_youtube_url('https://vimeo.com/123456789'));
        $this->assertFalse(util::is_youtube_url('https://example.com/video.mp4'));
        $this->assertFalse(util::is_youtube_url('not a url'));
        $this->assertFalse(util::is_youtube_url(''));
        $this->assertFalse(util::is_youtube_url('https://youtube.com/invalid'));
    }

    /**
     * Test is_vimeo_url function with valid Vimeo URLs.
     */
    public function test_is_vimeo_url_valid() {
        $this->assertTrue(util::is_vimeo_url('https://vimeo.com/123456789'));
        $this->assertTrue(util::is_vimeo_url('http://vimeo.com/123456789'));
        $this->assertTrue(util::is_vimeo_url('https://www.vimeo.com/123456789'));
        $this->assertTrue(util::is_vimeo_url('vimeo.com/123456789'));
        $this->assertTrue(util::is_vimeo_url('https://vimeo.com/483782679'));
    }

    /**
     * Test is_vimeo_url function with invalid URLs.
     */
    public function test_is_vimeo_url_invalid() {
        $this->assertFalse(util::is_vimeo_url('https://youtube.com/watch?v=abc123'));
        $this->assertFalse(util::is_vimeo_url('https://example.com/video.mp4'));
        $this->assertFalse(util::is_vimeo_url('not a url'));
        $this->assertFalse(util::is_vimeo_url(''));
        $this->assertFalse(util::is_vimeo_url('https://vimeo.com/invalid'));
    }

    /**
     * Test extract_youtube_video_id function.
     */
    public function test_extract_youtube_video_id() {
        $this->assertEquals('dQw4w9WgXcQ', util::extract_youtube_video_id('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertEquals('dQw4w9WgXcQ', util::extract_youtube_video_id('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertEquals('dQw4w9WgXcQ', util::extract_youtube_video_id('https://www.youtube.com/embed/dQw4w9WgXcQ'));
        $this->assertEquals('dQw4w9WgXcQ', util::extract_youtube_video_id('https://www.youtube.com/v/dQw4w9WgXcQ'));
        $this->assertEquals('dQw4w9WgXcQ', util::extract_youtube_video_id('https://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=share'));

        // Invalid URLs should return false
        $this->assertFalse(util::extract_youtube_video_id('https://vimeo.com/123456789'));
        $this->assertFalse(util::extract_youtube_video_id('not a url'));
        $this->assertFalse(util::extract_youtube_video_id(''));
    }

    /**
     * Test extract_vimeo_video_id function.
     */
    public function test_extract_vimeo_video_id() {
        $this->assertEquals('123456789', util::extract_vimeo_video_id('https://vimeo.com/123456789'));
        $this->assertEquals('483782679', util::extract_vimeo_video_id('https://vimeo.com/483782679'));
        $this->assertEquals('123456789', util::extract_vimeo_video_id('https://player.vimeo.com/video/123456789'));

        // Invalid URLs should return false
        $this->assertFalse(util::extract_vimeo_video_id('https://youtube.com/watch?v=abc123'));
        $this->assertFalse(util::extract_vimeo_video_id('not a url'));
        $this->assertFalse(util::extract_vimeo_video_id(''));
    }

    /**
     * Test is_embed_code function.
     */
    public function test_is_embed_code() {
        // Test iframe embed codes
        $this->assertTrue(util::is_embed_code('<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>'));
        $this->assertTrue(util::is_embed_code('<iframe src="https://player.vimeo.com/video/123456789"></iframe>'));

        // Test YouTube URLs (should be detected as embed codes)
        $this->assertTrue(util::is_embed_code('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertTrue(util::is_embed_code('https://youtu.be/dQw4w9WgXcQ'));

        // Test Vimeo URLs
        $this->assertTrue(util::is_embed_code('https://vimeo.com/123456789'));

        // Invalid embed codes
        $this->assertFalse(util::is_embed_code('not an embed code'));
        $this->assertFalse(util::is_embed_code(''));
        $this->assertFalse(util::is_embed_code('https://example.com/video.mp4'));
    }

    /**
     * Test extract_url_from_embed_code function.
     */
    public function test_extract_url_from_embed_code() {
        // Test direct URLs (should return as-is)
        $this->assertEquals('https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            util::extract_url_from_embed_code('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertEquals('https://vimeo.com/123456789',
            util::extract_url_from_embed_code('https://vimeo.com/123456789'));

        // Test iframe embed codes
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ',
            util::extract_url_from_embed_code('<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>'));
        $this->assertEquals('https://player.vimeo.com/video/123456789',
            util::extract_url_from_embed_code('<iframe src="https://player.vimeo.com/video/123456789"></iframe>'));

        // Invalid embed codes should return false
        $this->assertFalse(util::extract_url_from_embed_code('not an embed code'));
        $this->assertFalse(util::extract_url_from_embed_code(''));
    }

    /**
     * Test detect_external_source_type function.
     */
    public function test_detect_external_source_type() {
        // Test YouTube detection
        $this->assertEquals('youtube', util::detect_external_source_type('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertEquals('youtube', util::detect_external_source_type('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertEquals('youtube', util::detect_external_source_type('<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>'));

        // Test Vimeo detection
        $this->assertEquals('vimeo', util::detect_external_source_type('https://vimeo.com/123456789'));
        $this->assertEquals('vimeo', util::detect_external_source_type('<iframe src="https://player.vimeo.com/video/123456789"></iframe>'));

        // Test direct video URLs
        $this->assertEquals('direct_video', util::detect_external_source_type('https://example.com/video.mp4'));

        // Test unsupported embeds
        $this->assertEquals('unsupported_embed', util::detect_external_source_type('<iframe src="https://player.twitch.tv/?video=123"></iframe>'));

        // Invalid inputs should return false
        $this->assertFalse(util::detect_external_source_type('not a valid source'));
        $this->assertFalse(util::detect_external_source_type(''));
    }

    /**
     * Test normalize_sourcedata_hash function.
     */
    public function test_normalize_sourcedata_hash() {
        // Test gallery source (should return as-is)
        $contenthash = 'abc123def456';
        $this->assertEquals($contenthash, util::normalize_sourcedata_hash(VIDEO_SRC_GALLERY, $contenthash));

        // Test YouTube normalized format
        $youtubeid = 'dQw4w9WgXcQ';
        $youtubesourcedata = 'youtube:' . $youtubeid;
        $expectedhash = md5($youtubesourcedata);
        $this->assertEquals($expectedhash, util::normalize_sourcedata_hash(VIDEO_SRC_EXTERNAL, $youtubesourcedata));

        // Test Vimeo normalized format
        $vimeoid = '123456789';
        $vimeosourcedata = 'vimeo:' . $vimeoid;
        $expectedhash = md5($vimeosourcedata);
        $this->assertEquals($expectedhash, util::normalize_sourcedata_hash(VIDEO_SRC_EXTERNAL, $vimeosourcedata));

        // Test external URL (should hash)
        $externalurl = 'https://example.com/video.mp4';
        $expectedhash = md5($externalurl);
        $this->assertEquals($expectedhash, util::normalize_sourcedata_hash(VIDEO_SRC_EXTERNAL, $externalurl));
    }

    /**
     * Test normalize_sourcedata_for_usage function.
     */
    public function test_normalize_sourcedata_for_usage() {
        // Test gallery source (should return as-is)
        $contenthash = 'abc123def456';
        $this->assertEquals($contenthash, util::normalize_sourcedata_for_usage(VIDEO_SRC_GALLERY, $contenthash));

        // Test YouTube normalized format (should return as-is)
        $youtubeid = 'dQw4w9WgXcQ';
        $youtubesourcedata = 'youtube:' . $youtubeid;
        $this->assertEquals($youtubesourcedata, util::normalize_sourcedata_for_usage(VIDEO_SRC_EXTERNAL, $youtubesourcedata));

        // Test Vimeo normalized format (should return as-is)
        $vimeoid = '123456789';
        $vimeosourcedata = 'vimeo:' . $vimeoid;
        $this->assertEquals($vimeosourcedata, util::normalize_sourcedata_for_usage(VIDEO_SRC_EXTERNAL, $vimeosourcedata));

        // Test external URL (should hash)
        $externalurl = 'https://example.com/video.mp4';
        $expectedhash = md5($externalurl);
        $this->assertEquals($expectedhash, util::normalize_sourcedata_for_usage(VIDEO_SRC_EXTERNAL, $externalurl));
    }

    /**
     * Test is_youtube_embed_url function.
     */
    public function test_is_youtube_embed_url() {
        $this->assertTrue(util::is_youtube_embed_url('https://www.youtube.com/embed/dQw4w9WgXcQ'));
        $this->assertTrue(util::is_youtube_embed_url('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertFalse(util::is_youtube_embed_url('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertFalse(util::is_youtube_embed_url('https://vimeo.com/123456789'));
    }

    /**
     * Test is_vimeo_embed_url function.
     */
    public function test_is_vimeo_embed_url() {
        $this->assertTrue(util::is_vimeo_embed_url('https://player.vimeo.com/video/123456789'));
        $this->assertFalse(util::is_vimeo_embed_url('https://vimeo.com/123456789'));
        $this->assertFalse(util::is_vimeo_embed_url('https://youtube.com/watch?v=abc123'));
    }

    /**
     * Test get_youtube_embed_url function.
     */
    public function test_get_youtube_embed_url() {
        $videoid = 'dQw4w9WgXcQ';
        $url = util::get_youtube_embed_url($videoid);
        $this->assertStringContainsString('youtube.com/embed/' . $videoid, $url);
        $this->assertStringContainsString('enablejsapi=1', $url);
        $this->assertStringContainsString('rel=0', $url);

        // Test with origin
        $origin = 'https://example.com';
        $urlwithorigin = util::get_youtube_embed_url($videoid, $origin);
        $this->assertStringContainsString('origin=' . urlencode($origin), $urlwithorigin);
    }

    /**
     * Test get_vimeo_embed_url function.
     */
    public function test_get_vimeo_embed_url() {
        $videoid = '123456789';
        $url = util::get_vimeo_embed_url($videoid);
        $this->assertEquals('https://player.vimeo.com/video/' . $videoid, $url);
    }

    /**
     * Test get_youtube_thumbnail function.
     */
    public function test_get_youtube_thumbnail() {
        $videoid = 'dQw4w9WgXcQ';
        $thumbnail = util::get_youtube_thumbnail($videoid);
        $this->assertEquals('https://img.youtube.com/vi/' . $videoid . '/hqdefault.jpg', $thumbnail);
    }

    /**
     * Test calculate_percentage function.
     */
    public function test_calculate_percentage() {
        $this->assertEquals(50.0, util::calculate_percentage(50, 100));
        $this->assertEquals(25.0, util::calculate_percentage(25, 100));
        $this->assertEquals(0.0, util::calculate_percentage(0, 100));
        $this->assertEquals(100.0, util::calculate_percentage(100, 100));
        $this->assertEquals(33.33, util::calculate_percentage(1, 3), '', 0.01);
        $this->assertEquals(0.0, util::calculate_percentage(50, 0)); // Division by zero protection
    }

    /**
     * Test is_md5 function.
     */
    public function test_is_md5() {
        $this->assertTrue(util::is_md5('d41d8cd98f00b204e9800998ecf8427e'));
        $this->assertTrue(util::is_md5('abc123def4567890123456789012345'));
        $this->assertFalse(util::is_md5('not an md5 hash'));
        $this->assertFalse(util::is_md5('d41d8cd98f00b204e9800998ecf8427')); // Too short
        $this->assertFalse(util::is_md5('d41d8cd98f00b204e9800998ecf8427eg')); // Too long
        $this->assertFalse(util::is_md5(''));
    }

    /**
     * Test formatBytes function.
     */
    public function test_formatBytes() {
        $this->assertEquals('0 B', util::formatBytes(0));
        $this->assertEquals('1 KB', util::formatBytes(1024));
        $this->assertEquals('1 MB', util::formatBytes(1024 * 1024));
        $this->assertEquals('1 GB', util::formatBytes(1024 * 1024 * 1024));
        $this->assertEquals('512 B', util::formatBytes(512));
        $this->assertEquals('1.5 KB', util::formatBytes(1536));
    }

    /**
     * Test is_mpegdash function.
     */
    public function test_is_mpegdash() {
        $this->assertTrue(util::is_mpegdash('https://example.com/video.mpd'));
        $this->assertTrue(util::is_mpegdash('https://example.com/video.MPD'));
        $this->assertFalse(util::is_mpegdash('https://example.com/video.mp4'));
        $this->assertFalse(util::is_mpegdash('https://example.com/video.m3u8'));
    }

    /**
     * Test is_hls function.
     */
    public function test_is_hls() {
        $this->assertTrue(util::is_hls('https://example.com/video.m3u8'));
        $this->assertTrue(util::is_hls('https://example.com/video.M3U8'));
        $this->assertFalse(util::is_hls('https://example.com/video.mp4'));
        $this->assertFalse(util::is_hls('https://example.com/video.mpd'));
    }
}

