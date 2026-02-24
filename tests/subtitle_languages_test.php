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
 * Unit tests for mod_videolesson subtitle_languages class.
 *
 * @package    mod_videolesson
 * @category   test
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/videolesson/classes/subtitle_languages.php');

/**
 * Unit tests for subtitle_languages class.
 *
 * @package    mod_videolesson
 * @category   test
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subtitle_languages_test extends \basic_testcase {

    /**
     * Test get_supported_languages returns array.
     */
    public function test_get_supported_languages() {
        $languages = subtitle_languages::get_supported_languages();
        $this->assertIsArray($languages);
        $this->assertNotEmpty($languages);
    }

    /**
     * Test is_supported with valid language codes.
     */
    public function test_is_supported_valid() {
        $this->assertTrue(subtitle_languages::is_supported('en'));
        $this->assertTrue(subtitle_languages::is_supported('es'));
        $this->assertTrue(subtitle_languages::is_supported('fr'));
        $this->assertTrue(subtitle_languages::is_supported('de'));
        $this->assertTrue(subtitle_languages::is_supported('zh'));
        $this->assertTrue(subtitle_languages::is_supported('zh-TW'));
        $this->assertTrue(subtitle_languages::is_supported('original'));
    }

    /**
     * Test is_supported with invalid language codes.
     */
    public function test_is_supported_invalid() {
        $this->assertFalse(subtitle_languages::is_supported('xx'));
        $this->assertFalse(subtitle_languages::is_supported('invalid'));
        $this->assertFalse(subtitle_languages::is_supported(''));
    }

    /**
     * Test get_display_name with valid language codes.
     */
    public function test_get_display_name_valid() {
        $this->assertEquals('English', subtitle_languages::get_display_name('en'));
        $this->assertEquals('Spanish', subtitle_languages::get_display_name('es'));
        $this->assertEquals('French', subtitle_languages::get_display_name('fr'));
        $this->assertEquals('Original Language', subtitle_languages::get_display_name('original'));
    }

    /**
     * Test get_display_name with invalid language codes.
     */
    public function test_get_display_name_invalid() {
        $this->assertEquals('', subtitle_languages::get_display_name('xx'));
        $this->assertEquals('', subtitle_languages::get_display_name('invalid'));
        $this->assertEquals('', subtitle_languages::get_display_name(''));
    }

    /**
     * Test that all language codes in the list are valid.
     */
    public function test_all_languages_have_valid_codes() {
        $languages = subtitle_languages::get_supported_languages();
        foreach ($languages as $code => $name) {
            $this->assertNotEmpty($code, "Language code should not be empty");
            $this->assertNotEmpty($name, "Language name should not be empty for code: $code");
            $this->assertTrue(subtitle_languages::is_supported($code), "Language code $code should be supported");
        }
    }
}

