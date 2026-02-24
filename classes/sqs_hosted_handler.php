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
 * SQS hosted
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson;

defined('MOODLE_INTERNAL') || die();

class sqs_hosted_handler {

    /** @var string $apiurl The WordPress REST API endpoint URL */
    private $apiurl;

    /** @var string $licensekey The license key for validation */
    private $licensekey;

    public function __construct() {
        $config = get_config('mod_videolesson');
        $this->apiurl = $config->apiurl;
        $this->licensekey = $config->license_key;
    }

    private function get_queue_messages() : array {
        global $DB;
        if (!$this->apiurl || !$this->licensekey) {
            throw new \Exception('API URL or License Key not set');
        }

        // Get contenthashes from pending conversions
        $sql = "SELECT contenthash FROM {videolesson_conv} WHERE uploaded = 200 AND status <> 200";
        $records = $DB->get_records_sql($sql);
        $keys = [];
        foreach ($records as $record) {
            $keys[] = $record->contenthash;
        }

        // Also get contenthashes from pending/processing subtitles
        $subtitleSql = "SELECT DISTINCT contenthash FROM {videolesson_subtitles} WHERE status IN ('pending', 'processing')";
        $subtitleRecords = $DB->get_records_sql($subtitleSql);
        foreach ($subtitleRecords as $record) {
            $keys[] = $record->contenthash;
        }

        // Remove duplicates
        $keys = array_unique($keys);

        if (empty($keys)) {
            return [];
        }

        $postdata = [
            'license_key' => $this->licensekey,
            'action' => 'sqs',
            'objectkey' => json_encode($keys)
        ];

        $messages = util::execute_hosted_api_request($this->apiurl, $postdata, [
            'check_http_code' => true
        ]);
        return $messages;
    }

    /**
     * Store received SQS queue messages in the DB.
     *
     * @param array $messages THe messages to store.
     */

    private function store_messages(array $messages) : void {
        global $DB;
        $messagerecords = [];
        $messagehashes = [];

        if (empty($messages)) {
            return;
        }

        foreach ($messages as $message) {
            $hash = md5($message['message']);
            $record = new \stdClass();
            $record->objectkey = $message['objectkey'];
            $record->process = $message['process'];
            $record->status = $message['status'];
            $record->messagehash = $hash;
            $record->message = $message['message'];
            $record->senttime = $message['senttime'];
            $record->timecreated = time();
            $messagerecords[$hash] = $record;
            $messagehashes[] = $hash;
        }

        $transaction = $DB->start_delegated_transaction();
        list($insql, $inparams) = $DB->get_in_or_equal($messagehashes);
        $sql = "SELECT messagehash FROM {videolesson_queue_msgs} WHERE messagehash $insql";
        $existingmessages = $DB->get_records_sql($sql, $inparams);
        $recordstoinsert = array_diff_key($messagerecords, $existingmessages);
        $DB->insert_records('videolesson_queue_msgs', $recordstoinsert);
        $transaction->allow_commit();
    }

    /**
     * @return int Count of messages processed.
     */
    public function process_queue() : int {
        $messages = $this->get_queue_messages(); // Get current messages from queue.
        if (!empty($messages['messages'])){
            $this->store_messages($messages['messages']); // Store messages in database.
            return count($messages['messages']);
        }

        return 0;
    }

}
