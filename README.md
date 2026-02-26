# moodle-mod_videolesson

# Video Lesson Activity for Moodle™

Video Lesson Activity is a native Moodle™ activity module that enables teachers to create structured, video-based lessons with completion tracking and engagement analytics.

It supports two types of video sources:
- Direct video uploads processed via AWS (recommended)
- External video links (YouTube / Vimeo)

---

## Features

- Native Moodle activity type
- Minimum watch percentage requirement
- Optional seeking restrictions
- Engagement analytics (watch time, completion tracking)
- Support for direct uploads (AWS) and external links (YouTube/Vimeo)
- Integration with Video Lesson filter and TinyMCE plugin

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
---

## Related Plugins

For extended functionality, install the companion plugins:

- `filter_videolesson` – Enables rendering of Video Lesson content inside text areas.
- `tiny_videolesson` – Adds an editor button to insert Video Lesson content.

---

## Versioning

Releases follow Moodle versioning conventions.

Example:
v1.0.0 – Initial public release

---

## License

This plugin is licensed under the GNU GPL v3 or later.

See the LICENSE file for details.

---

Moodle™ is a registered trademark of Moodle Pty Ltd.
This plugin is not affiliated with or endorsed by Moodle Pty Ltd.
