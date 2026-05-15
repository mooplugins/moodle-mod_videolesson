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
 * The main mod_videolesson configuration form.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/videolesson/locallib.php');

/**
 * Module instance settings form.
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videolesson_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $OUTPUT, $PAGE;
        $mform = $this->_form;
        $cm = $this->_cm;
        $access = new \mod_videolesson\access();
        $formjsparams = ['restrict' => $access->restrict()];

        $PAGE->requires->js_call_amd('mod_videolesson/form', 'init', [$formjsparams]);

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'srcfieldset', get_string('modform:header', 'mod_videolesson'));
        $mform->setExpanded('srcfieldset');

        $mform->closeHeaderBefore('buttonar');
        $srcopts = videolesson_sources_options();
        $mform->addElement('select', 'source', get_string('modform:source', 'mod_videolesson'), $srcopts, []);

        if ($access->restrict()) {
            $msg = $access->get_message();
            $mform->addElement('hidden', 'noconfig', 'yes');
            $mform->addElement('static', 'missingconfig', '', $msg);
        }

        $currentmaxbytes = (int) get_config('moodlecourse', 'maxbytes');
        if ($currentmaxbytes === 0) {
            if (isset($CFG->maxbytes)) {
                $choices = get_max_upload_sizes($CFG->maxbytes, 0, 0, 0);
            } else {
                $choices = get_max_upload_sizes(0, 0, 0, 0);
            }
            $currentmaxbytes = (int) max(array_keys($choices));
        }
        // Hide upload and gallery options for external hosting type.
        $hostingtype = get_config('mod_videolesson', 'hosting_type');
        if ($hostingtype !== 'none') {
            $fmrestrictions = get_string('maxsize', 'moodle', display_size($currentmaxbytes));
            $mform->addElement('static', 'fprestriction', '', $fmrestrictions);

            $ffprobe = new \mod_videolesson\ffprobe(false);
            if (!$ffprobe->is_valid_path() && in_array($hostingtype, ['hosted', 'self'])) {
                $ffprobeerrortext = get_string(
                    'error:upload:video:ffprobe:notset',
                    'mod_videolesson',
                    $CFG->wwwroot . '/admin/settings.php?section=modsettingvideolesson#id_s_mod_videolesson_pathtoffprobe'
                );

                $ffprobeelement = $OUTPUT->notification(
                    $ffprobeerrortext, \core\output\notification::NOTIFY_ERROR, false);

                $mform->addElement('static', 'ffprobeerrormessage', '', $ffprobeelement);
            } else {
                // Upload new.
                $mform->addElement(
                    'filepicker',
                    'newvideo',
                    get_string('modform:uploadnew', 'mod_videolesson'),
                    null,
                    ['maxbytes' => $currentmaxbytes, 'accepted_types' => ['.mp4', '.ts', '.webm', '.flv', '.avi', '.mpeg', '.mov']]
                );
            }
        }

        $defaultsubtitle = !empty(get_config('mod_videolesson', 'createsubtitle')) ? 1 : 0;
        $mform->addElement('checkbox', 'subtitle', '', get_string('modform:subtitle', 'mod_videolesson'));
        $mform->setDefault('subtitle', $defaultsubtitle);
        $mform->setType('subtitle', PARAM_BOOL);

        $selected = null;
        $disableseek = $disablespeed = $disablepip = 0;
        if (isset($cm->id) && $cm->id) {
            $activity = new \mod_videolesson\activity($cm->id);
            $mod = $activity->moduleinstance;

            if (!$activity->is_video_ready() && !$activity->no_video_data()) {
                $mform->addElement('html', get_string('modform:upload:processing', 'mod_videolesson'));
                $mform->addElement('html',
                    '<style>
                    #video_gallery_container,#fitem_id_source,#fitem_id_newvideo{
                    display:none !important;}
                    </style>'
                );
            }

            if ($mod->source == MOD_VIDEOLESSON_SRC_GALLERY) {
                $selected = $mod->sourcedata;
                $mform->setDefault('contenthash', $selected);
            } else if ($mod->source == MOD_VIDEOLESSON_SRC_EXTERNAL) {
                $sourcedata = $mod->sourcedata;

                // Check if sourcedata is in normalized format (e.g., "youtube:VIDEO_ID").
                if (preg_match('/^(youtube|vimeo):([a-zA-Z0-9_-]+)$/i', $sourcedata, $matches)) {
                    $externaltype = strtolower($matches[1]);
                    $externalvideoid = $matches[2];

                    // Reconstruct URL for editing.
                    if ($externaltype === 'youtube') {
                        $videourl = 'https://www.youtube.com/watch?v=' . $externalvideoid;
                    } else if ($externaltype === 'vimeo') {
                        $videourl = 'https://vimeo.com/' . $externalvideoid;
                    } else {
                        $videourl = $sourcedata;
                    }
                } else {
                    $videourl = $sourcedata;
                }

                $mform->setDefault('videourl', $videourl);
            }

            if ($mod->options) {
                $options = json_decode($mod->options);
                $disableseek = $options->player->seek;
                $disablespeed = $options->player->disablespeed;
                $disablepip = $options->player->disablepip;
            }
        }

        if ($hostingtype !== 'none') {
            // Gallery.
            $videosource = new \mod_videolesson\videosource();
            $items = $videosource->get_items($selected, false, false);
            $selectedvideo = false;
            if (isset($items[$selected])) {
                $selectedvideo = $items[$selected];
                unset($items[$selected]);
            }
            $items = array_values($items);
        } else {
            $items = [];
        }

        $templateparams = [
            'videos' => $items,
            'selectedvideo' => $selectedvideo,
            'border' => true,
            'librarylink' => true,
        ];

        $gallery = $OUTPUT->render_from_template('mod_videolesson/form_video_gallery', $templateparams);
        $mform->addElement('html', $gallery);
        $mform->addElement('text', 'contenthash', '', ['class' => 'd-none']);
        $mform->setType('contenthash', PARAM_TEXT);

        // Thumbnail.
        $mform->addElement('checkbox', 'addthumbnail', get_string('modform:addthumbnail', 'mod_videolesson'));
        $mform->addElement(
            'filepicker',
            'thumbnail',
            get_string('modform:uploadthumbnail', 'mod_videolesson'),
            null,
            ['maxbytes' => 4 * 1024 * 1024, 'accepted_types' => ['.jpg', '.png']]
        );
        $mform->hideIf('thumbnail', 'addthumbnail', 'notchecked');

        // URL field for youtube, vimeo, external link, or embed code.
        $mform->addElement(
            'textarea',
            'videourl',
            get_string('modform:videourl', 'mod_videolesson'),
            ['cols' => '40', 'rows' => '4', 'wrap' => 'virtual']
        );
        $mform->setType('videourl', PARAM_TEXT);

        $options = [
            0 => get_string('modform:allowseek', 'mod_videolesson'),
            1 => get_string('modform:disableseek', 'mod_videolesson'),
            2 => get_string('modform:disableseekrewind', 'mod_videolesson'),
        ];

        $overrides = '';
        if ($overrideseek = get_config('mod_videolesson', 'overrideseekbehavior')) {
            $overrides .= get_string('modform:overrideseek' . $overrideseek, 'mod_videolesson');
        }

        if (get_config('mod_videolesson', 'overridedisablespeed')) {
            $overrides .= get_string('modform:overridedisablespeed', 'mod_videolesson');
        }

        if (get_config('mod_videolesson', 'overridedisablepip')) {
            $overrides .= get_string('modform:overridedisablepip', 'mod_videolesson');
        }

        if (!empty($overrides)) {
            $settingsurl = new \moodle_url('/admin/category.php', ['category' => 'modvideolessonfolder']);
            $msg = $OUTPUT->notification(
                get_string('modform:overridewarning', 'mod_videolesson', ['url' => $settingsurl->out(), 'items' => $overrides]),
                \core\output\notification::NOTIFY_WARNING,
                false
            );
            $mform->addElement('static', 'overrideseek', '', $msg);
        }

        $mform->addElement('select', 'disableseek', get_string('modform:seekoptions', 'mod_videolesson'), $options);
        $mform->setDefault('disableseek', $disableseek);
        $mform->setType('disableseek', PARAM_INT);
        $mform->addHelpButton('disableseek', 'modform:seekoptions', 'mod_videolesson');

        $mform->addElement('checkbox', 'disablespeed', get_string('modform:disablespeed', 'mod_videolesson'));
        $mform->setDefault('disablespeed', $disablespeed);
        $mform->setType('disablespeed', PARAM_INT);
        $mform->addHelpButton('disablespeed', 'modform:disablespeed', 'mod_videolesson');

        $mform->addElement('checkbox', 'disablepip', get_string('modform:disablepip', 'mod_videolesson'));
        $mform->setDefault('disablepip', $disablepip);
        $mform->setType('disablepip', PARAM_INT);
        $mform->addHelpButton('disablepip', 'modform:disablepip', 'mod_videolesson');

        // Checkboxes hide, other field hide will be taken care of the custom js.
        $mform->hideIf('addthumbnail', 'source', 'eq', MOD_VIDEOLESSON_SRC_EXTERNAL);
        $mform->hideIf('subtitle', 'source', 'neq', MOD_VIDEOLESSON_SRC_UPLOAD);
        $mform->hideIf('fprestriction', 'source', 'neq', MOD_VIDEOLESSON_SRC_UPLOAD);

        // Hide gallery/upload for external hosting or if restricted.
        $hostingtype = get_config('mod_videolesson', 'hosting_type');
        if ($access->restrict_modform_elements() || $hostingtype === 'none') {
            $elements = ['newvideo', 'contenthash', 'thumbnail', 'disableseek', 'addthumbnail', 'subtitle'];

            foreach ($elements as $element) {
                $mform->disabledIf($element, 'noconfig', 'eq', 'yes');
                $mform->hideIf($element, 'noconfig', 'eq', 'yes');
            }
            $mform->addElement('html', '<style> #video_gallery_container,#fitem_id_newvideo{display:none !important;}</style>');
        }

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Preprocess the form data.
     *
     * @param array $defaultvalues Default values
     */
    public function data_preprocessing(&$defaultvalues) {

        $draftitemid = file_get_submitted_draft_itemid('thumbnail');
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_videolesson', 'thumbnail', 0);
        $defaultvalues['thumbnail'] = $draftitemid;

        if ($defaultvalues['thumbnail']) {
            $defaultvalues['addthumbnail'] = true;
        }

        $defaultprogress = get_config('mod_videolesson', 'completionprogress');
        if (empty($defaultvalues['completionprogress'])) {
            $defaultvalues['completionprogress'] = $defaultprogress;
        }

        $defaultvalues['completionprogressenabled'] = $defaultvalues['completionprogress'] !== null;

        // Enforce disable seek threshold for existing activities.
        $threshold = get_config('mod_videolesson', 'completionprogress_force_disable_seek_threshold');
        $threshold = $threshold ? (int)$threshold : 0;
        $overrideseek = get_config('mod_videolesson', 'overrideseekbehavior');

        // Only enforce if threshold is enabled, no global override, and completion is set.
        if ($threshold > 0 && $overrideseek == 0 && !empty($defaultvalues['completionprogress'])) {
            $completionprogress = (int)$defaultvalues['completionprogress'];
            if ($completionprogress >= $threshold) {
                // Force disable seek if not already disabled.
                if (empty($defaultvalues['disableseek']) || $defaultvalues['disableseek'] == 0) {
                    $defaultvalues['disableseek'] = 1; // Force disable seek.
                }
            }
        }
    }

    /**
     * Validate the form data.
     *
     * @param array $data Input data (not yet validated)
     * @param array $files Input files (not yet validated)
     * @return array Array of error messages, empty array if no errors
     */
    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);
        $access = new \mod_videolesson\access();

        switch ($data['source']) {
            case MOD_VIDEOLESSON_SRC_GALLERY:
                if ($access->restrict_modform_elements()) {
                    $errors['source'] = get_string('modform:error:source:restricted', 'mod_videolesson');
                    break;
                }

                if (empty($data['contenthash'])) {
                    $errors['source'] = get_string('modform:error:source', 'mod_videolesson');
                }

                if ($data['addthumbnail'] && !$data['thumbnail']) {
                    $errors['thumbnail'] = get_string('modform:error:thumbnail', 'mod_videolesson');
                }

                break;

            case MOD_VIDEOLESSON_SRC_EXTERNAL:
                if (empty($data['videourl'])) {
                    $errors['videourl'] = get_string('modform:error:videourl', 'mod_videolesson');
                } else {
                    $input = trim($data['videourl']);

                    // Try to extract URL from embed code if it looks like embed code.
                    $url = \mod_videolesson\util::extract_url_from_embed_code($input);
                    if (!$url) {
                        $url = $input;
                    }

                    // Validate: must be a video URL (direct file, YouTube, or Vimeo) or embed code.
                    $isvalid = \mod_videolesson\util::is_video_url($url) ||
                               \mod_videolesson\util::is_youtube_url($url) ||
                               \mod_videolesson\util::is_vimeo_url($url) ||
                               \mod_videolesson\util::is_youtube_embed_url($url) ||
                               \mod_videolesson\util::is_vimeo_embed_url($url) ||
                               \mod_videolesson\util::is_embed_code($input);

                    if (!$isvalid) {
                        $errors['videourl'] = get_string('modform:error:videourl:invalid', 'mod_videolesson');
                    }
                }

                break;
            default: // MOD_VIDEOLESSON_SRC_UPLOAD.
                if ($access->restrict_modform_elements()) {
                    $errors['source'] = get_string('modform:error:source:restricted', 'mod_videolesson');
                    break;
                }

                $usercontext = \context_user::instance($USER->id);
                $fs = get_file_storage();
                $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['newvideo'], 'id', false);
                if (!$draftfiles) {
                    $errors['newvideo'] = get_string('required');
                } else {
                    $file = reset($draftfiles);
                    $awshandler = new \mod_videolesson\aws_handler('output');
                    $existingprefixes = $awshandler->list_all_prefixes_array(); // All in the bucket.

                    if (in_array($file->get_contenthash(), $existingprefixes)) {
                        $videosource = new \mod_videolesson\videosource();
                        $title = $videosource->get_video_title($file->get_contenthash());
                        $src = $file->get_contenthash();

                        $viewurl = new \moodle_url(
                            '/mod/videolesson/library.php',
                            ['action' => 'view', 'src' => $src, 'title' => $title]
                        );

                        $errnewvideo = get_string('error:video:exists', 'mod_videolesson', $file->get_filename());
                        $errnewvideo .= ' <a href="' . $viewurl->out() . '" target="_blank">View</a>';

                        $errors['newvideo'] = $errnewvideo;

                        \core\notification::add($errors['newvideo'], \core\output\notification::NOTIFY_ERROR);
                    } else {
                        $ffprobe = videolesson_validation_ffprobe($file);
                        if ($ffprobe['error'] == true) {
                            $errors['newvideo'] = $ffprobe['reason'];
                        }
                    }
                }

                break;
        }

        // Enforce disable seek threshold when completion percentage meets threshold.
        $threshold = get_config('mod_videolesson', 'completionprogress_force_disable_seek_threshold');
        $threshold = $threshold ? (int)$threshold : 0;
        $overrideseek = get_config('mod_videolesson', 'overrideseekbehavior');

        // Only enforce if threshold is enabled, no global override, and completion is enabled.
        if ($threshold > 0 && $overrideseek == 0) {
            $suffix = $this->get_suffix();
            $completionenabled = !empty($data['completionprogressenabled' . $suffix]);

            if ($completionenabled) {
                $completionprogress = (int)($data['completionprogress' . $suffix] ?? 0);

                if ($completionprogress >= $threshold) {
                    // Force disable seek if not already disabled.
                    if (empty($data['disableseek']) || $data['disableseek'] == 0) {
                        $data['disableseek'] = 1; // Force disable seek.
                        // Add notification explaining the auto-enforcement.
                        \core\notification::add(
                            get_string('modform:completion:seek_auto_enforced', 'mod_videolesson', $threshold),
                            \core\output\notification::NOTIFY_INFO
                        );
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Add completion rules to the form.
     *
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $opts = [];
        foreach (range(1, 100) as $i) {
            $opts[$i] = $i . '%';
        }

        $group = [];
        $group[] = $mform->createElement(
            'checkbox',
            'completionprogressenabled',
            '',
            get_string('modform:completion:progress', 'mod_videolesson')
        );
        $group[] = $mform->createElement('select', 'completionprogress', 'Percentage', $opts, ['class' => 'ml-2']);
        $mform->setType('completionprogress', PARAM_INT);

        $groupname = $this->get_suffixed_name('completionprogressgroup');

        $mform->addGroup($group, $groupname, 'Require watch progress %', [' '], false);
        $mform->disabledIf('completionprogress', 'completionprogressenabled', 'notchecked');

        return [$groupname];
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        return !empty($data['completionprogressenabled' . $suffix]);
    }

        /**
         * Get the suffix of name.
         *
         * @param string $fieldname The field name of the completion element.
         * @return string The suffixed name.
         */
    protected function get_suffixed_name(string $fieldname): string {
        return $fieldname . $this->get_suffix();
    }
}
