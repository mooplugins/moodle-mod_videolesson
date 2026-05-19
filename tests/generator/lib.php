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
 * mod_videolesson data generator.
 *
 * @package    mod_videolesson
 * @category   test
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/videolesson/locallib.php');

/**
 * Videolesson module data generator class.
 *
 * @package    mod_videolesson
 * @category   test
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videolesson_generator extends testing_module_generator {
    /**
     * Creates a new videolesson module instance.
     *
     * @param array|stdClass|null $record Data for the module instance.
     * @param array|null $options Course module options.
     * @return stdClass Instance record with cmid set.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (array) $record + [
            'name' => 'Test videolesson',
            'source' => MOD_VIDEOLESSON_SRC_EXTERNAL,
            'videourl' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ];

        return parent::create_instance($record, $options);
    }
}
