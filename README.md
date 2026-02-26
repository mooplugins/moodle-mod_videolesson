# moodle-mod_videolesson

# Video Lesson Activity Plugin for Moodle™

A modern video learning activity plugin built specifically for Moodle™ sites. Video Lesson is designed to help teachers teach and learners learn through **video-driven courses**.

The plugin supports uploading or embedding videos, organizing them in a central **Video Library**, enforcing **completion rules** (such as minimum watch percentage and disabling fast-forward), tracking **video analytics**, and streaming smoothly through AWS for scalable performance.

Educators can create structured video lessons, monitor learner engagement, and ensure compliance for training programs—all directly inside Moodle courses.

---

## Purpose

Moodle supports video files, but it does not provide:

- A dedicated video activity type

- Minimum watch percentage completion rules

- Engagement analytics (watch time, drop-offs, etc.)

- Seek/fast-forward control for compliance

- A centralized video library

- Built-in scalable video delivery workflows

Video Lesson Activity was created to solve these gaps in a clean, Moodle-integrated way.

---

## Key Features

**Dedicated Video Activity**

Add video lessons just like any other Moodle activity from Moodle activity chooser — no external dashboards.

**Video Library**

Organize, manage, and reuse videos across courses from a central, built-in library.

**Video Analytics**

Track:

- Watch time

- Completion percentage

- Total views

- Learner-level engagement data

**Video Completion Rule**

Require learners to watch a minimum percentage (e.g., 90%) before marking activity complete.

Optional seek/fast-forward control for compliance-driven training.

**Bulk Upload Videos**

Upload multiple videos at once; the plugin prepares them for streaming automatically.

**Multiple Video Sources**

- Direct uploads, or from Video library, 

- Self-hosted URLs,

- Youtube or Vimeo

**AWS Video Hosting**

Enable adaptive streaming, automatic transcoding, and scalable delivery using your own AWS infrastructure or MooPlugins hosting.

**Clean Video JS Player**

Distraction-free interface powered by VideoJS. Mobile-friendly and optimized for learning focus.

**TinyMCE Editor Button**

Insert videos inside labels, pages, or quizzes using the built-in editor button.

Read more: https://www.mooplugins.com/moodle-video-lesson-activity-plugin/

---

## Supported Video Sources

### 1. Direct Video Uploads (AWS-based) — Recommended

Video files are processed and delivered using Amazon Web Services:

- Storage: Amazon S3
- Transcoding: AWS MediaConvert

Here are the **Hosting Options**:

**Option 1 — Self-Managed**

Use your own AWS infrastructure:

- S3 (storage)

- MediaConvert (transcoding)

- CloudFront (delivery)

Recommended for advanced users comfortable managing AWS resources.

**Option 2 — Managed Hosting**

Optional hosting plans available via MooPlugins (https://www.mooplugins.com/moodle-video-lesson-activity-plugin/#hosting-plans)


### 2. External Links

- YouTube
- Vimeo

When using external platforms, Video Lesson can still enforce completion rules and track engagement within Moodle.

---

### Architecture Overview

- Fully integrated as a Moodle activity module

- Uses Moodle APIs and completion tracking system

- Video streaming handled via external storage (recommended: AWS S3)

- Transcoding supported through AWS MediaConvert

- No external SaaS dashboards required

---

## Requirements

- Moodle 4.4.12 or higher
- PHP version supported by your Moodle installation
- AWS account (for self-managed scalable hosting)

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

## Contributing

Community collaboration is welcome.

If you would like to:

- Report bugs

- Suggest improvements

- Contribute code

- Discuss architecture

Please open an issue in this repository.

---

## License

This plugin is licensed under the GNU GPL v3 or later.

See the LICENSE file for details.

---

## Trademark Notice

Moodle™ is a registered trademark of Moodle Pty Ltd. MooPlugins.com is not affiliated with or endorsed by Moodle Pty Ltd or any of its related entities.

---

## Learn More

**Live Demo (Student Experience):**

https://demo.mooplugins.com/

**Product Page:**

https://www.mooplugins.com/product/video-lesson-plugin/

**AWS Provisioning Guide:**

Provisioning AWS Infrastructure for Video Lesson Activity

https://www.mooplugins.com/docs/provisioning-aws-infrastructure-for-video-lesson-activity/

**Documentation Portal:**

https://www.mooplugins.com/docs/

**Support Portal:**

https://www.mooplugins.com/tickets/
