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
 * AWS S3 handler class
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_videolesson;

defined('MOODLE_INTERNAL') || die();

class aws_handler {

    /** @var string $buckettype Type of bucket (input/output) */
    private $buckettype;

    /** @var string $hostingtype The license type, determining hosted or SDK-based operations */
    private $hostingtype;

    /** @var aws_hosted_handler|aws_sdk_handler $handler The handler instance */
    private $handler;

    /**
     * Constructor for the aws_handler class.
     *
     * @param string $buckettype Input or output.
     * @throws \Exception If bucket type is invalid.
     */
    public function __construct($buckettype) {

        if (!in_array($buckettype, ['input', 'output'])) {
            throw new \Exception('Invalid bucket type');
        }

        $this->buckettype = $buckettype;
        $this->hostingtype = get_config('mod_videolesson', 'hosting_type');
        // Instantiate the appropriate handler based on the license type.
        $this->handler = $this->hostingtype === 'hosted'
            ? new aws_hosted_handler($this->buckettype)
            : new aws_sdk_handler($this->buckettype);
    }

    /**
     * Lists objects in the S3 bucket.
     *
     * @param string $prefix Optional prefix to filter the list of objects.
     * @param string $continuationtoken Continuation token for paginated results.
     * @return array List of objects.
     */
    public function list_objects($prefix = '', $continuationtoken = '', $delimit = false, $return = false) {
        return $this->handler->list_objects($prefix, $continuationtoken, $delimit, $return);
    }

    /**
     * List all prefixes in the S3 bucket and return them as an array of cleaned prefixes.
     *
     * This method retrieves all the prefixes (logical groupings) from an S3 bucket, cleans them
     * by removing the first segment before the first slash and trimming the trailing slash,
     * and then returns them as an array. This is useful when working with S3 where there are no
     * actual folders, and objects are grouped by prefixes.
     *
     * @return array An array of cleaned prefixes without the first segment and trailing slashes.
     */
    public function list_all_prefixes_array($refreshcache = false) {

        $cache = \cache::make('mod_videolesson', 'prefixes_cache');
        $cachekey = 'all_prefixes';

        if ($refreshcache) {
            $cache->delete($cachekey);
        }

        // Check cache first
        $cachedprefixes = $cache->get($cachekey);
        if ($cachedprefixes !== false) {
            return $cachedprefixes;
        }

        // Fetch all objects with their prefixes in the S3 bucket
        $result = $this->list_objects('', '', true);
        $prefixes = [];
        // Check if 'CommonPrefixes' exists
        if (isset($result['CommonPrefixes'])) {
            // If 'CommonPrefixes' is an array of arrays
            if (is_array($result['CommonPrefixes']) && isset($result['CommonPrefixes'][0])) {
                foreach ($result['CommonPrefixes'] as $prefixData) {
                    // Check if each entry is an array and contains the 'Prefix' key
                    if (is_array($prefixData) && isset($prefixData['Prefix'])) {
                        $cleanedPrefix = $this->clean_prefix($prefixData['Prefix']);
                        $prefixes[] = $cleanedPrefix;
                    }
                }
            }
            // Handle case where 'CommonPrefixes' is a single array with 'Prefix'
            elseif (is_array($result['CommonPrefixes']) && isset($result['CommonPrefixes']['Prefix'])) {
                $cleanedPrefix = $this->clean_prefix($result['CommonPrefixes']['Prefix']);
                $prefixes[] = $cleanedPrefix;
            }
        } else {
            // Handle the case when 'CommonPrefixes' is missing or not an array
            error_log('Invalid CommonPrefixes structure: ' . print_r($result, true));
        }

        // Save to cache
        $cache->set($cachekey, $prefixes);

        return $prefixes;
    }

    /**
     * Puts an object into the S3 bucket.
     *
     * @param string $key The object key.
     * @param string $file file object
     * @param array $options Optional parameters for the put operation.
     * @return array|string The result of the put operation.
     */
    public function put_object($key, $file, $options = [], $return = false) {
        return $this->handler->put_object($key, $file, $options, $return);
    }

    /**
     * Deletes an object from the S3 bucket.
     *
     * @param string $key The object key.
     * @return array|string The result of the delete operation.
     */
    public function delete_object($key) {
        return $this->handler->delete_object($key);
    }

    /**
     * Deletes multiple objects from the S3 bucket.
     *
     * @param array $keys List of object keys.
     * @return array The result of the delete operation.
     */
    public function delete_objects(array $keys) {
        return $this->handler->delete_objects($keys);
    }

    // Helper function to clean the prefix string
    private function clean_prefix($prefix) {
        // Remove the first segment before the slash and trim trailing slashes
        $cleanedPrefix = preg_replace('/^[^\/]+\//', '', $prefix);
        return rtrim($cleanedPrefix, '/');
    }

    public function cloudfrontdomain() {
        return $this->handler->cloudfrontdomain();
    }

    public function cloudfrontdomainlistformat($key) {
        return $this->handler->cloudfrontdomainlistformat($key);
    }

    public function canupload(){
        return $this->handler->canupload();
    }

    /**
     * Checks if an object exists in the S3 bucket.
     *
     * @param string $key The object key.
     * @return bool True if the object exists, false otherwise.
     */
    public function does_object_exist($key) {
        return $this->handler->does_object_exist($key);
    }
}
