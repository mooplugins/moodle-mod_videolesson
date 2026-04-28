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
 * Unit tests for mod_videolesson conversion class.
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
require_once($CFG->dirroot . '/mod/videolesson/classes/conversion.php');

/**
 * Unit tests for conversion class.
 *
 * @package    mod_videolesson
 * @category   test
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_videolesson\conversion
 */
final class conversion_test extends \advanced_testcase {
    /**
     * Test conversion constants are defined correctly.
     */
    public function test_conversion_constants(): void {
        $this->assertEquals(200, conversion::CONVERSION_FINISHED);
        $this->assertEquals(201, conversion::CONVERSION_IN_PROGRESS);
        $this->assertEquals(202, conversion::CONVERSION_ACCEPTED);
        $this->assertEquals(404, conversion::CONVERSION_NOT_FOUND);
        $this->assertEquals(500, conversion::CONVERSION_ERROR);
        $this->assertEquals(503, conversion::CONVERSION_UPLOAD_ERROR);
    }

    /**
     * Test get_conversion_settings returns required settings.
     */
    public function test_get_conversion_settings_basic(): void {
        global $CFG, $DB;

        $this->resetAfterTest();

        // Create a mock conversion record.
        $conversionrecord = new \stdClass();
        $conversionrecord->contenthash = 'testcontenthash123456789012345678901234567890';
        $conversionrecord->id = 1;

        $conversion = new conversion();
        $settings = $conversion->get_conversion_settings($conversionrecord);

        // Check required settings are present.
        $this->assertArrayHasKey('siteid', $settings);
        $this->assertArrayHasKey('siteurl', $settings);
        $this->assertArrayHasKey('transcoder', $settings);
        $this->assertArrayHasKey('pluginversion', $settings);

        // Check values.
        $this->assertEquals($CFG->siteidentifier, $settings['siteid']);
        $this->assertEquals($CFG->wwwroot, $settings['siteurl']);
        $this->assertEquals('mediaconvert', $settings['transcoder']);
    }

    /**
     * Test get_conversion_settings includes subtitle when pending subtitle record exists.
     */
    public function test_get_conversion_settings_with_subtitle(): void {
        global $DB;

        $this->resetAfterTest();

        // Create a conversion record.
        $conversionrecord = new \stdClass();
        $conversionrecord->contenthash = 'testcontenthash123456789012345678901234567890';
        $conversionrecord->id = 1;

        // Create a pending subtitle record.
        $subtitlerecord = new \stdClass();
        $subtitlerecord->contenthash = $conversionrecord->contenthash;
        $subtitlerecord->language_code = 'en';
        $subtitlerecord->status = \mod_videolesson\local\services\subtitle_service::STATUS_PENDING;
        $subtitlerecord->requested_at = time();
        $subtitlerecord->retry_count = 0;
        $subtitlerecord->id = $DB->insert_record('videolesson_subtitles', $subtitlerecord);

        $conversion = new conversion();
        $settings = $conversion->get_conversion_settings($conversionrecord);

        // Check subtitle setting is included.
        $this->assertArrayHasKey('subtitle', $settings);
        $this->assertEquals('en', $settings['subtitle']);
    }

    /**
     * Test get_conversion_settings does not include subtitle when no pending record exists.
     */
    public function test_get_conversion_settings_without_subtitle(): void {
        global $DB;

        $this->resetAfterTest();

        // Create a conversion record.
        $conversionrecord = new \stdClass();
        $conversionrecord->contenthash = 'testcontenthash123456789012345678901234567890';
        $conversionrecord->id = 1;

        $conversion = new conversion();
        $settings = $conversion->get_conversion_settings($conversionrecord);

        // Check subtitle setting is not included.
        $this->assertArrayNotHasKey('subtitle', $settings);
    }

    /**
     * Test get_conversion_settings does not include subtitle when subtitle is completed.
     */
    public function test_get_conversion_settings_subtitle_completed(): void {
        global $DB;

        $this->resetAfterTest();

        // Create a conversion record.
        $conversionrecord = new \stdClass();
        $conversionrecord->contenthash = 'testcontenthash123456789012345678901234567890';
        $conversionrecord->id = 1;

        // Create a completed subtitle record (should not be included).
        $subtitlerecord = new \stdClass();
        $subtitlerecord->contenthash = $conversionrecord->contenthash;
        $subtitlerecord->language_code = 'en';
        $subtitlerecord->status = \mod_videolesson\local\services\subtitle_service::STATUS_COMPLETED;
        $subtitlerecord->requested_at = time();
        $subtitlerecord->completed_at = time();
        $subtitlerecord->retry_count = 0;
        $subtitlerecord->id = $DB->insert_record('videolesson_subtitles', $subtitlerecord);

        $conversion = new conversion();
        $settings = $conversion->get_conversion_settings($conversionrecord);

        // Check subtitle setting is not included (only pending status is included).
        $this->assertArrayNotHasKey('subtitle', $settings);
    }
}
