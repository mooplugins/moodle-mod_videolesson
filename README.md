# moodle-mod_videolesson

# Video Lesson Activity Plugin for Moodle™

Video Lesson is a Moodle activity designed to help teachers teach and learners learn through **video-driven courses**.

The plugin supports uploading or embedding videos, organizing them in a central **Video Library**, enforcing **completion rules** (such as minimum watch percentage and disabling fast-forward), tracking **video analytics**, and streaming smoothly through AWS for scalable performance.

Educators can create structured video lessons, monitor learner engagement, and ensure compliance for training programs—all directly inside Moodle courses.

---

## Key Features

**Native to Moodle**

Add video lessons just like any other Moodle activity — no external dashboards.

**Video Library**

Organize, manage, and reuse videos across courses from a central, built-in library.

**Video Analytics**

Track watch time, completion percentage, views, and learner-level insights.

**Video Completion Rule**

Require learners to watch a minimum percentage (e.g., 90%) before progressing.

**Bulk Upload Videos**

Upload multiple videos at once; the plugin prepares them for streaming automatically.

**Embed & External Video Support**

Embed videos from YouTube or Vimeo, or use any direct MP4 URL — no AWS setup required.

**AWS Video Hosting**

Enable adaptive streaming, automatic transcoding, and scalable delivery using your own AWS infrastructure or MooPlugins hosting.

**Clean Video JS Player**

A distraction-free VideoJS player optimized for clarity, accessibility, and learning.

**TinyMCE Editor Button**

Insert videos inside labels, pages, or quizzes using the built-in editor button.

Read more: https://www.mooplugins.com/moodle-video-lesson-activity-plugin/

---

## Supported Video Sources

### 1. Direct Video Uploads (AWS-based) — Recommended

Video files are processed and delivered using Amazon Web Services:

- Storage: Amazon S3
- Transcoding: AWS MediaConvert

### 2. External Links

- YouTube
- Vimeo

When using external platforms, Video Lesson can still enforce completion rules and track engagement within Moodle.

---

## Requirements

- Moodle 4.1 or higher
- PHP version supported by your Moodle installation

### Installation dependencies (required)

This plugin requires the following at installation time:

- `local_aws` plugin: https://moodle.org/plugins/local_aws
- Amazon AWS SDK for PHP

These dependencies are required to enable Direct Video Uploads (AWS S3 + MediaConvert).  
External links (YouTube/Vimeo) are supported, but the dependencies are still required to install the plugin.

---

## Installation

1. Install the dependency plugin:
   - `local_aws`: https://moodle.org/plugins/local_aws

2. Ensure the Amazon AWS SDK for PHP is available (as required by your AWS integration).

3. Download or clone this repository and place the folder into:
/mod/videolesson

4. Visit:
Site administration → Notifications
---

## Documentation

For installation, configuration, and usage guides, see:
https://www.mooplugins.com/docs-category/video-lesson-activity/

## Event Flow Documentation

https://github.com/mooplugins/moodle-mod_videolesson/blob/main/EVENT_FLOW_DOCUMENTATION.md

---

## Related Plugins

For extended functionality, install the companion plugins:

- `filter_videolesson` – Enables rendering of Video Lesson content inside text areas.
- `tiny_videolesson` – Adds an editor button to insert Video Lesson content.

---

## Upgrade & Compatibility Notes

https://github.com/mooplugins/moodle-mod_videolesson/blob/main/upgrade_comp.md

---

## License

This plugin is licensed under the GNU GPL v3 or later.

See the LICENSE file for details.

---

Moodle™ is a registered trademark of Moodle Pty Ltd.
This plugin is not affiliated with or endorsed by Moodle Pty Ltd.
