# Changelog

All notable changes to the Video Lesson Activity plugin are documented here.

## 1.0.5 - 2026-11-11

### Added

- Library card view, with a switch back to the existing list. Each card shows
  the thumbnail, date, title, folder or course, views, completion, watch time,
  and instances. A status badge is shown on the thumbnail while the video is
  not yet transcoded.
- Report charts drawn with Moodle's Charts API: views across the video
  timeline, platforms, operating systems, browsers, and countries. Each tab
  has a heading, short description, and colour key.
- Impressions, plays, and single-session playback pages rebuilt as cards.
  Playback activity includes a video-time timeline and separate lists for
  playback, seeks, visibility, and media changes.
- `SECURITY.md` describing how to report a vulnerability.

### Changed

- The views chart groups watch coverage into time buckets instead of plotting
  one point per second. A distribution with a single category is a summary
  card rather than a chart.
- Playback, seek, visibility, volume, and quality lists show the first rows,
  then **View all events** / **Show less**.
- Uploaded video types are limited to mp4, m4v, webm, mkv, and m3u8.
- Usage and progress lookups gained indexes for watch-time and completion
  queries (#27).

### Fixed

- Seek From and To no longer show the landing position twice. New sessions
  store the origin, and older sessions rebuild the jump from the playhead.
- Changing the video no longer confirms when the dialog is dismissed by
  clicking outside it.
- Server-side requests to arbitrary user-supplied video URLs are rejected
  (#32).
- Stored playback fields are escaped before they are rendered (#33).
- Completion progress is calculated on the server instead of trusting the
  client total (#34).
- Playback reports only include records for the requested activity (#35).
- Thumbnail files require module access (#36).
- The Privacy API declares external recipients and the fields sent to them
  (#37).
- Removing a Video Lesson deletes its telemetry and media records (#38).
- Playback analytics have a retention and deletion policy (#39).
- Emailed conversion error logs are limited, and hosted sites keep their
  existing recipient (#40).

## 1.0.4 - 2026-09-04

### Added

- BigBlueButton recordings in the Video Library, including a recordings
  folder, publish state, and moving recordings between folders.

## 1.0.3 - 2026-06-30

### Changed

- Removed the Bowser dependency. Platform, browser, and operating system for
  player analytics are read by a small user-agent parser in the player script.

## 1.0.2 - 2026-06-29

### Changed

- Compatibility is documented as Moodle 4.4 or later.

## 1.0.1 - 2026-04-28

### Changed

- README documentation and related plugin links.
- Companion plugin usage for `filter_videolesson` and `tiny_videolesson`.
- Installation requirements. No separate `local_aws` plugin or external AWS
  SDK installation is required.
- Fixes requested during the Moodle plugins directory review.

## 1.0.0 - 2026-02-24

### Added

- Video Lesson activity module for Moodle courses.
- Watch-percentage completion tracking.
- Seeking controls for video lessons.
- Student video progress tracking.
- Video engagement analytics and reporting.
- Reusable Video Library.
- Uploaded videos, Video Library videos, YouTube, Vimeo, and direct video URLs,
  depending on site configuration.
- AWS S3 and AWS MediaConvert support for uploaded video storage, transcoding,
  and adaptive streaming.
- Site settings for default completion progress and video behaviour.
