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
 * Videos table
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/mod/videolesson/classes/util.php');
class videos_table extends table_sql {
    public $totalinstance = 0;
    public $vconfig = [];
    public $items = [];
    public $cloudfrontdomain;
    public $classconversion;
    public $prefixes;

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    public function __construct($uniqueid, $items) {
        $awshandler = new \mod_videolesson\aws_handler('output');
        $this->classconversion = new \mod_videolesson\conversion();
        $this->cloudfrontdomain = $awshandler->cloudfrontdomain();
        $this->prefixes = array_flip($awshandler->list_all_prefixes_array());

        $this->items = $items;
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = [
            'name',
            'folder',
            'status',
            'instances',
            'size',
            'source_size',
            'timecreated',
            'action',
        ];
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headerstrings = get_strings(
            [
                'col_vid_title',
                'col_folder',
                'col_vid_status',
                'col_vid_instances',
                'col_vid_size',
                'col_vid_source_size',
                'col_timecreated',
                'col_timemodified',
                'col_action',
            ], 'mod_videolesson'
        );
        $headers = [
            $headerstrings->col_vid_title,
            $headerstrings->col_folder,
            $headerstrings->col_vid_status,
            $headerstrings->col_vid_instances,
            $headerstrings->col_vid_size,
            $headerstrings->col_vid_source_size,
            $headerstrings->col_timecreated,
            $headerstrings->col_action,
        ];
        $this->define_headers($headers);
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('action');
        $this->no_sorting('folder');
        $this->pageable(true);
    }

    public function get_sort_columns() {
        $sortcolumns = parent::get_sort_columns();
        return $sortcolumns;
    }

    public function col_name($log) {
        global $OUTPUT;
        $nothumb = new moodle_url('/mod/videolesson/pix/monologo.svg');
        $thumbnail = '';

        if ($log->transcoder_status == $this->classconversion::CONVERSION_ERROR) {
            $thumbnail = \html_writer::tag('span', $OUTPUT->pix_icon('i/error', 'Error', 'mod_videolesson', []), []);
        } else {
            $src = $this->items[$log->contenthash]['thumbnail'] ?? $nothumb->out();
            $style = 'width: 120px;height: auto;';
            $thumbnail = \html_writer::tag(
                'img',
                '',
                [
                    'src' => $src,'class' => 'rounded my-2', 'style' => $style,
                    'alt' => 'No thumbnail','onerror' => "this.src='".$nothumb->out()."'"
                ]
            );
        }

        $videoname = new \mod_videolesson\output\videoname($log);
        return $thumbnail . '<br>'. $videoname->render($OUTPUT);
    }

    public function col_status($log) {
        // Combine uploaded and transcoder status into a single status field
        // Logic: pending (waiting for upload) -> uploaded (uploaded but not transcoded) -> transcoder status
        $status = '';
        $badge = '';

        if ($log->uploaded != $this->classconversion::CONVERSION_FINISHED) {
            // Not uploaded yet - show pending/error status
            $status = get_string('upload:status:' . $log->uploaded, 'mod_videolesson');
            if ($log->uploaded == $this->classconversion::CONVERSION_UPLOAD_ERROR || $log->uploaded == 500) {
                $badge = 'danger';
            } else {
                $badge = 'warning';
            }
        } else if (!empty($log->transcoder_status) && $log->transcoder_status != null) {
            // Uploaded and has transcoder status - show transcoder status
            $status = get_string('transcoding:status:' . $log->transcoder_status, 'mod_videolesson');
            if ($log->transcoder_status == $this->classconversion::CONVERSION_IN_PROGRESS) {
                $badge = 'info';
            } else if ($log->transcoder_status == $this->classconversion::CONVERSION_ACCEPTED) {
                $badge = 'warning';
            } else if ($log->transcoder_status == $this->classconversion::CONVERSION_FINISHED) {
                $badge = 'success';
            } else if (in_array($log->transcoder_status, [$this->classconversion::CONVERSION_NOT_FOUND, $this->classconversion::CONVERSION_ERROR])) {
                $badge = 'danger';
            }
        } else {
            // Uploaded but no transcoder status yet - show uploaded
            $status = get_string('status:uploaded', 'mod_videolesson');
            $badge = 'info';
        }

        if ($badge) {
            return \html_writer::span($status, 'badge badge-' . $badge);
        }
        return $status;
    }

    public function col_instances($log) {
        if ($log->instances) {
            $url = new moodle_url('/mod/videolesson/library.php', [
                'action' => 'instances',
                'contenthash' => $log->contenthash,
            ]);

            return html_writer::link($url, $log->instances, ['target' => '_blank']);
        } else {
            return '';
        }
    }

    public function col_timemodified($log) {
        return userdate($log->timemodified);
    }

    public function col_timecreated($log) {
        return userdate($log->timecreated);
    }

    public function col_size($log){
        return \mod_videolesson\util::formatBytes($log->bucket_size);
    }

    public function col_folder($log) {
        global $DB;

        if (isset($log->folderid) && $log->folderid) {
            $folder = $DB->get_record('videolesson_folders', ['id' => $log->folderid]);
            if ($folder) {
                return html_writer::span($folder->name, 'videolesson-folder-name');
            }
        }
        return html_writer::span(get_string('folder:uncategorized', 'mod_videolesson'), 'videolesson-folder-name text-muted');
    }

