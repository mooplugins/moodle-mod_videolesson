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
 * Unit tests for mod_videolesson lib.php functions.
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
require_once($CFG->dirroot . '/mod/videolesson/lib.php');
require_once($CFG->dirroot . '/mod/videolesson/classes/util.php');

/**
 * Unit tests for lib.php functions.
 *
 * @package    mod_videolesson
 * @category   test
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::videolesson_preparedata
 */
final class lib_test extends \advanced_testcase {
    /**
     * Create a videolesson course module and return its cmid.
     *
     * @return int
     */
    private function get_test_cmid(): int {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $module = $generator->create_module('videolesson', [
            'course' => $course->id,
            'source' => MOD_VIDEOLESSON_SRC_EXTERNAL,
            'videourl' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        return $module->cmid;
    }

    /**
     * Test videolesson_preparedata with MOD_VIDEOLESSON_SRC_GALLERY source.
     */
    public function test_videolesson_preparedata_gallery(): void {
        $this->resetAfterTest();

        $data = new \stdClass();
        $data->source = MOD_VIDEOLESSON_SRC_GALLERY;
        $data->contenthash = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $data->coursemodule = $this->get_test_cmid();

        $data = videolesson_preparedata($data);

        $this->assertEquals($data->contenthash, $data->sourcedata);
    }

    /**
     * Test videolesson_preparedata with MOD_VIDEOLESSON_SRC_EXTERNAL YouTube URL.
     */
    public function test_videolesson_preparedata_external_youtube(): void {
        $this->resetAfterTest();

        $data = new \stdClass();
        $data->source = MOD_VIDEOLESSON_SRC_EXTERNAL;
        $data->videourl = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $data->coursemodule = $this->get_test_cmid();

        $data = videolesson_preparedata($data);

        $this->assertStringStartsWith('youtube:', $data->sourcedata);
        $this->assertStringContainsString('dQw4w9WgXcQ', $data->sourcedata);
    }

    /**
     * Test videolesson_preparedata with MOD_VIDEOLESSON_SRC_EXTERNAL Vimeo URL.
     */
    public function test_videolesson_preparedata_external_vimeo(): void {
        $this->resetAfterTest();

        $data = new \stdClass();
        $data->source = MOD_VIDEOLESSON_SRC_EXTERNAL;
        $data->videourl = 'https://vimeo.com/123456789';
        $data->coursemodule = $this->get_test_cmid();

        $data = videolesson_preparedata($data);

        $this->assertStringStartsWith('vimeo:', $data->sourcedata);
        $this->assertStringContainsString('123456789', $data->sourcedata);
    }

    /**
     * Test videolesson_preparedata with MOD_VIDEOLESSON_SRC_EXTERNAL direct video URL.
     */
    public function test_videolesson_preparedata_external_direct_video(): void {
        $this->resetAfterTest();

        $data = new \stdClass();
        $data->source = MOD_VIDEOLESSON_SRC_EXTERNAL;
        $data->videourl = 'https://example.com/video.mp4';
        $data->coursemodule = $this->get_test_cmid();

        videolesson_preparedata($data);

        $this->assertEquals('https://example.com/video.mp4', $data->sourcedata);
    }

    /**
     * Test videolesson_preparedata with MOD_VIDEOLESSON_SRC_EXTERNAL unsupported embed.
     */
    public function test_videolesson_preparedata_external_unsupported_embed(): void {
        $this->resetAfterTest();

        $embedcode = '<iframe src="https://player.twitch.tv/?video=123"></iframe>';
        $data = new \stdClass();
        $data->source = MOD_VIDEOLESSON_SRC_EXTERNAL;
        $data->videourl = $embedcode;
        $data->coursemodule = $this->get_test_cmid();

        $data = videolesson_preparedata($data);

        // Should store the original embed code as-is.
        $this->assertEquals($embedcode, $data->sourcedata);
    }

    /**
     * Test videolesson_preparedata throws exception for invalid YouTube URL.
     */
    public function test_videolesson_preparedata_invalid_youtube_url(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);

        $data = new \stdClass();
        $data->source = MOD_VIDEOLESSON_SRC_EXTERNAL;
        $data->videourl = 'https://youtube.com/invalid';
        $data->coursemodule = $this->get_test_cmid();

        $data = videolesson_preparedata($data);
    }

    /**
     * Test videolesson_preparedata throws exception for invalid Vimeo URL.
     */
    public function test_videolesson_preparedata_invalid_vimeo_url(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);

        $data = new \stdClass();
        $data->source = MOD_VIDEOLESSON_SRC_EXTERNAL;
        $data->videourl = 'https://vimeo.com/invalid';
        $data->coursemodule = $this->get_test_cmid();

        $data = videolesson_preparedata($data);
    }

    /**
     * Test videolesson_preparedata throws exception for invalid video URL.
     */
    public function test_videolesson_preparedata_invalid_video_url(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);

        $data = new \stdClass();
        $data->source = MOD_VIDEOLESSON_SRC_EXTERNAL;
        $data->videourl = 'not a valid url';
        $data->coursemodule = $this->get_test_cmid();

        $data = videolesson_preparedata($data);
    }
}
