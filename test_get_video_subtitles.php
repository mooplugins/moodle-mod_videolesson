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
 * Test script for get_video_subtitles method
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

defined('MOODLE_INTERNAL') || die();

// Require admin login for security
require_login();
require_capability('moodle/site:config', context_system::instance());

// Set page context
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/mod/videolesson/test_get_video_subtitles.php');
$PAGE->set_title('Test get_video_subtitles');
$PAGE->set_heading('Test get_video_subtitles Method');

try {
    echo $OUTPUT->header();
} catch (\Exception $e) {
    die('Error rendering header: ' . $e->getMessage());
} catch (\Error $e) {
    die('Fatal error rendering header: ' . $e->getMessage());
}

echo html_writer::tag('h2', 'Test get_video_subtitles Method');
echo html_writer::tag('p', 'This script tests the get_video_subtitles method with different contenthashes.');

// Create videosource instance
try {
    $videosource = new \mod_videolesson\videosource();
} catch (\Exception $e) {
    echo html_writer::div(
        'Error creating videosource: ' . htmlspecialchars($e->getMessage()) . '<br>' .
        html_writer::tag('small', 'Stack trace: <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>'),
        'alert alert-danger'
    );
    echo $OUTPUT->footer();
    exit;
} catch (\Error $e) {
    echo html_writer::div(
        'Fatal error creating videosource: ' . htmlspecialchars($e->getMessage()) . '<br>' .
        html_writer::tag('small', 'Stack trace: <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>'),
        'alert alert-danger'
    );
    echo $OUTPUT->footer();
    exit;
}

// Get contenthash from URL parameter or use default
$contenthash = optional_param('contenthash', '', PARAM_TEXT);

// Show form to enter contenthash
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-header']);
echo html_writer::tag('h5', 'Test Parameters', ['class' => 'mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

echo html_writer::start_tag('form', ['method' => 'GET', 'class' => 'mb-3']);
echo html_writer::start_tag('div', ['class' => 'form-group']);
echo html_writer::label('Content Hash:', 'contenthash');
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'contenthash',
    'id' => 'contenthash',
    'class' => 'form-control',
    'value' => $contenthash,
    'placeholder' => 'Enter contenthash to test (e.g., abc123def456...)',
    'required' => true
]);
echo html_writer::tag('small', 'Enter a contenthash from videolesson_conv table', ['class' => 'form-text text-muted']);
echo html_writer::end_tag('div');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => 'Test',
    'class' => 'btn btn-primary'
]);
echo html_writer::end_tag('form');

// Show list of available contenthashes from database
global $DB, $CFG;
$available_hashes = $DB->get_records_sql(
    "SELECT DISTINCT contenthash FROM {videolesson_conv} ORDER BY timecreated DESC LIMIT 20",
    []
);

