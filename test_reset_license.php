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
 * Test script to set/reset mock license data
 *
 * WARNING: This is for testing only. Remove or restrict access in production.
 *
 * Usage:
 * - Set mock license: /mod/videolesson/test_reset_license.php?action=set
 * - Reset/clear license: /mod/videolesson/test_reset_license.php?action=reset
 * - View current license: /mod/videolesson/test_reset_license.php?action=view
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

// Security check - only allow in development/testing
// Remove or restrict this in production!
require_login();
$context = \context_system::instance();
require_capability('moodle/site:config', $context);

$action = optional_param('action', 'view', PARAM_ALPHANUMEXT);

// Mock license data for testing
$mock_license_data = [
    'result' => 'success',
    'license_key' => 'TEST-LICENSE-KEY-' . time(),
    'status' => 'active',
    'type' => 'hosted', // Can be 'hosted', 'self', or 'none'
    'date_expiry' => date('Y-m-d', strtotime('+1 year')),
    'apiurl' => 'https://api.mooplugins.com',
    'cloudfrontdomain' => 'https://cdn.mooplugins.com',
];

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>License Test Reset</title>';
echo '<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;}</style>';
echo '</head><body>';
echo '<h1>VideoAWS License Test Reset</h1>';

switch ($action) {
    case 'set':
        // Set mock license data
        set_config('license_key', $mock_license_data['license_key'], 'mod_videolesson');
        set_config('license_details', json_encode($mock_license_data), 'mod_videolesson');
        set_config('hosting_type', $mock_license_data['type'], 'mod_videolesson');
        set_config('apiurl', $mock_license_data['apiurl'], 'mod_videolesson');
        set_config('cloudfrontdomainhosted', $mock_license_data['cloudfrontdomain'], 'mod_videolesson');

        // Set setup steps as complete for testing
        set_config('setup_step1_complete', 1, 'mod_videolesson');
        set_config('setup_step2_complete', 1, 'mod_videolesson');

        echo '<p class="success">✓ Mock license data set successfully!</p>';
        echo '<h2>Mock License Data:</h2>';
        echo '<pre>' . htmlspecialchars(json_encode($mock_license_data, JSON_PRETTY_PRINT)) . '</pre>';
        break;

    case 'enable_test_mode':
        // Enable test mode for free hosting (mocks external API calls)
        $result = set_config('test_mode_free_hosting', 1, 'mod_videolesson');
        if ($result !== false) {
            echo '<p class="success">✓ Test mode enabled for free hosting!</p>';
            echo '<p>Free hosting activation will now return mock data instead of calling external API.</p>';
            echo '<p>Config value set: ' . (get_config('mod_videolesson', 'test_mode_free_hosting') ? 'Yes' : 'No') . '</p>';
        } else {
            echo '<p class="error">✗ Failed to enable test mode. Please check permissions.</p>';
        }
        break;

    case 'disable_test_mode':
        // Disable test mode
        unset_config('test_mode_free_hosting', 'mod_videolesson');
        echo '<p class="success">✓ Test mode disabled for free hosting!</p>';
        echo '<p>Free hosting activation will now call the real external API.</p>';
        break;

    case 'reset':
        // Clear all license and setup data
        unset_config('license_key', 'mod_videolesson');
        unset_config('license_details', 'mod_videolesson');
        unset_config('hosting_type', 'mod_videolesson');
        unset_config('apiurl', 'mod_videolesson');
        unset_config('cloudfrontdomainhosted', 'mod_videolesson');
        unset_config('mooplugins_license', 'mod_videolesson');
        unset_config('mooplugins_license_details', 'mod_videolesson');
        unset_config('setup_step1_complete', 'mod_videolesson');
        unset_config('setup_step2_complete', 'mod_videolesson');
        unset_config('setup_step3_complete', 'mod_videolesson');
        unset_config('setup_completed', 'mod_videolesson');

        echo '<p class="success">✓ All license and setup data cleared!</p>';
        break;

    case 'view':
    default:
        // View current license data
        $license_key = get_config('mod_videolesson', 'license_key');
        $license_details = get_config('mod_videolesson', 'license_details');
        $hosting_type = get_config('mod_videolesson', 'hosting_type');
        $setup_step1 = get_config('mod_videolesson', 'setup_step1_complete');
        $setup_step2 = get_config('mod_videolesson', 'setup_step2_complete');
        $setup_step3 = get_config('mod_videolesson', 'setup_step3_complete');
        $test_mode = get_config('mod_videolesson', 'test_mode_free_hosting');
        $license_details_response = get_config('mod_videolesson', 'license_details_response');

        echo '<h2>Current License Data:</h2>';
        echo '<pre>';
        echo "License Key: " . ($license_key ?: 'Not set') . "\n";
        echo "Hosting Type: " . ($hosting_type ?: 'Not set') . "\n";
        echo "Setup Step 1 Complete: " . ($setup_step1 ? 'Yes' : 'No') . "\n";
        echo "Setup Step 2 Complete: " . ($setup_step2 ? 'Yes' : 'No') . "\n";
        echo "Setup Step 3 Complete: " . ($setup_step3 ? 'Yes' : 'No') . "\n";
        echo "Test Mode (Free Hosting): " . ($test_mode ? 'Enabled' : 'Disabled') . "\n";
        echo "\nLicense Details:\n";
        if ($license_details) {
            $decoded = json_decode($license_details, true);
            echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT));
        } else {
            echo 'Not set';
        }


        if ($license_details_response) {
            $decoded = json_decode($license_details_response, true);
            echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT));
        }

        echo '</pre>';
        break;
}

echo '<hr>';
echo '<h2>Actions:</h2>';
echo '<ul>';
echo '<li><a href="?action=set">Set Mock License</a></li>';
echo '<li><a href="?action=reset">Reset/Clear License</a></li>';
echo '<li><a href="?action=view">View Current License</a></li>';
echo '<li><a href="?action=enable_test_mode">Enable Test Mode (Free Hosting)</a> - Mocks external API calls</li>';
echo '<li><a href="?action=disable_test_mode">Disable Test Mode (Free Hosting)</a> - Uses real API</li>';
echo '</ul>';

echo '<hr>';
echo '<p><small>WARNING: This script is for testing only. Remove or restrict access in production.</small></p>';
echo '</body></html>';