    public function other_cols($colname, $value) {
        global $OUTPUT, $DB;

        if ($colname == 'action') {
            $action = '';
            if (has_capability('mod/videolesson:manage', context_system::instance())) {

                $inbucket = isset($this->prefixes[$value->contenthash]);

                if ($value->transcoder_status == $this->classconversion::CONVERSION_FINISHED && $inbucket) {
                    // Manual. no need to call aws just to check to avoid multiple request. we will just assume that it exists.
                    $src = $this->cloudfrontdomain . $value->contenthash;
                    if ($value->mediaconvert) {
                        $src .= '/conversions/'.$value->contenthash.'.m3u8';
                    } else {
                        $src .= '/conversions/'.$value->contenthash.'_hls_playlist.m3u8';
                    }

                    $viewurl = '#';
                    $attr = [
                        'class' => 'videolesson-viewmodal-data',
                        'data-videolesson-src' => $src,
                        'data-videolesson-contenthash' => $value->contenthash
                    ];

                    $action .= $OUTPUT->action_icon($viewurl, new pix_icon('i/play', get_string('view'), 'mod_videolesson', $attr), null , $attr);

                    if ($value->hasmp4) {
                        $src = "{$this->cloudfrontdomain}{$value->contenthash}/mp4/{$value->contenthash}.mp4";
                        $attr = ['target' => '_blank'];
                        $action .= $OUTPUT->action_icon(
                            $src, new pix_icon('i/mp4', get_string('manage:video:mp4', 'mod_videolesson'), 'mod_videolesson', []),null , $attr);
                    }

                }

                if ($value->status == $this->classconversion::CONVERSION_FINISHED && !$inbucket) {
                    $viewurl = '#';
                    $attr = ['class' => 'text-danger'];
                    $action .= $OUTPUT->action_icon($viewurl, new pix_icon('i/missing', get_string('bucket:missing', 'mod_videolesson'), 'mod_videolesson'), null , $attr);
                }

                $total = $DB->count_records('videolesson' , [
                    'sourcedata' => $value->contenthash
                ]);

                if ($value->uploaded == $this->classconversion::CONVERSION_UPLOAD_ERROR) {
                    $retryurl = new \moodle_url(
                        '/mod/videolesson/library.php',
                        [
                            'action' => 'retry',
                            'contenthash' => $value->contenthash,
                            'sesskey' => sesskey()
                        ]
                    );

                    $retryurlaction = new confirm_action(
                        get_string('manage:video:upload:retry:confirm', 'mod_videolesson', $value->name)
                    );

                    $action .= $OUTPUT->action_icon(
                        $retryurl,
                        new pix_icon(
                            'i/retry',
                            get_string('manage:video:upload:retry','mod_videolesson'),
                            'mod_videolesson',
                            ['class' => 'text-warning']
                        ),
                        $retryurlaction
                    );
                }

                $reporturl = new \moodle_url(
                    '/mod/videolesson/report.php',
                    ['action' => 'video', 'contenthash' => $value->contenthash, 'sesskey' => sesskey()]);
                $attr = [];
                $action .= $OUTPUT->action_icon($reporturl, new pix_icon('i/chart', get_string('report:view', 'mod_videolesson'), 'mod_videolesson', $attr), null , $attr);

                $candelete = (
                    !$total ||
                    ($value->status == $this->classconversion::CONVERSION_FINISHED &&
                    $value->transcoder_status != $this->classconversion::CONVERSION_FINISHED)
                );

                if ($candelete) {
                    // Get video ID from videolesson_conv table for consistent deletion flow
                    $videorecord = $DB->get_record('videolesson_conv', ['contenthash' => $value->contenthash]);
                    $videoid = $videorecord ? $videorecord->id : null;

                    if ($videoid) {
                        // Use AJAX-based delete similar to bulk delete flow
                        // Create a link that JavaScript will intercept
                        $deleteurl = '#';
                        $attr = [
                            'class' => 'videolesson-delete-link text-danger',
                            'data-video-id' => $videoid,
                            'data-contenthash' => $value->contenthash,
                            'data-video-name' => format_string($value->name),
                            'href' => '#',
                            'title' => get_string('delete')
                        ];

                        $action .= html_writer::link(
                            $deleteurl,
                            $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', ['class' => 'text-danger']),
                            $attr
                        );
                    } else {
                        // Fallback to URL-based delete if video ID not found
                        $deleteurl = new \moodle_url(
                            '/mod/videolesson/library.php',
                            [
                                'action' => 'delete',
                                'contenthash' => $value->contenthash,
                                'sesskey' => sesskey()
                            ]
                        );

                        $deleteaction = new confirm_action(
                            get_string('manage:video:delete:confirm', 'mod_videolesson', $value->name)
                        );

                        $action .= $OUTPUT->action_icon(
                            $deleteurl,
                            new pix_icon(
                                't/delete',
                                get_string('delete'),
                                'moodle',
                                ['class' => 'text-danger']
                            ),
                            $deleteaction
                        );
                    }
                }
            }

            return $action;
        } else if ($colname == 'source_size' ) {
            $record = $DB->get_record('videolesson_data', ['contenthash' => $value->contenthash]);
            if ($record) {
                return \mod_videolesson\util::formatBytes($record->size);
            }
            return '';
        }
    }

    function wrap_html_start() {
        $totalcount = $this->totalrows;
        echo html_writer::tag(
            'div',
            get_string('manage:videos:total', 'mod_videolesson', $totalcount),
            ['class' => 'totalresultsfooter']
        );
        parent::wrap_html_start();
    }

    /**
     * Generate HTML for the start of the row
     *
     * @param array|object $row
     * @return string HTML
     */
    public function start_row($row) {
        $attributes = [];
        if (isset($row->id)) {
            $attributes['data-videolesson-id'] = $row->id;
            $attributes['draggable'] = 'true';
            $attributes['class'] = 'videolesson-draggable-row';
        }
        return html_writer::start_tag('tr', $attributes);
    }
}
