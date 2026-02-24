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
 * Settings
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/videolesson/classes/admin_setting_moopluginlicense.php');

$setupstep2complete = get_config('mod_videolesson', 'setup_step2_complete');
$donewizard = !empty($setupstep2complete);

$ADMIN->add('modsettings', new admin_category(
    'modvideolessonfolder',
    get_string('modulename', 'mod_videolesson'),
    $module->is_enabled() === false
));

$manage = new admin_externalpage(
    'videolessonprovision',
    get_string('provision', 'mod_videolesson'),
    new \moodle_url('/mod/videolesson/provision.php'),
    'moodle/site:config',
    !$donewizard
);
$ADMIN->add('modvideolessonfolder', $manage);

$access = new \mod_videolesson\access();
$settings = new admin_settingpage(
    'modsettingvideolesson',
    get_string('settings:videolesson:header', 'mod_videolesson'),
    'moodle/site:config',
    $module->is_enabled() === false || !$donewizard
);

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
        'awsheader',
        get_string('settings:aws:header', 'mod_videolesson'),
        get_string('settings:aws:header_desc', 'mod_videolesson')
    ));

    $hostingoptions = [
        'self' => "Self Managed",
        'hosted' => 'Mooplugins',
        'none' => 'External'
    ];

    $settings->add(new admin_setting_configselect(
        'mod_videolesson/hosting_type',
        get_string('settings:aws:hostingtype', 'mod_videolesson'),
        get_string('settings:aws:hostingtype_help', 'mod_videolesson', $CFG->wwwroot . '/mod/videolesson/provision.php'),
        'self',
        $hostingoptions
    ));

    $settings->add(new \mod_videolesson\admin_setting_moopluginlicense(
        'mod_videolesson/license_key',
        get_string('settings:aws:moopluginlicense', 'mod_videolesson'),
        get_string('settings:aws:moopluginlicense_help', 'mod_videolesson'),
        '', // Default value
    ));

    $settings->add(new admin_setting_configtext(
        'mod_videolesson/api_key',
        get_string('settings:aws:key', 'mod_videolesson'),
        get_string('settings:aws:key_help', 'mod_videolesson'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'mod_videolesson/api_secret',
        get_string('settings:aws:secret', 'mod_videolesson'),
        get_string('settings:aws:secret_help', 'mod_videolesson'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'mod_videolesson/s3_input_bucket',
        get_string('settings:aws:input_bucket', 'mod_videolesson'),
        get_string('settings:aws:input_bucket_help', 'mod_videolesson'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'mod_videolesson/s3_output_bucket',
        get_string('settings:aws:output_bucket', 'mod_videolesson'),
        get_string('settings:aws:output_bucket_help', 'mod_videolesson'),
        ''
    ));

    $regionoptions = [
        'us-east-1'      => 'US East (N. Virginia)',
        'us-west-1'      => 'US West (N. California)',
        'us-west-2'      => 'US West (Oregon)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'ap-south-1'     => 'Asia Pacific (Mumbai)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'eu-west-1'      => 'EU (Ireland)',
    ];

    $settings->add(new admin_setting_configselect(
        'mod_videolesson/api_region',
        get_string('settings:aws:region', 'mod_videolesson'),
        get_string('settings:aws:region_help', 'mod_videolesson'),
        'ap-southeast-2',
        $regionoptions
    ));

    // DynamoDB settings for transcoding status.
    $settings->add(new admin_setting_configtext(
        'mod_videolesson/dynamodb_table_name',
        get_string('settings:aws:dynamodb_table_name', 'mod_videolesson'),
        get_string('settings:aws:dynamodb_table_name_help', 'mod_videolesson'),
        'videolesson-transcoding-status',
        PARAM_TEXT
    ));

    // SNS Topic ARN for subtitle generation.
    $settings->add(new admin_setting_configtext(
        'mod_videolesson/sns_topic_arn',
        get_string('settings:aws:sns_topic_arn', 'mod_videolesson'),
        get_string('settings:aws:sns_topic_arn_desc', 'mod_videolesson'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'mod_videolesson/cloudfrontdomain',
        get_string('settings:aws:cloudfrontdomain', 'mod_videolesson'),
        get_string('settings:aws:cloudfrontdomain_help', 'mod_videolesson'),
        '',
        PARAM_URL
    ));

    // FFprobe settings.
    $settings->add(new admin_setting_heading(
        'ffprobeheader',
        get_string('settings:ffprobe:header', 'mod_videolesson'),
        get_string('settings:ffprobe:header_desc', 'mod_videolesson')
    ));

    $settings->add(new admin_setting_configexecutable(
        'mod_videolesson/pathtoffprobe',
        get_string('settings:ffprobe:pathtoffprobe', 'mod_videolesson'),
        get_string('settings:ffprobe:pathtoffprobe_desc', 'mod_videolesson'),
        '/usr/bin/ffprobe'
    ));

    $settings->add(new admin_setting_heading(
        'configintro',
        get_string('settings:instancedefaults', 'mod_videolesson'),
        get_string('settings:instancedefaults_desc', 'mod_videolesson')
    ));

    // Activity completion default.
    $options = range(0, 100);
    $setting = new admin_setting_configselect(
        'mod_videolesson/completionprogress',
        get_string('settings:completion:progress', 'mod_videolesson'),
        get_string('settings:completion:progressdesc', 'mod_videolesson'),
        85,
        $options
    );
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox(
        'mod_videolesson/exluderoles',
        get_string('settings:roles:exclude', 'mod_videolesson'),
        get_string('settings:roles:excludedesc', 'mod_videolesson'),
        1
    );
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox(
        'mod_videolesson/createsubtitle',
        get_string('settings:subtitle:create', 'mod_videolesson'),
        get_string('settings:subtitle:createdesc', 'mod_videolesson'),
        0
    );
    $settings->add($setting);

    $options = [
        0 => get_string('settings:seek:nooverride', 'mod_videolesson'),
        1 => get_string('settings:seek:allow', 'mod_videolesson'),
        2 => get_string('settings:seek:disableseek', 'mod_videolesson'),
        3 => get_string('settings:seek:disableseekrewind', 'mod_videolesson')
    ];
    $settings->add(new admin_setting_configselect(
        'mod_videolesson/overrideseekbehavior', // Config name
        get_string('settings:seek:override:options', 'mod_videolesson'),
        get_string('settings:seek:override:description', 'mod_videolesson'),
        0,
        $options
    ));

    // Completion threshold to force disable seek.
    $thresholdoptions = [];
    for ($i = 0; $i <= 100; $i += 5) {
        $thresholdoptions[$i] = $i . '%';
    }
    $settings->add(new admin_setting_configselect(
        'mod_videolesson/completionprogress_force_disable_seek_threshold',
        get_string('settings:completion:force_disable_seek_threshold', 'mod_videolesson'),
        get_string('settings:completion:force_disable_seek_threshold_help', 'mod_videolesson'),
        70,
        $thresholdoptions
    ));

    // Disable speed option.
    $settings->add(new admin_setting_configcheckbox(
        'mod_videolesson/overridedisablespeed', // Config name.
        get_string('settings:speed:override', 'mod_videolesson'), // Setting title.
        get_string('settings:speed:override:desc', 'mod_videolesson'), // Description.
        0 // Default value: unchecked.
    ));

    // Disable Picture-in-Picture (PiP) option.
    $settings->add(new admin_setting_configcheckbox(
        'mod_videolesson/overridedisablepip',
        get_string('settings:pip:override', 'mod_videolesson'),
        get_string('settings:pip:override:desc', 'mod_videolesson'),
        0
    ));

    $settings->add(new admin_setting_heading(
        'mod_videolesson/tinynotice',
        '',
        '<div class="alert alert-info" role="alert">
        <h5 class="alert-heading">🎞 Insert Video from Library</h5>
        <p>Insert videos from the <strong>Video Library</strong> directly into content areas using the TinyMCE editor. TinyMCE must be set as the default text editor for this feature to work.</p>
        <div class="mt-3">
            <p>See: <em><a href="https://www.mooplugins.com/docs/how-to-set-tinymce-as-the-default-text-editor/ " target="_blank" rel="noopener">How to Set TinyMCE as the Default Text Editor in Moodle</a></em> for instructions.</p>
        </div></div>'
    ));

    $PAGE->requires->js_init_code("
        function toggleFieldsBasedOnHostingType() {
            var hostingType = document.querySelector('select[name=\"s_mod_videolesson_hosting_type\"]');
            var licenseKeyField = document.querySelector('input[name=\"s_mod_videolesson_license_key\"]');

            // List of input field selectors to hide/disable with their row containers
            var fieldsToToggle = [
                'input[name=\"s_mod_videolesson_api_key\"]',
                'input[name=\"s_mod_videolesson_api_secret\"]',
                'input[name=\"s_mod_videolesson_s3_input_bucket\"]',
                'input[name=\"s_mod_videolesson_s3_output_bucket\"]',
                'select[name=\"s_mod_videolesson_api_region\"]',
                'input[name=\"s_mod_videolesson_sns_topic_arn\"]',
                'input[name=\"s_mod_videolesson_cloudfrontdomain\"]',
                'input[name=\"s_mod_videolesson_dynamodb_table_name\"]'
            ];

            function setFieldsState(isDisabled) {
                fieldsToToggle.forEach(function(selector) {
                    var field = document.querySelector(selector);
                    var fieldRow = field ? field.closest('.form-item.row') : null;

                    if (field) {
                        field.disabled = isDisabled;
                    }

                    if (fieldRow) {
                        fieldRow.style.display = isDisabled ? 'none' : ''; // Hide/show the entire row
                    }
                });
            }

            // Disable the license key field when the hosting type is 'self'
            function setLicenseKeyFieldState(isReadonly) {
                if (licenseKeyField) {
                    licenseKeyField.readOnly = isReadonly;

                    // Find the closest row element for the license key field
                    var licenseKeyFieldRow = licenseKeyField.closest('.form-item.row');

                    // Hide or show the row based on the isReadonly flag
                    if (licenseKeyFieldRow) {
                        licenseKeyFieldRow.style.display = isReadonly ? 'none' : '';
                    }

                }
            }


            // Initial check on page load
            var hostingTypeValue = hostingType.value;
            var isSelfManaged = hostingTypeValue === 'self';
            var isHosted = hostingTypeValue === 'hosted';
            setFieldsState(!isSelfManaged);
            // Hide license key field for 'self' and 'none', show only for 'hosted'
            setLicenseKeyFieldState(!isHosted);

            // Change event listener to toggle fields dynamically
            hostingType.addEventListener('change', function() {
                var hostingTypeValue = this.value;
                var isSelfManaged = hostingTypeValue === 'self';
                var isHosted = hostingTypeValue === 'hosted';
                setFieldsState(!isSelfManaged);
                // Hide license key field for 'self' and 'none', show only for 'hosted'
                setLicenseKeyFieldState(!isHosted);
            });
        }

        document.addEventListener('DOMContentLoaded', toggleFieldsBasedOnHostingType);
    ");
}

if ($donewizard) {
    $ADMIN->add('modvideolessonfolder', $settings);
}
$settings = null;

$manage = new admin_externalpage(
    'videolessonmanage',
    get_string('manage_videos', 'mod_videolesson'),
    new \moodle_url('/mod/videolesson/library.php'),
    'mod/videolesson:manage',
    !$donewizard
);
$ADMIN->add('modvideolessonfolder', $manage);

$setupwizard = new admin_externalpage(
    'videolessonsetup',
    get_string('setup:wizard:title', 'mod_videolesson'),
    new \moodle_url('/mod/videolesson/index.php'),
    'moodle/site:config',
    $donewizard  // Hide from menu when setup is complete
);
$ADMIN->add('modvideolessonfolder', $setupwizard);