if (!empty($available_hashes)) {
    echo html_writer::tag('p', html_writer::tag('strong', 'Available Content Hashes (click to test):'));
    echo html_writer::start_tag('div', ['class' => 'list-group']);
    foreach ($available_hashes as $hash) {
        $url = new moodle_url('/mod/videolesson/test_get_video_subtitles.php', ['contenthash' => $hash->contenthash]);
        echo html_writer::tag('a', htmlspecialchars($hash->contenthash), [
            'href' => $url->out(false),
            'class' => 'list-group-item list-group-item-action'
        ]);
    }
    echo html_writer::end_tag('div');
} else {
    echo html_writer::div('No contenthashes found in database.', 'alert alert-info');
}

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Run test if contenthash is provided
if ($contenthash) {
    echo html_writer::start_tag('div', ['class' => 'card mt-4']);
    echo html_writer::start_tag('div', ['class' => 'card-header']);
    echo html_writer::tag('h5', 'Test Results for: ' . htmlspecialchars($contenthash), ['class' => 'mb-0']);
    echo html_writer::end_tag('div');
    echo html_writer::start_tag('div', ['class' => 'card-body']);

    // Show database records
    global $DB, $CFG;
    echo html_writer::tag('h6', '1. Database Records');

    // Check videolesson_subtitles table
    $subtitle_records = $DB->get_records('videolesson_subtitles', [
        'contenthash' => $contenthash,
        'status' => 'completed',
    ]);

    echo html_writer::tag('p', html_writer::tag('strong', 'videolesson_subtitles table (status=completed):'));
    if (!empty($subtitle_records)) {
        echo html_writer::start_tag('table', ['class' => 'table table-sm table-bordered']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', 'ID');
        echo html_writer::tag('th', 'Language Code');
        echo html_writer::tag('th', 'Status');
        echo html_writer::tag('th', 'Time Created');
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');
        foreach ($subtitle_records as $record) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', $record->id);
            echo html_writer::tag('td', htmlspecialchars($record->language_code));
            echo html_writer::tag('td', htmlspecialchars($record->status));
            echo html_writer::tag('td', userdate($record->timecreated));
            echo html_writer::end_tag('tr');
        }
        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    } else {
        echo html_writer::div('No completed subtitle records found.', 'alert alert-info');
    }

    // Check videolesson_conv table for legacy subtitle field
    $conv_record = $DB->get_record('videolesson_conv', ['contenthash' => $contenthash]);
    echo html_writer::tag('p', html_writer::tag('strong', 'videolesson_conv table (legacy subtitle field):'));
    if ($conv_record) {
        echo html_writer::start_tag('table', ['class' => 'table table-sm table-bordered']);
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', 'Content Hash');
        echo html_writer::tag('td', htmlspecialchars($conv_record->contenthash));
        echo html_writer::end_tag('tr');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', 'Subtitle Field');
        echo html_writer::tag('td', htmlspecialchars($conv_record->subtitle ?? '(empty)'));
        echo html_writer::end_tag('tr');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', 'Uploaded Status');
        echo html_writer::tag('td', $conv_record->uploaded);
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('table');
    } else {
        echo html_writer::div('No record found in videolesson_conv table.', 'alert alert-warning');
    }

    // Show S3 listing attempt
    echo html_writer::tag('h6', '2. S3 Listing');
    echo html_writer::tag('p', html_writer::tag('strong', 'Prefix: ') . htmlspecialchars($contenthash . '/subtitles'));

    try {
        $s3output = new \mod_videolesson\aws_handler('output');
        $prefix = $contenthash . '/subtitles';
        $s3result = $s3output->list_objects($prefix);

        if (!empty($s3result['Contents'])) {
            echo html_writer::tag('p', html_writer::tag('strong', 'S3 Objects Found:'));
            echo html_writer::start_tag('table', ['class' => 'table table-sm table-bordered']);
            echo html_writer::start_tag('thead');
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', 'Key');
            echo html_writer::tag('th', 'Size');
            echo html_writer::tag('th', 'Last Modified');
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead');
            echo html_writer::start_tag('tbody');
            foreach ($s3result['Contents'] as $object) {
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', htmlspecialchars($object['Key']));
                echo html_writer::tag('td', number_format($object['Size'] ?? 0) . ' bytes');
                echo html_writer::tag('td', isset($object['LastModified']) ? userdate(strtotime($object['LastModified'])) : 'N/A');
                echo html_writer::end_tag('tr');
            }
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
        } else {
            echo html_writer::div('No S3 objects found at prefix: ' . htmlspecialchars($prefix), 'alert alert-info');
        }
    } catch (\Exception $e) {
        echo html_writer::div('Error listing S3 objects: ' . htmlspecialchars($e->getMessage()), 'alert alert-danger');
    }

    // Test the actual method
    echo html_writer::tag('h6', '3. Method Result');

    try {
        $start_time = microtime(true);
        $subtitles = $videosource->get_video_subtitles($contenthash);
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2);

        echo html_writer::tag('p', html_writer::tag('strong', 'Execution Time: ') . $execution_time . ' ms');
        echo html_writer::tag('p', html_writer::tag('strong', 'Number of Subtitles Found: ') . count($subtitles));

        if (!empty($subtitles)) {
            echo html_writer::start_tag('table', ['class' => 'table table-bordered']);
            echo html_writer::start_tag('thead');
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', 'Language Code');
            echo html_writer::tag('th', 'Language Name');
            echo html_writer::tag('th', 'Filename');
            echo html_writer::tag('th', 'URL');
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead');
            echo html_writer::start_tag('tbody');
            foreach ($subtitles as $subtitle) {
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', htmlspecialchars($subtitle['code'] ?? 'N/A'));
                echo html_writer::tag('td', htmlspecialchars($subtitle['language'] ?? 'N/A'));
                echo html_writer::tag('td', htmlspecialchars($subtitle['filename'] ?? 'N/A'));
                echo html_writer::tag('td', html_writer::tag('a', htmlspecialchars($subtitle['url'] ?? 'N/A'), [
                    'href' => $subtitle['url'] ?? '#',
                    'target' => '_blank',
                    'class' => 'text-break'
                ]));
                echo html_writer::end_tag('tr');
            }
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');

            echo html_writer::tag('p', html_writer::tag('strong', 'Raw Result:'));
            echo html_writer::tag('pre', htmlspecialchars(print_r($subtitles, true)), ['class' => 'bg-light p-3']);
        } else {
            echo html_writer::div('No subtitles found.', 'alert alert-warning');
            echo html_writer::tag('p', html_writer::tag('strong', 'Raw Result:'));
            echo html_writer::tag('pre', htmlspecialchars(print_r($subtitles, true)), ['class' => 'bg-light p-3']);
        }
    } catch (\Exception $e) {
        echo html_writer::div(
            html_writer::tag('strong', 'Exception: ') . htmlspecialchars($e->getMessage()) . '<br>' .
            html_writer::tag('small', 'Stack trace: <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>'),
            'alert alert-danger'
        );
    }

    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
