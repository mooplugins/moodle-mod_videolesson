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

Use the optional `tiny_videolesson` companion plugin to insert Video Lesson content inside labels, pages, descriptions, and other TinyMCE-supported areas.

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

- Moodle 4.4.12 or later
- PHP version supported by your Moodle version
- AWS S3 and AWS MediaConvert credentials are required only when using uploaded video storage, transcoding, or adaptive streaming workflows

## AWS SDK

VideoLesson uses the AWS SDK bundled with Moodle core. No separate `local_aws` plugin or external AWS SDK installation is required.

---

## Installation

### Install from the Moodle plugins directory

1. Log in to your Moodle site as an administrator.
2. Go to **Site administration → Plugins → Install plugins**.
3. Search for **Video Lesson Activity** in the Moodle plugins directory.
4. Click **Install** and follow the on-screen validation steps.
5. Complete the Moodle upgrade process when prompted.
6. Go to **Site administration → Plugins → Activity modules → Video Lesson Activity** and configure the plugin settings.

### Manual installation

1. Download the plugin ZIP file.
2. Extract the ZIP file.
3. Rename the extracted folder to `videolesson` if needed.
4. Copy the folder to: /path/to/moodle/mod/videolesson

---

## Documentation

For installation, configuration, and usage guides, see:
https://www.mooplugins.com/docs-category/video-lesson-activity/

## Event Flow Documentation

https://github.com/mooplugins/moodle-mod_videolesson/blob/main/EVENT_FLOW_DOCUMENTATION.md

---

## Related plugins

Video Lesson Activity can be used on its own as a Moodle activity module. For authoring and embedding Video Lesson content in other Moodle text areas, you can also install the companion plugins below.

| Plugin                                           | Purpose                                                                                                                           | GitHub                                                            | Moodle plugins directory                                                  |
| ------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------- | ------------------------------------------------------------------------- |
| Video Lesson Activity (`mod_videolesson`)        | Main Moodle activity module for creating video lessons, tracking progress, enforcing completion, and viewing analytics.           | [GitHub](https://github.com/mooplugins/moodle-mod_videolesson)    | [Moodle plugins directory](https://moodle.org/plugins/mod_videolesson)    |
| Video Lesson Filter (`filter_videolesson`)       | Renders embedded Video Lesson content inside Moodle text areas such as labels, course sections, pages, and activity descriptions. | [GitHub](https://github.com/mooplugins/moodle-filter_videolesson) | [Moodle plugins directory](https://moodle.org/plugins/filter_videolesson) |
| Video Lesson TinyMCE Button (`tiny_videolesson`) | Adds a TinyMCE editor button so teachers can insert Video Lesson content into Moodle text areas.                                  | [GitHub](https://github.com/mooplugins/moodle-tiny_videolesson)   | [Moodle plugins directory](https://moodle.org/plugins/tiny_videolesson)   |

### Recommended installation order

1. Install **Video Lesson Activity** (`mod_videolesson`).
2. Install **Video Lesson Filter** (`filter_videolesson`) if you want embedded Video Lesson content to render inside Moodle text areas.
3. Install **Video Lesson TinyMCE Button** (`tiny_videolesson`) if you want teachers to insert Video Lesson content directly from the TinyMCE editor.


---

## Upgrade & Compatibility Notes

https://github.com/mooplugins/moodle-mod_videolesson/blob/main/upgrade_comp.md

---

## Release notes

### Version 1.0.3

Maintenance release removing an unused third-party dependency.

Changes:
* Removed the Bowser dependency and implemented a lightweight user-agent parsing function directly in the plugin's main JavaScript to collect platform, browser, and OS information for player analytics.

### Version 1.0.2
* Updated compatibility information for Moodle 4.4 or later.

### Version 1.0.1

Maintenance release with Moodle plugins directory review fixes and documentation updates.

Changes:

* Updated README documentation and related plugin links.
* Clarified companion plugin usage for `filter_videolesson` and `tiny_videolesson`.
* Clarified installation requirements.
* Confirmed that no separate `local_aws` plugin or external AWS SDK installation is required.
* Included fixes requested during the Moodle plugins directory review process.

### Version 1.0.0

Initial public release of Video Lesson Activity for Moodle.

Included features:

* Added Video Lesson activity module for Moodle courses.
* Added watch-percentage based video completion tracking.
* Added seeking behavior controls for video lessons.
* Added student video progress tracking.
* Added video engagement analytics and reporting.
* Added reusable Video Library support.
* Added support for uploaded videos, Video Library videos, YouTube, Vimeo, and direct video URLs depending on site configuration.
* Added AWS S3 and AWS MediaConvert support for uploaded video storage, transcoding, and adaptive streaming.
* Added site-level settings for default completion progress and video behavior controls.

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

https://www.mooplugins.com/moodle-video-lesson-activity-plugin/

**AWS Provisioning Guide:**

Provisioning AWS Infrastructure for Video Lesson Activity

https://www.mooplugins.com/docs/provisioning-aws-infrastructure-for-video-lesson-activity/

**Documentation Portal:**

https://www.mooplugins.com/docs/

**Support Portal:**

https://www.mooplugins.com/tickets/
