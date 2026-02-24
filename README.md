# moodle-mod_videolesson

# Video Lesson Activity for Moodle™

Video Lesson Activity is a native Moodle™ activity module that enables teachers to create structured, video-based lessons with completion tracking and engagement analytics.

It integrates seamlessly into Moodle and supports both AWS-based video delivery and external video platforms.

---

## Features

- Native Moodle activity type
- Minimum watch percentage requirement
- Optional seeking restrictions
- Engagement analytics (watch time, completion tracking)
- Support for multiple video source types
- Integration with Video Lesson filter and TinyMCE plugin

---

## Supported Video Sources

Video Lesson supports the following video source options:

### 1. Direct Video Uploads (AWS-based)

Video files are processed and delivered using Amazon Web Services:

- Storage: Amazon S3
- Transcoding: AWS MediaConvert

Deployment options:

- **Self-hosted** – Use your own AWS account and infrastructure.
- **MooPlugins-managed hosting** – AWS infrastructure provisioned and maintained by MooPlugins.

Both options provide adaptive streaming and scalable delivery.

### 2. External Links

The activity also supports external video platforms:

- YouTube
- Vimeo

When using supported external platforms, Video Lesson can still:

- Track learner engagement
- Enforce minimum watch percentage
- Apply completion rules within Moodle

---

## Requirements

- Moodle 4.1 or higher (adjust as needed)
- PHP version supported by your Moodle installation

For direct uploads:
- AWS S3
- AWS MediaConvert

---

## Installation

1. Download or clone this repository.
2. Place the folder into:

   /mod/videolesson

3. Visit **Site administration → Notifications** to complete installation.

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
