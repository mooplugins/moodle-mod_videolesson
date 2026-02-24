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
 * SQS self hosted
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videolesson;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * Handles AWS SQS operations for mod_videolesson.
 *
 * @package     mod_videolesson
 * @copyright   2022 BitKea Technologies LLP
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sqs_sdk_handler {

    /** @var object Plugin configuration settings. */
    private $config;

    /** @var \Aws\Sqs\SqsClient SQS client instance. */
    private $client;

    /** Maximum number of messages to fetch from the SQS queue per run. */
    private const MAX_MESSAGES = 100;

    /**
     * Constructor for sqs_sdk_handler.
     */
    public function __construct() {
        $this->config = get_config('mod_videolesson');
    }

    /**
     * Creates and returns an AWS SQS client.
     *
     * @param \GuzzleHttp\Handler|null $handler Optional HTTP handler.
     * @return \Aws\Sqs\SqsClient
     */
    public function create_client() {
        $connectionoptions = [
            'version' => 'latest',
            'region' => $this->config->api_region,
            'credentials' => [
                'key' => $this->config->api_key,
                'secret' => $this->config->api_secret,
            ]
        ];

        // Instantiate the client if not already created.
        if (!isset($this->client)) {
            $this->client = \local_aws\local\client_factory::get_client('\Aws\Sqs\SqsClient', $connectionoptions);
        }

        return $this->client;
    }

    /**
     * Retrieves pending messages from the AWS SQS queue.
     *
     * @return array The messages retrieved from the SQS queue.
     */
    private function get_queue_messages() : array {
        global $CFG;

        $messages = [];
        $messageparams = [
            'AttributeNames' => ['All'],
            'MaxNumberOfMessages' => 10,  // Maximum messages per call.
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->config->sqs_queue_url,
            'VisibilityTimeout' => 60,
            'WaitTimeSeconds' => 10, // Controls polling delay.
        ];

        while (count($messages) < self::MAX_MESSAGES) {
            $result = $this->client->receiveMessage($messageparams);
            $newmessages = $result->get('Messages');
            if (empty($newmessages)) {
                // Stop if no new messages are received.
                break;
            }

            // Handle each message uniquely and avoid duplicates.
            foreach ($newmessages as $newmessage) {
                $messagebody = json_decode($newmessage['Body']);
                $messagehash = md5(json_encode($messagebody->message));

                // Primary check: Extract bucketkey from objectkey and compare
                $messagebucketkey = null;
                $currentbucketkey = $this->config->bucket_key ?? 'videolesson';

                if (!empty($messagebody->objectkey)) {
                    // Extract bucketkey from objectkey (format: "bucket_key/contenthash")
                    $parts = explode('/', $messagebody->objectkey, 2);
                    if (count($parts) > 1) {
                        $messagebucketkey = $parts[0];
                    }
                }

                // Check bucketkey first (primary method used by Lambda)
                $bucketkeyMatches = ($messagebucketkey === $currentbucketkey);

                // Fallback: Check siteid for backward compatibility with old messages
                $messagesiteid = null;
                if (isset($newmessage['MessageAttributes']['siteid']['StringValue'])) {
                    $messagesiteid = $newmessage['MessageAttributes']['siteid']['StringValue'];
                }
                $siteidMatches = ($messagesiteid === $CFG->siteidentifier);

                // Accept message if bucketkey matches, or if bucketkey check fails but siteid matches (backward compat)
                if ($bucketkeyMatches || (!$messagebucketkey && $siteidMatches)) {
                    $messages[$messagehash] = $newmessage;
                }
                // Otherwise skip the message (belongs to different tenant)
            }
        }

        return $messages;
    }

    /**
     * Stores received SQS messages in the database.
     *
     * @param array $messages The messages to store.
     */
    private function store_messages(array $messages) : void {
        global $DB;

        if (empty($messages)) {
            return; // Exit if no messages to process.
        }

        $messagerecords = [];
        $messagehashes = [];

        foreach ($messages as $message) {
            $messagebody = json_decode($message['Body']);

            // Skip messages without essential attributes.
            if (empty($messagebody->objectkey) || empty($messagebody->timestamp)) {
                continue;
            }

            $messagejson = json_encode($messagebody->message);
            $hash = md5($messagejson);
            $record = new \stdClass();
            $record->objectkey = preg_replace('/^videolesson\//', '', $messagebody->objectkey, 1);
            $record->process = $messagebody->process;
            $record->status = $messagebody->status;
            $record->messagehash =$hash;
            $record->message = $messagejson;
            $record->senttime = $messagebody->timestamp;
            $record->timecreated = time();

            $messagerecords[$hash] = $record;
            $messagehashes[] = $hash;
        }

        // Avoid inserting duplicate messages into the database.
        $transaction = $DB->start_delegated_transaction();
        list($insql, $inparams) = $DB->get_in_or_equal($messagehashes);
        $sql = "SELECT messagehash FROM {videolesson_queue_msgs} WHERE messagehash $insql";
        $existingmessages = $DB->get_records_sql($sql, $inparams);
        $recordstoinsert = array_diff_key($messagerecords, $existingmessages);
        $DB->insert_records('videolesson_queue_msgs', $recordstoinsert);
        $transaction->allow_commit();
    }

    /**
     * Deletes messages from the AWS SQS queue.
     *
     * @param array $messages The messages to delete.
     * @return array The results of the deletions.
     */
    private function delete_queue_messages(array $messages) : array {
        $results = [];

        foreach ($messages as $message) {
            $deleteparams = [
                'QueueUrl' => $this->config->sqs_queue_url,
                'ReceiptHandle' => $message['ReceiptHandle'],
            ];

            $results[] = $this->client->deleteMessage($deleteparams)->get('@metadata');
        }

        return $results;
    }

    /**
     * Processes outstanding messages in the SQS queue.
     *
     * @return int The count of messages processed.
     */
    public function process_queue() : int {
        $this->create_client();
        $messages = $this->get_queue_messages(); // Retrieve messages.
        $this->store_messages($messages); // Store messages in the database.
        $this->delete_queue_messages($messages); // Delete messages from the queue.
        return count($messages);
    }

}
