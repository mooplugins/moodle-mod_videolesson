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
 * Setup Wizard
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = \context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

$url = new \moodle_url('/mod/videolesson/index.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('setup:wizard:title', 'mod_videolesson'));
$PAGE->set_heading(get_string('setup:wizard:title', 'mod_videolesson'));
$PAGE->set_pagelayout('admin');

admin_externalpage_setup('videolessonsetup');

// Handle form actions
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
if ($action) {
    require_sesskey();

    switch ($action) {
        case 'save_step1':
            // Save selected option to temp config
            $selectedoption = optional_param('hosting-type', '', PARAM_ALPHANUMEXT);
            $allowedoptions = ['free', 'self', 'none'];
            if (in_array($selectedoption, $allowedoptions)) {
                set_config('setup_wizard_selected_option', $selectedoption, 'mod_videolesson');
                set_config('setup_step1_complete', 1, 'mod_videolesson');
                redirect($url);
            } else {
                \core\notification::error(get_string('error:invalid:option', 'mod_videolesson'));
            }
            break;

        case 'activate_free_hosting':
            // Activate free hosting - either generate new or activate existing license
            $hasexistinglicense = optional_param('has_existing_license', 0, PARAM_INT);
            $existinglicensekey = optional_param('existing_license_key', '', PARAM_TEXT);

            $license = new \mod_videolesson\license();

            // Check if user wants to use existing license
            if ($hasexistinglicense && !empty($existinglicensekey)) {
                // Activate existing license
                $result = $license->activate($existinglicensekey);

                if ($result['result'] === 'success') {
                    // After activation, get full license details including apiurl and cloudfrontdomain
                    $licensedata = $license->callapi(\mod_videolesson\license::ACTION_CHECK, $existinglicensekey);
                    if ($licensedata['result'] === 'success' && !empty($licensedata['license_key'])) {
                        // Ensure type is set to 'hosted' and all required fields are present
                        $licensedata['type'] = 'hosted';
                        // Ensure license_key is set (CHECK response should have it, but be safe)
                        if (empty($licensedata['license_key'])) {
                            $licensedata['license_key'] = $existinglicensekey;
                        }
                        // Save full license details (this will update the data saved by activate())
                        $license->save($licensedata);
                    }
                    set_config('hosting_type', 'hosted', 'mod_videolesson');
                    redirect($url);
                } else {
                    \core\notification::error($result['message'] ?? get_string('setup:step2:hosted:activation:error', 'mod_videolesson'));
                    redirect($url);
                }
            } else {
                // Generate new free license
                $result = $license->generate_free_license();

                if ($result['result'] === 'success') {
                    // generate_free_license() already saves the license data including apiurl and cloudfrontdomain
                    // No need to call save() again here as it would overwrite with empty values
                    set_config('hosting_type', 'hosted', 'mod_videolesson');
                    redirect($url);
                } else {
                    \core\notification::error($result['message'] ?? get_string('setup:step2:hosted:activation:error', 'mod_videolesson'));
                    redirect($url);
                }
            }
            break;

        case 'validate_aws_and_complete_step2':
            // Validate AWS connection and complete Step 2 for self-managed
            $apikey = optional_param('api_key', '', PARAM_TEXT);
            $apisecret = optional_param('api_secret', '', PARAM_TEXT);
            $s3inputbucket = optional_param('s3_input_bucket', '', PARAM_TEXT);
            $s3outputbucket = optional_param('s3_output_bucket', '', PARAM_TEXT);
            $apiregion = optional_param('api_region', 'ap-southeast-2', PARAM_TEXT);

            // Validate required fields
            if (empty($apikey) || empty($apisecret) || empty($s3inputbucket) || empty($s3outputbucket)) {
                \core\notification::error(get_string('error:aws:required:fields', 'mod_videolesson'));
                redirect($url);
            }

            // Save AWS settings first
            set_config('api_key', $apikey, 'mod_videolesson');
            set_config('api_secret', $apisecret, 'mod_videolesson');
            set_config('s3_input_bucket', $s3inputbucket, 'mod_videolesson');
            set_config('s3_output_bucket', $s3outputbucket, 'mod_videolesson');
            set_config('api_region', $apiregion, 'mod_videolesson');

            // Save optional fields
            $dynamodbtable = optional_param('dynamodb_table_name', 'videolesson-transcoding-status', PARAM_TEXT);
            $snstopicarn = optional_param('sns_topic_arn', '', PARAM_TEXT);
            $cloudfrontdomain = optional_param('cloudfrontdomain', '', PARAM_URL);
            set_config('dynamodb_table_name', $dynamodbtable, 'mod_videolesson');
            set_config('sns_topic_arn', $snstopicarn, 'mod_videolesson');
            set_config('cloudfrontdomain', $cloudfrontdomain, 'mod_videolesson');

            // Validate AWS connection
            try {
                // Create a temporary config object for testing
                $testconfig = new \stdClass();
                $testconfig->api_key = $apikey;
                $testconfig->api_secret = $apisecret;
                $testconfig->api_region = $apiregion;
                $testconfig->s3_input_bucket = $s3inputbucket;
                $testconfig->s3_output_bucket = $s3outputbucket;

                // Test AWS S3 connection using plugin's AWS S3 class
                $awss3 = new \mod_videolesson\aws_s3($testconfig);
                $awss3->create_client();

                // Test input bucket access
                $inputresult = $awss3->is_bucket_accessible($s3inputbucket);
                if (!$inputresult->success) {
                    $errormessage = get_string('setup:step2:self:validation:input_bucket_failed', 'mod_videolesson') . ' ' . $inputresult->message;
                    $errormessage .= ' ' . get_string('setup:step2:self:validation:check_info', 'mod_videolesson');
                    \core\notification::error($errormessage);
                    redirect($url);
                }

                // Test output bucket access
                $outputresult = $awss3->is_bucket_accessible($s3outputbucket);
                if (!$outputresult->success) {
                    $errormessage = get_string('setup:step2:self:validation:output_bucket_failed', 'mod_videolesson') . ' ' . $outputresult->message;
                    $errormessage .= ' ' . get_string('setup:step2:self:validation:check_info', 'mod_videolesson');
                    \core\notification::error($errormessage);
                    redirect($url);
                }

                // Both buckets are accessible, save settings and complete step
                set_config('hosting_type', 'self', 'mod_videolesson');
                set_config('setup_step2_complete', 1, 'mod_videolesson');
                unset_config('setup_wizard_selected_option', 'mod_videolesson'); // Clear temp config
                \core\notification::success(get_string('setup:step2:self:validation:success', 'mod_videolesson'));
                redirect($url);
            } catch (\Exception $e) {
                $errormessage = get_string('error:aws:connection:failed', 'mod_videolesson') . ': ' . $e->getMessage();
                $errormessage .= ' ' . get_string('setup:step2:self:validation:check_info', 'mod_videolesson');
                \core\notification::error($errormessage);
                redirect($url);
            }
            break;

        case 'complete_step2':
            // Complete Step 2 (for hosted after activation, or external)
            $selectedoption = get_config('mod_videolesson', 'setup_wizard_selected_option');

            // Determine hosting type based on selected option
            if ($selectedoption === 'free') {
                // Should already be set to 'hosted' by activate_free_hosting
                // Just ensure it's set
                if (get_config('mod_videolesson', 'hosting_type') !== 'hosted') {
                    set_config('hosting_type', 'hosted', 'mod_videolesson');
                }
            } else if ($selectedoption === 'none') {
                set_config('hosting_type', 'none', 'mod_videolesson');
            }

            set_config('setup_step2_complete', 1, 'mod_videolesson');
            unset_config('setup_wizard_selected_option', 'mod_videolesson'); // Clear temp config
            redirect($url);
            break;

        case 'go_back_step1':
            // Go back to Step 1 - unmark Step 1 complete
            unset_config('setup_step1_complete', 'mod_videolesson');
            // Also clear Step 2 completion if it exists
            unset_config('setup_step2_complete', 'mod_videolesson');
            redirect($url);
            break;
    }
}

// Step completion tracking (new system)
$step1complete = get_config('mod_videolesson', 'setup_step1_complete');
$step2complete = get_config('mod_videolesson', 'setup_step2_complete');
$step3complete = get_config('mod_videolesson', 'setup_step3_complete');

// Read selected option from temp config (for Step 2 template selection)
$selectedoption = get_config('mod_videolesson', 'setup_wizard_selected_option');

// Determine current step
$hostingtype = get_config('mod_videolesson', 'hosting_type');
$currentstep = 1;
if (!empty($step1complete) && empty($step2complete)) {
    $currentstep = 2;
} else if (!empty($step1complete) && !empty($step2complete) && empty($step3complete)) {
    // Step 3 is the completion screen for all hosting types
    $currentstep = 3;
} else if (!empty($step1complete) && !empty($step2complete) && !empty($step3complete)) {
    // Setup is complete, show Step 3
    $currentstep = 3;
}

// Cleanup temp config if we're on Step 3 or setup is complete
if ($currentstep == 3) {
    unset_config('setup_wizard_selected_option', 'mod_videolesson');
}

$awssettings = [
    'api_key' => get_config('mod_videolesson', 'api_key') ?: '',
    'api_secret' => get_config('mod_videolesson', 'api_secret') ?: '',
    's3_input_bucket' => get_config('mod_videolesson', 's3_input_bucket') ?: '',
    's3_output_bucket' => get_config('mod_videolesson', 's3_output_bucket') ?: '',
    'api_region' => get_config('mod_videolesson', 'api_region') ?: 'ap-southeast-2',
    'dynamodb_table_name' => get_config('mod_videolesson', 'dynamodb_table_name') ?: 'videolesson-transcoding-status',
    'sns_topic_arn' => get_config('mod_videolesson', 'sns_topic_arn') ?: '',
    'cloudfrontdomain' => get_config('mod_videolesson', 'cloudfrontdomain') ?: '',
];

$hostinglicensekey = get_config('mod_videolesson', 'license_key') ?: '';
// hostingtype already retrieved above

// Check if free hosting is activated (for Step 2 Option 1)
$isactivated = false;
if ($hostingtype === 'hosted' && !empty($hostinglicensekey)) {
    $isactivated = true;
}

$currentregion = get_config('mod_videolesson', 'api_region') ?: 'ap-southeast-2';
$regionoptionslist = [
    'us-east-1' => 'US East (N. Virginia)',
    'us-west-1' => 'US West (N. California)',
    'us-west-2' => 'US West (Oregon)',
    'ap-northeast-1' => 'Asia Pacific (Tokyo)',
    'ap-south-1' => 'Asia Pacific (Mumbai)',
    'ap-southeast-1' => 'Asia Pacific (Singapore)',
    'ap-southeast-2' => 'Asia Pacific (Sydney)',
    'eu-west-1' => 'EU (Ireland)',
];
$regionoptions = [];
foreach ($regionoptionslist as $key => $value) {
    $regionoptions[] = [
        'key' => $key,
        'value' => $value,
        'selected' => ($key === $currentregion)
    ];
}

// Step 3 is shown for all hosting types (it's the completion screen)
// Step 3 progress bar always shows for uniformity
$showstep3 = true;

// Determine which Step 2 template to show based on selected option
$step2templatedata = [
    'is_hosted' => false,
    'is_self' => false,
    'is_external' => false,
];

if ($selectedoption === 'self') {
    $step2templatedata['is_self'] = true;
} else if ($selectedoption === 'none') {
    $step2templatedata['is_external'] = true;
} else if ($selectedoption === 'free' || empty($selectedoption)) {
    // Default to hosted
    $step2templatedata['is_hosted'] = true;
}

// Prepare selected_option data for template
$selectedoptiondata = [
    'value' => $selectedoption,
    'is_free' => ($selectedoption === 'free'),
    'is_self' => ($selectedoption === 'self'),
    'is_none' => ($selectedoption === 'none'),
];

$templatedata = [
    'step1complete' => !empty($step1complete),
    'step2complete' => !empty($step2complete),
    'step3complete' => !empty($step3complete),
    'currentstep' => $currentstep,
    'step1current' => ($currentstep == 1),
    'step2current' => ($currentstep == 2),
    'step3current' => ($currentstep == 3),
    'show_step3' => $showstep3,
    'wwwroot' => $CFG->wwwroot,
    'sesskey' => sesskey(),
    'awssettings' => $awssettings,
    'regionoptions' => $regionoptions,
    'hostinglicensekey' => $hostinglicensekey,
    'hostingtype' => [
        'value' => $hostingtype,
        'is_self' => ($hostingtype === 'self'),
        'is_hosted' => ($hostingtype === 'hosted'),
        'is_external' => ($hostingtype === 'none'),
    ],
    'is_activated' => $isactivated,
    'selected_option' => $selectedoptiondata,
    'step2_template' => $step2templatedata,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videolesson/setup_wizard', $templatedata);
// JavaScript for UI enhancements only (no state management)
$PAGE->requires->js_call_amd('mod_videolesson/setup_wizard', 'init', [$currentstep]);
echo $OUTPUT->footer();
