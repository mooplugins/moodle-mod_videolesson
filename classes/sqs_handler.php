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
 * AWS SQS handler for the VideoLesson module.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson;

defined('MOODLE_INTERNAL') || die();

class sqs_handler {

    /** @var string $hostingtype The license type, determining hosted or SDK-based operations */
    private $hostingtype;

    /** @var aws_hosted_handler|aws_sdk_handler $handler The handler instance */
    private $handler;

    /**
     * Constructor for the sqs_handler class.
     *
     * @throws \Exception If bucket type is invalid.
     */
    public function __construct() {
        $this->hostingtype = get_config('mod_videolesson', 'hosting_type');
        $this->handler = $this->hostingtype === 'hosted'
            ? new sqs_hosted_handler()
            : new sqs_sdk_handler();
    }

    /**
     * Processes the SQS queue using the appropriate handler.
     *
     * @return mixed The result of the queue processing, depending on the handler implementation.
     * @throws \Exception If the handler does not implement the process_queue method.
     */
    public function process_queue() {
        // Ensure the handler has the process_queue method before calling it.
        if (!method_exists($this->handler, 'process_queue')) {
            throw new \Exception('The handler does not implement the process_queue method.');
        }

        // Delegate the queue processing to the handler.
        return $this->handler->process_queue();
    }
}
