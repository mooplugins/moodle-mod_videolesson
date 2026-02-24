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
 * Plugin strings
 *
 * @package    mod_videolesson
 * @author     BitKea Technologies LLP
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Video Lesson';
$string['modulename'] = 'Video Lesson';
$string['modulenameplural'] = 'Videos';
$string['pluginadministration'] = 'Video administration';
$string['cachedef_serve'] = 'Low latency file serve cache';
$string['ffprobe:invalidpath'] = 'Invalid FFProbe path';
$string['privacy:metadata:mod_videolesson:externalpurpose'] = 'This information is sent to AWS API in order the file to be converted to an alternative format. The file is temporarily kept in an AWS S3 bucket and gets deleted after the conversion is done.';
$string['privacy:metadata:mod_videolesson:filecontent'] = 'The content of the file.';
$string['privacy:metadata:mod_videolesson:params'] = 'The query parameters passed to AWS API.';
$string['provision:creatings3'] = 'Creating resource S3 Bucket';
$string['provision:bucketcreated'] = 'Created {$a->bucket} bucket, at location {$a->location}';
$string['provision:bucketexists'] = 'Bucket exists';
$string['provision:inputbucket'] = 'Input bucket: {$a}';
$string['provision:lambdaenvupdate'] = 'Updating Lambda transcode funciton enivronment variables.';
$string['provision:lambdaresourcearn'] = 'Lambda Resource ARN: {$a}';
$string['provision:lambdaarchiveuploaded'] = 'Lambda archive uploaded sucessfully to: {$a}';
$string['provision:outputbucket'] = 'Output bucket: {$a}';
$string['provision:setconfig'] = 'Setting plugin configuration in Moodle, from returned settings.';
$string['provision:resourcestack'] = 'Provisioning the Lambda function to provide a custom cloudformation resource provider';
$string['provision:resourcestackcreated'] = 'Cloudformation custom resource stack created. Stack ID is: {$a}';
$string['provision:stack'] = 'Provisioning the Video AWS stack resources';
$string['provision:s3useraccesskey'] = 'Video AWS S3 user access key: {$a}';
$string['provision:s3usersecretkey'] = 'Video AWS S3 user secret key: {$a}';
$string['provision:sqsqueue'] = 'SQS queue URL: {$a}';
$string['provision:stackcreated'] = 'Cloudformation stack created. Stack ID is: {$a}';
$string['provision:uploadlambdaarchives'] = 'Uploading Lambda function archives to resource S3 bucket';
$string['provision:request:accepted'] = '<strong>Your provision request has been successfully submitted!</strong>
<br><br>
We\'re processing your request, and you\'ll receive an email notification shortly once it\'s complete. The email will confirm whether the request was successful or if any additional steps are needed.
<br>
Thank you for your patience!';
$string['dashboard:heading'] = 'Video Lesson Dashboard';
$string['report:created'] = 'Created';
$string['report:completed'] = 'Completed';
$string['report:duration'] = 'Duration: ';
$string['report:files'] = 'Instances: ';
$string['report:filedetails'] = 'File details';
$string['report:filename'] = 'Filename';
$string['report:format'] = 'Format: ';
$string['report:heading'] = 'Video Lesson Overview Report';
$string['report:heading_desc'] = 'This table shows an overview of conversions.';
$string['report:nocostdata'] = ' - ';
$string['report:sourceresolution'] = 'Source resolution: ';
$string['report:targetresolutions'] = 'Target resolutions: ';
$string['report:size'] = 'Size: ';
$string['report:status'] = 'Status';
$string['report:summary:filesummary'] = 'All file record summary';
$string['report:summary:processsummary'] = 'Multimedia file process summary';
$string['report:processsummary:title'] = 'Process status totals';
$string['report:summary:processsummary:uniquemultimediaobjects'] = 'Unique file objects';
$string['report:summary:processsummary:metadataprocessedfiles'] = 'Metadata processed files';
$string['report:summary:processsummary:transcodedfiles'] = 'Transcoded files';
$string['report:summary:title'] = 'Database file record totals';
$string['report:summary:totals'] = 'Totals';
$string['report:summary:filesummary:otherfiles'] = 'Other files';
$string['report:summary:filesummary:videofiles'] = 'Video files';
$string['report:summary:filesummary:audiofiles'] = 'Audio files';
$string['report:summary:filesummary:total'] = 'Total';
$string['report:summary:cost:remaindescription'] = 'Cost to convert remaining files based on current settings.';
$string['report:summary:cost:donedescription'] = 'Accumulated cost for converted files.';
$string['report:summary:cost:disclaimer'] = 'Note: Cost displayed are calculated based on conversion settings, and are for the puposes of planning. Please refer to AWS billing for actual amounts charged.';
$string['report:summary:cost:total'] = 'Total:';
$string['report:summary:nodata'] = 'No data found';
$string['report:summary:transcodetotal'] = 'Transcode cost';
$string['report:summary:warning:invalidpresets'] = 'There are no valid AWS Elastic Transcoder preset ids in your settings, pricing cannot be calculated.';
$string['report:summary:warning:noaudiocost'] = 'No audio transcode data: could not obtain audio transcode pricing data for {$a} region.';
$string['report:summary:warning:nohdcost'] = 'No high definition transcode data: Could not obtain high definition transcode pricing data for {$a} region.';
$string['report:summary:warning:nosdcost'] = 'No standard definition transcode data: Could not obtain standard definition transcode pricing data for {$a} region.';
$string['report:transcodecost'] = 'Transcode cost: ';
$string['report:type'] = 'File type: ';
$string['report:typeaudio'] = 'Audio';
$string['report:typevideo'] = 'Video';
$string['settings:videolesson:header'] = 'Video Lesson Settings';
$string['settings:instancedefaults'] = 'The values you set here define the default values that are used in the settings form when you create a new video instance.';
$string['settings:instancedefaults_desc'] = 'The values you set here define the default values that are used in the settings form when you create a new video instance.';
$string['settings:completion:progress'] = 'Completion progress';
$string['settings:completion:progressdesc'] = 'Student must reach certain percentage of the video';
$string['settings:roles:exclude'] = 'Exclude roles';
$string['settings:roles:excludedesc'] = 'Progress only applies to student role';
$string['settings:aws:hostingtype'] = 'Hosting';
$string['settings:aws:hostingtype_help'] = '<strong>Self-Managed</strong> - Use your own AWS account for hosting videos. Visit the <a href="{$a}">Provisioning</a> page to set up the AWS resources required for the Video Lesson plugin to work. If you do not have a license key, please purchase one from our <a target="_blank" href="https://mooplugins.com/">website</a> or <a target="_blank" href="https://mooplugins.com/tickets">contact</a> our support team for assistance.
<br><strong>Mooplugins</strong> - Utilize the AWS infrastructure provided and managed by Mooplugins. Hosting license key is required for this option. If you do not have a license key, please purchase one from our <a target="_blank" href="https://mooplugins.com/">website</a> or <a target="_blank" href="https://mooplugins.com/tickets">contact</a> our support team for assistance.
<br><strong>External</strong> - External video links (for example, YouTube, Vimeo or self-hosted MP4 URLs) as the video source.';
$string['settings:aws:moopluginlicense'] = 'License key';
$string['settings:aws:moopluginlicense_help'] = 'Mooplugins license key';
$string['settings:aws:cloudfrontdomain'] = 'Cloudfront Domain';
$string['settings:aws:cloudfrontdomain_help'] = 'Cloudfront Domain';
$string['settings:aws:description'] = 'These settings define the required AWS settings to allow Moodle to connect to AWS for conversion.';
$string['settings:aws:header:nolicense'] = 'License Key Required';
$string['settings:aws:header:nolicense_desc'] = 'To access the settings, please enter your valid license key <a href="{$a}">here</a>. The license key is required to unlock and proceed with the configuration options. If you don\'t have a license key, please purchase one from our <a target="_blank" href="https://mooplugins.com/tickets">website</a> or contact support.';
$string['settings:aws:header:expiredlicense'] = 'License Key is expired';
$string['settings:aws:header:expiredlicense_desc'] = 'To access the settings, please enter your valid license key <a href="{$a}">here</a>. The license key is required to unlock and proceed with the configuration options. If you don\'t have a license key, please purchase one from our <a target="_blank" href="https://mooplugins.com/tickets">website</a> or contact support.';
$string['settings:aws:header'] = 'AWS settings';
$string['settings:aws:header_desc'] = 'The settings for the AWS components used to convert media files and extract information.';
$string['settings:aws:key'] = 'Key';
$string['settings:aws:key_help'] = 'Amazon API key credential.';
$string['settings:aws:secret'] = 'Secret';
$string['settings:aws:secret_help'] = 'Amazon API secret credential.';
$string['settings:aws:input_bucket'] = 'Input bucket';
$string['settings:aws:input_bucket_help'] = 'Amazon S3 bucket to upload media files for conversion.';
$string['settings:aws:output_bucket'] = 'Output bucket';
$string['settings:aws:output_bucket_help'] = 'Amazon S3 bucket to fetch converted media files.';
$string['settings:aws:region'] = 'Region';
$string['settings:aws:region_help'] = 'Amazon API gateway region.';
$string['settings:aws:sqs_queue_url'] = 'SQS queue URL';
$string['settings:aws:sqs_queue_url_help'] = 'URL of the AWS SQS queue to receive status messages from.';
$string['settings:aws:dynamodb_table_name'] = 'DynamoDB table name';
$string['settings:aws:dynamodb_table_name_help'] = 'Name of the DynamoDB table for storing transcoding status. If set, transcoding status will be checked via DynamoDB instead of SQS. Requires AWS Lambda to write status to DynamoDB. Default: videolesson-transcoding-status';
$string['settings:aws:sns_topic_arn'] = 'SNS Topic ARN';
$string['settings:aws:sns_topic_arn_desc'] = 'AWS SNS Topic ARN for triggering subtitle generation';
$string['settings:aws:usesdkcreds'] = 'Use the default credential provider chain to find AWS credentials';
$string['settings:aws:usesdkcreds_desc'] = 'If Moodle is hosted inside AWS, the default credential chain can be used for access to resources. If so, the AWS key and Secret key are not required to be provided.';
$string['settings:connectionsuccess'] = 'Could establish connection to the external object storage.';
$string['settings:connectionfailure'] = 'Could not establish connection to the external object storage.';
$string['settings:description'] = 'Description';
$string['settings:lowlatency'] = 'Use cached file serving';
$string['settings:lowlatency_help'] = 'This allows videolesson to use low latency moodle cached file serving for videolesson. Improves performance for Moodle based file serving. Disable if using external file serving such a CDN.';
$string['settings:writefailure'] = 'Could not write object to the external object storage. ';
$string['settings:readfailure'] = 'Could not read object from the external object storage. ';
$string['settings:deletesuccess'] = 'Could delete object from the external object storage - It is not recommended for the user to have delete permissions. ';
$string['settings:deleteerror'] = 'Could not delete object from the external object storage. ';
$string['settings:permissioncheckpassed'] = 'Permissions check passed.';
$string['settings:ffprobe:description'] = 'These settings relate to FFProbe. FFprobe is used for initial Moodle server side analysis of files.';
$string['settings:ffprobe:header'] = 'FFProbe settings';
$string['settings:ffprobe:header_desc'] = 'The settings for the FFProbe binary.';
$string['settings:enrichment:description'] = 'These settings control what services are used to enrich the information related to media files';
$string['settings:enrichment:header'] = 'Enrichment settings';
$string['settings:enrichment:header_desc'] = 'These settings determine the enrichment processing.';
$string['settings:enrichment:detectentities'] = 'Entity detection';
$string['settings:enrichment:detectentities_help'] = 'Detect entities in video files, such as companies and locations.';
$string['settings:enrichment:detectfaces'] = 'Face detection';
$string['settings:enrichment:detectfaces_help'] = 'Detect faces in video files, including facial attributes and expressions.';
$string['settings:enrichment:detectlabels'] = 'Label detection';
$string['settings:enrichment:detectlabels_help'] = 'Detect object labels in video files, such as chair, crowd, and human.';
$string['settings:enrichment:detectpeople'] = 'People detection';
$string['settings:enrichment:detectpeople_help'] = 'Detect the occurance of individuals in a video.';
$string['settings:enrichment:detectphrases'] = 'Key phrase detection';
$string['settings:enrichment:detectphrases_help'] = 'Detect key phrases in audio and video files.';
$string['settings:enrichment:detectmoderation'] = 'Moderation detection';
$string['settings:enrichment:detectmoderation_help'] = 'Perform moderation content on videos for adult or sensitive content.';
$string['settings:enrichment:detectsentiment'] = 'Sentiment detection';
$string['settings:enrichment:detectsentiment_help'] = 'Perform sentiment detection on video and audio files.';
$string['settings:processing:description'] = 'These settings control how and when media files are processed.';
$string['settings:processing:header'] = 'Processing settings';
$string['settings:processing:header_desc'] = 'These settings control how media files are processed.';
$string['settings:processing:convertfrom'] = 'Convert since';
$string['settings:processing:convertfrom_help'] = 'Only files added to Moodle since this time period will be converted. Applies to both background processing and view conversions.';
$string['settings:processing:maxruntime'] = 'Maximum task runtime';
$string['settings:processing:maxruntime_help'] = 'This setting controls the maximum runtime of the metadata extraction task. After this duration, the task will exit cleanly.';
$string['settings:processing:proactiveconversion'] = 'Background processing';
$string['settings:processing:proactiveconversion_help'] = 'When enabled media files will be processed via scheduled task.';
$string['settings:processing:viewconversion'] = 'View processing';
$string['settings:processing:viewconversion_help'] = 'When enabled media files will be processed when they are first viewed.';
$string['settings:output:description'] = 'These settings control what outputs are produced when a file is converted.';
$string['settings:output:header'] = 'Output settings';
$string['settings:output:header_desc'] = 'These settings control what outputs are produced when a file is converted.';
$string['settings:output:quality_low'] = ' Low quailty output - 600 Kb/s';
$string['settings:output:quality_low_help'] = 'Source file will be transcode to a low quality low bandwidth output.';
$string['settings:output:quality_medium'] = 'Medium quality output - 1.2 Mb/s';
$string['settings:output:quality_medium_help'] = 'Source file will be transcode to a medium quality medium bandwidth output.';
$string['settings:output:quality_high'] = 'High quality output - 2.4 Mb/s';
$string['settings:output:quality_high_help'] = 'Source file will be transcode to a high quality high bandwidth output.';
$string['settings:output:quality_extrahigh'] = 'Extra high quality output - 4.8 Mb/s';
$string['settings:output:quality_extrahigh_help'] = 'Source file will be transcode to an extra high quality extra high bandwidth output.';
$string['settings:output:audio_output'] = 'Audio output';
$string['settings:output:audio_output_help'] = 'Provide an audio only output for video files.';
$string['settings:output:download_files'] = 'Download files';
$string['settings:output:download_files_help'] = 'Provide option for users to download files.';
$string['settings:output:usecustompresets'] = 'Use custom presets';
$string['settings:output:usecustompresets_help'] = 'Check to enable support for custom ETS presets.';
$string['settings:output:custompresets'] = 'Define custom presets';
$string['settings:output:custompresets_help'] = 'Enter any custom preset ID\'s to use, seperated by a comma.';
$string['settings:enrichment:transcribe'] = 'Trascribe file';
$string['settings:enrichment:transcribe_help'] = 'Attempt an automated transcription on audio and video files.';
$string['settings:ffprobe:pathtoffprobe'] = 'FFProbe binary path';
$string['settings:ffprobe:pathtoffprobe_desc'] = 'The path to the FFProbe binary on the server running Moodle.';
$string['settings:subtitle:create'] = 'Generate subtitle';
$string['settings:subtitle:createdesc'] = 'If this option is enabled, the "Generate subtitle" checkbox will be checked by default in the activity form.';
$string['settings:seek:nooverride'] = 'Do not override (use activity setting)';
$string['settings:seek:allow'] = 'Allow seek';
$string['settings:seek:disableseek'] = 'Disable seek';
$string['settings:seek:disableseekrewind'] = 'Disable seeking forward, allow rewind';
$string['settings:seek:override:options'] = 'Select seeking behavior';
$string['settings:seek:override:description'] = 'If set to "Disable seek" or "Allow rewind", this option will override the per-activity setting and enforce the selected behavior site-wide. If set to "Allow seek", individual activity settings will be used.';
$string['settings:completion:force_disable_seek_threshold'] = 'Completion threshold to force disable seek';
$string['settings:completion:force_disable_seek_threshold_help'] = 'When activity completion percentage is set to this threshold or higher, seeking will be automatically disabled. This ensures students watch the required portion of the video rather than skipping ahead. Set to 0% to disable this automatic enforcement.';
$string['modform:completion:seek_auto_enforced'] = 'Seeking has been automatically disabled because completion requires watching {$a}% or more of the video.';
$string['settings:speed:override'] = 'Disable speed control';
$string['settings:speed:override:desc'] = 'If checked, this will override the activity settings and disable the speed control for all videos site-wide.';
$string['settings:pip:override'] = 'Disable Picture-in-Picture mode';
$string['settings:pip:override:desc'] = 'If checked, this will override the activity settings and disable the Picture-in-Picture mode for all videos site-wide.';
$string['task:extractmetadata'] = 'extract multimedia file metadata.';
$string['task:processconversions'] = 'process pending conversions.';
$string['test:bucketnotexists'] = 'The {$a} bucket does not exist.';
$string['test:fileuploaded'] = 'Test file uploaded';
$string['test:uploadfile'] = 'Uploading test file';
$string['task:reportprocess'] = 'extract report data.';
$string['task:poll_conversions'] = 'Check stale conversions';
$string['header_manage_videos'] = 'Video Library';
$string['manage_videos'] = 'Video Library';
$string['manage:videos:total'] = 'Total results: {$a}';
$string['col_vid_title'] = 'Title';
$string['col_vid_status'] = 'Status';
$string['col_vid_instances'] = 'Instances';
$string['col_vid_size'] = 'Size';
$string['col_vid_source_size'] = 'Source size';
$string['col_timecreated'] = 'Time created';
$string['col_timemodified'] = 'Time modified';
$string['col_action'] = 'Action';
$string['header_video_instances'] = 'Video instances';
$string['col_instance_course'] = 'Course';
$string['col_instance_title'] = 'Title';
$string['removefilter'] = 'Remove filter';
$string['video_title'] = 'Title';
$string['video_title_help'] = 'Change the title of the video';
$string['activity:processing'] = 'The video lesson is currently being transcoded. Please wait until the process is complete.';
$string['activity:notfound'] = 'Video file is missing.';
$string['video_src_upload'] = 'Upload video';
$string['video_src_embed'] = 'Embed code';
$string['video_src_external'] = 'External link';
$string['video_src_aws'] = 'Video Library';
$string['link:upload'] = 'Upload video';
$string['link:library'] = 'Video Library';
$string['bucket:missing'] = 'Missing in bucket';
$string['modform:header'] = 'Video';
$string['modform:source'] = 'Choose video source';
$string['modform:uploadnew'] = 'Upload new video';
$string['modform:subtitle'] = 'Generate subtitle';
$string['modform:addthumbnail'] = 'Add custom video thumbnail';
$string['modform:uploadthumbnail'] = 'Video thumbnail';
$string['modform:videourl'] = 'Enter video URL or embed code';
$string['modform:videourl_help'] = 'Enter a direct video file URL (mp4, webm, etc.), YouTube/Vimeo URL, or embed code (iframe). YouTube and Vimeo videos will have tracking enabled. Other embed providers will work but without tracking.';
$string['modform:embedcode'] = 'Enter embed code';
$string['modform:seekoptions'] = 'Seeking behavior';
$string['modform:overridewarning'] = 'This setting is overridden by the <a href="{$a->url}" target="_blank">global configuration</a> and may not take effect. <ul> {$a->items} </ul>';
$string['modform:overrideseek1'] = '<li>Seeking behavior: Allow seek</li>';
$string['modform:overrideseek2'] = '<li>Seeking behavior: Disable seek</li>';
$string['modform:overrideseek3'] = '<li>Seeking behavior: Disable seeking forward, allow rewind</li>';

$string['modform:seekoptions_help'] = 'Control how learners interact with the video timeline:
<ul>
<li>Allow seeking: Learners can skip forward or backward freely.</li>
<li>Disable seeking: Disables all seeking — learners must watch linearly.</li>
<li>Disable seeking forward, allow rewind: Learners can go back but not skip ahead.</li>
</ul>
Use this to ensure learners watch the video in sequence and prevent skipping essential content.';

$string['modform:overridedisablespeed'] = '<li>Disable speed control</li>';
$string['modform:overridedisablepip'] = '<li>Disable picture-in-picture mode </li>';
$string['modform:allowseek'] = 'Allow seeking';
$string['modform:disableseek'] = 'Disable seeking';
$string['modform:disableseekrewind'] = 'Disable seeking forward, allow rewind';
$string['modform:disablespeed'] = 'Disable playback speed control';
$string['modform:disablespeed_help'] = 'Prevents learners from changing playback speed. Useful for maintaining intended pacing in tutorials, compliance, or timed content.';
$string['modform:disablepip'] = 'Disable picture-in-picture mode';
$string['modform:disablepip_help'] = 'Stops learners from popping the video out into a floating window. Helps ensure they stay focused within the activity.';
$string['modform:error:thumbnail'] = 'Missing thumbnail';
$string['modform:error:videourl'] = 'Video url required';
$string['modform:error:videourl:invalid'] = 'Not valid video url';
$string['modform:error:videourl:invalid:vimeo'] = 'Not valid vimeo url';
$string['modform:error:videourl:invalid:youtube'] = 'Not valid youtube url';
$string['modform:error:embedcode'] = 'Embded code required';
$string['modform:upload:processing'] = '<div class="alert alert-warning alert-block fade in" role="alert" data-aria-autofocus="true">
Processing the video. Please wait patiently while the process completes.</div>';
$string['modform:error:embedcode:invalid'] = 'Not valid embed code';
$string['error:invalidembedcode'] = 'Invalid embed code: Could not extract URL from embed code';
$string['error:unsupportedembedprovider'] = 'Unsupported embed provider: Only YouTube and Vimeo are supported';
$string['modform:error:source'] = '<div class="alert alert-danger" role="alert">Please select a video from the gallery!</div>';
$string['modform:videochange:title'] = 'Replace video';
$string['modform:videochange:content'] = 'Replacing video will reset users\' progress. Do you really want to proceed?';
$string['modform:videochange:yes'] = 'Yes, Change the video!';
$string['modform:videochange:cancel'] = 'cancel';
$string['modform:noconfig:alert:title'] = 'Missing Config';
$string['modform:noconfig:alert:content'] = '<h3>Video Library Unavailable</h3>The Video Library requires AWS to be configured before it can be used. At the moment, the required AWS settings are missing or incomplete. Please ensure you have entered valid AWS settings <a href="/admin/settings.php?section=modsettingvideolesson">here</a> to proceed, where you can configure your AWS credentials or select a hosting option. If you\'re unsure which option is right for you, <a href="https://www.mooplugins.com/contact/">contact</a> our support team.';
$string['modform:noconfig:alert:button'] = 'Got it.';
$string['modform:completion:progress'] = 'Student must reach certain percentage of the video';
$string['completiondetail:progressdesc'] = 'Watch at least {$a}% of the video';
$string['manage:video:delete:confirm'] = 'Delete video "{$a}"?';
$string['manage:upload:form:element:filemanager'] = 'Videos';
$string['manage:upload:form:element:uploadbtn'] = 'Upload Videos';
$string['manage:upload:form:element:subtitle'] = 'Generate subtitle';
$string['manage:upload:link'] = 'Upload Videos';
$string['manage:upload:video:added'] = 'Video files added to queue for transcoding';
$string['manage:upload:video:skipped'] = 'Video files skipped as it already exists';
$string['canupload:upload_limit'] = 'You have reached the upload limit, Please upgrade your plan.';
$string['canupload:monthly_upload_limit'] = 'You have reached the monthly upload limit, Please upgrade your plan.';
$string['manage:video:view:link'] = 'Click to view';
$string['manage:video:mp4'] = 'Mp4';
$string['manage:video:upload:retry'] = 'Retry';
$string['manage:video:upload:retry:confirm'] = 'Retry uploading video "{$a}"?';
$string['player:resume:modal:title'] = 'Continue from where you left off?';
$string['player:resume:modal:body'] = 'Would you like to continue where you left off?';
$string['player:resume:modal:yes'] = 'Yes, continue!';
$string['player:resume:modal:no'] = 'No';
$string['player:processing:video:ready'] = 'Video is ready! Reloading the page...';
$string['player:processing:video:done'] = 'Processing done. Reloading the page...';
$string['ws:notify:reqcomplete'] = 'Progress requirement met.';
$string['error:videoisused'] = 'Video is used in {$a} instance/s';
$string['error:video:exists'] = 'The video {$a} already exists in gallery!';
$string['error:nothingtodelete'] = 'Nothing to delete';
$string['error:nocap:delete'] = 'You have no capability to delete a video';
$string['success:delete'] = 'Video deleted successfully';
$string['inplace:edit:name'] = 'Edit video title';
$string['inplace:edit:newname'] = 'New title';
$string['report:all:header'] = 'Video Lesson Report: {$a}';
$string['report:view'] = 'View report';
$string['report:impressions'] = 'Player Impressions';
$string['report:plays'] = 'Plays';
$string['report:unique'] = 'Unique Viewers';
$string['report:totaltime'] = 'Total Minutes Viewed';
$string['report:avgtime'] = 'Avg. Time Spent';
$string['report:avgcompletion'] = 'Avg. Completion Rate';
$string['report:views:heading'] = 'Views/Duration';
$string['report:platforms:heading'] = 'Platforms';
$string['report:browsers:heading'] = 'Browsers';
$string['report:countries:heading'] = 'Countries';
$string['report:nodata'] = 'No data to show.';
$string['report:impressions:header'] = 'Player Impressions';
$string['report:impressions:col:user'] = 'User';
$string['report:impressions:col:time'] = 'Time';
$string['report:impressions:col:others'] = 'Platform|OS|Browser|IP';
$string['report:play:user'] = 'User:';
$string['report:play:time'] = 'Time:';
$string['report:play:city'] = 'City:';
$string['report:play:country'] = 'Country:';
$string['report:play:ip'] = 'IP:';
$string['report:play:platform'] = 'Platform:';
$string['report:play:browser'] = 'Browser:';
$string['report:play:os'] = 'OS:';
$string['report:play:playback:header'] = 'Playback Events';
$string['report:play:playback:col:type'] = 'Type';
$string['report:play:playback:col:time'] = 'Timestamp';
$string['report:play:playback:col:position'] = 'Position';
$string['report:play:seek:header'] = 'Seek events';
$string['report:play:seek:col:time'] = 'Timestamp';
$string['report:play:seek:col:startposition'] = 'Start Position';
$string['report:play:seek:col:newposition'] = 'New Position';
$string['report:play:visibility:header'] = 'Visibility';
$string['report:play:visibility:col:time'] = 'Timestamp';
$string['report:play:visibility:col:position'] = 'Position';
$string['report:play:visibility:col:active'] = 'Active';
$string['report:play:visibility:yes'] = 'Yes';
$string['report:play:visibility:no'] = 'No';
$string['report:play:volume:header'] = 'Volume changes';
$string['report:play:volume:col:time'] = 'Timestamp';
$string['report:play:volume:col:position'] = 'Position';
$string['report:play:volume:col:level'] = 'Volume Level';
$string['report:play:quality:header'] = 'Quality changes';
$string['report:play:quality:col:time'] = 'Timestamp';
$string['report:play:quality:col:position'] = 'Position';
$string['report:play:quality:col:quality'] = 'Quality';
$string['report:plays:header'] = 'Plays';
$string['report:plays:col:user'] = 'User';
$string['report:plays:col:time'] = 'Time';
$string['report:plays:col:duration'] = 'Watch duration';
$string['report:plays:col:data'] = 'Watch data';
$string['report:plays:progress'] = '{$a}% progress';
$string['report:unique:header'] = 'Unique Viewers';
$string['report:unique:col:user'] = 'User';
$string['report:unique:col:watch'] = 'Watch count';
$string['form:gallery:selectvideo'] = 'Select a video file';
$string['form:gallery:col:name'] = 'Name';
$string['form:gallery:col:duration'] = 'Duration';
$string['form:gallery:col:date'] = 'Date added';
$string['form:gallery:col:folder'] = 'Folder';
$string['form:gallery:col:thumbnail'] = 'Thumbnail';
$string['form:gallery:col:title'] = 'Video Title';
$string['manage:upload'] = 'Upload Videos';
$string['manage:filter:upload'] = 'Search video';
$string['manage:filter:search'] = 'Search';
$string['manage:filter:reset'] = 'Reset';
$string['player:progress'] = 'Progress';
$string['player:progress:reached'] = 'Progress requirement reached';
$string['player:waiting'] = 'Waiting for video to be processed';
$string['player:video:error'] = 'Something went wrong with the processing of the video.';
$string['novideolessonconfig'] = 'Unable to connect. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.
Details : {$a}';
$string['player:seeking:disabled:header'] = 'Skipping Disabled';
$string['player:seeking:disabled:description'] = 'Skipping ahead in the video has been disabled. The video must be watched in full to continue.';
$string['gotit'] = 'Got it!';
$string['messageprovider:notification'] = 'Notification';
$string['license'] = 'License';
$string['license:header'] = 'VideoLesson License';
$string['license:key'] = 'License Key';
$string['license:key_help'] = 'License Key';
$string['license:type'] = 'License Type';
$string['license:type_help'] = 'Select the type of license';
$string['license:type:self'] = 'Self Managed AWS';
$string['license:type:hosted'] = 'MooPlugin Hosted AWS';
$string['license:deactivate'] = 'Deactivate this license key';
$string['license:deactivated'] = 'License key deactivated';
$string['license:deactivate:notallowed'] = 'User is not allowed to deactivate VideoLesson license.';
$string['license:accepted'] = 'License key accepted successfully.';
$string['license:invalid'] = 'Invalid license key. Please enter a valid key.';
$string['license:invalidtype'] = 'Invalid license type. Your license is for self managed, Please enter a valid key.';
$string['license:expired'] = 'License expired';
$string['license:description:deactivate'] = 'License is active.';
$string['license:description'] = 'Enter your license key to provision the AWS stack. If you don\'t have one yet, you can <a target="_blank" href="https://www.mooplugins.com/product/aws-provisioning-setup-for-video-lesson-activity/">purchase a license</a> or <a target="_blank" href="https://www.mooplugins.com/contact/">contact</a> our support team for assistance.';
$string['provision:header'] = 'Provision Video Lesson Stack';
$string['provision:license'] = 'Provisioning license key';
$string['provision:license_help'] = 'Provisioning license key';
$string['provision:key'] = 'AWS Key ID';
$string['provision:key_help'] = 'AWS Key ID';
$string['provision:secret'] = 'AWS Secret Key';
$string['provision:secret_help'] = 'AWS Secret Key';
$string['provision:region'] = 'AWS Region';
$string['provision:region_help'] = 'AWS Region';
$string['provision'] = 'Provision';
$string['transcoding:status:200'] = 'Transcoded';
$string['transcoding:status:201'] = 'Transcoding In Progress';
$string['transcoding:status:202'] = 'Transcoding Pending';
$string['transcoding:status:404'] = 'Not Found';
$string['transcoding:status:500'] = 'Transcoding Error';
$string['upload:status:200'] = 'Uploaded';
$string['upload:status:202'] = 'Upload Pending';
$string['upload:status:503'] = 'Upload Failed';
$string['upload:status:500'] = 'Upload Error';
$string['status:uploaded'] = 'Uploaded';
$string['status:pending'] = 'Upload Pending';
$string['required'] = 'This field is required.';
$string['config:missing'] = '<h3>Video Library Unavailable</h3>The Video Library requires AWS to be configured before it can be used. At the moment, the required AWS settings are missing or incomplete. Please ensure you have entered valid AWS settings <a href="{$a}">here</a> to proceed, where you can configure your AWS credentials or select a hosting option. If you\'re unsure which option is right for you, <a href="https://www.mooplugins.com/contact/">contact</a> our support team.';
$string['access:nolicense'] = '<h3>License Key Required</h3>
This feature cannot be accessed because a valid license key has not been configured.
Please enter your license key <a target="_blank" href="{$a}">here</a> to enable access.
Without a valid license, this functionality will remain unavailable.
If you need assistance or would like to purchase a license, please <a href="https://www.mooplugins.com/contact/" target="_blank">contact</a> our support team.';
$string['access:expiredlicense'] = '<h3>License Key Expired</h3>
This feature cannot be accessed because your license key has expired.
Please renew or enter a valid license key <a target="_blank" href="{$a}">here</a> to restore access.
Until a valid license is provided, this functionality will remain unavailable.
If you need assistance with renewal, please <a href="https://www.mooplugins.com/contact/" target="_blank">contact</a> our support team.';
$string['access:nolicense:modform'] = '<h3>License Key Required</h3>
These settings cannot be accessed because a valid license key has not been configured.
Please enter your license key <a target="_blank" href="{$a}">here</a> to enable access.
Without a valid license, these settings will remain unavailable.
If you need assistance or would like to purchase a license, please <a href="https://www.mooplugins.com/contact/" target="_blank">contact</a> our support team.';
$string['access:nolicense:activity'] = 'Activity not available.';
$string['access:nolicense:adminnotice'] = 'Video Lesson Activity: Your license is about to expire soon. Please renew it to ensure uninterrupted access to all features and services. If your license expires, you may lose access to critical functionalities. Contact support or visit your account settings to renew your license.';

$string['dailytask'] = 'Daily Task at 1 AM';
$string['expirynotification:subject'] = 'Video Lesson Reminder: Expiry Date Approaching';
$string['expirynotification:message'] = 'Dear Administrator,

This is a friendly reminder that the license expiry date of Video Lesson module is approaching. The expiry date is set for {$a}. Please take the necessary actions to renew or address this before the expiry date.

If you have any questions or need further assistance, please do not hesitate to contact us.

Best regards,
MooPlugins Team';

// Folder management strings.
$string['folder:create'] = 'Create Folder';
$string['folder:rename'] = 'Rename Folder';
$string['folder:edit'] = 'Edit Folder';
$string['folder:delete'] = 'Delete Folder';
$string['folder:move'] = 'Move to Folder';
$string['folder:name'] = 'Folder Name';
$string['folder:name_required'] = 'Folder name is required';
$string['folder:create_success'] = 'Folder created successfully';
$string['folder:create_error'] = 'Failed to create folder';
$string['folder:update_success'] = 'Folder updated successfully';
$string['folder:update_error'] = 'Failed to update folder';
$string['folder:delete_success'] = 'Folder deleted successfully';
$string['folder:delete_error'] = 'Failed to delete folder. Folder may contain child folders.';
$string['folder:delete_confirm'] = 'Are you sure you want to delete this folder?';
$string['folder:delete_option_move'] = 'Move videos to parent folder';
$string['folder:delete_option_remove'] = 'Delete videos inside this folder';
$string['folder:move_video_success'] = 'Video moved successfully';
$string['folder:move_video_error'] = 'Failed to move video';
$string['folder:max_depth_reached'] = 'Maximum folder depth (3 levels) reached';
$string['folder:uncategorized'] = 'Uncategorized';
$string['folder:drag_handle'] = 'Drag to move video to a folder';
$string['folder:all_videos'] = 'All Videos';
$string['folder:video_count'] = '{$a} video(s)';
$string['folder:no_videos'] = 'No videos';
$string['folder:expand'] = 'Expand';
$string['folder:collapse'] = 'Collapse';
$string['folder:collapse_all'] = 'Collapse All Folders';
$string['folder:expand_all'] = 'Expand All Folders';
$string['folder:new'] = 'New Folder';
$string['folder:parent'] = 'Parent Folder';
$string['folder:root'] = 'Root';
$string['folder:assign'] = 'Assign Folder';
$string['folder:select'] = 'Select Folder';
$string['error:folder:invalid'] = 'The selected folder is no longer available.';
$string['folder:drop_here'] = 'Drop video here';
$string['folder:invalid_name'] = 'Invalid folder name';
$string['folder:tree'] = 'Folder Tree';
$string['folder:manage'] = 'Manage Folders';
$string['col_folder'] = 'Folder';
$string['bulk:select_all'] = 'Select all';
$string['bulk:select_video'] = 'Select video';
$string['bulk:move'] = 'Move to Folder';
$string['bulk:delete'] = 'Delete Selected';
$string['bulk:selected_count'] = 'selected';
$string['bulk:confirm_delete'] = 'Are you sure you want to delete {$a} video(s)?';
$string['bulk:move_success'] = 'Videos moved successfully';
$string['bulk:delete_success'] = 'Videos deleted successfully';
$string['bulk:error'] = 'An error occurred during the operation';
$string['bulk:no_selection'] = 'Please select at least one video';
$string['error:video:notfound'] = 'Video with ID {$a} not found';
$string['confirmation'] = 'Confirmation';
$string['subtitle:generate'] = 'Generate Subtitles';
$string['subtitle:select_language'] = 'Select Language';
$string['subtitle:modal:title'] = 'Generate Subtitles';
$string['subtitle:modal:body'] = 'Select one or more languages for subtitles. Hold Ctrl (Windows/Linux) or Command (Mac) to select multiple.';
$string['subtitle:modal:remaining'] = 'Remaining subtitles to generate: {$a}';
$string['subtitle:modal:existing'] = 'Existing subtitles: {$a}';
$string['subtitle:modal:submit'] = 'Proceed';
$string['success:subtitle:triggered'] = 'Subtitle generation request sent successfully';
$string['error:subtitle:not_transcoded'] = 'Video must be transcoded before generating subtitles';
$string['error:subtitle:invalid_lang'] = 'Invalid language code: {$a}';
$string['error:subtitle:trigger_failed'] = 'Failed to trigger subtitle generation';
$string['error:subtitle:exception'] = 'Error triggering subtitle generation: {$a}';
$string['error:subtitle:partial_failure'] = 'Some languages failed: {$a}';
$string['error:subtitle:already_requested'] = 'One or more selected languages are already being processed or completed.';
$string['error:subtitle:no_languages'] = 'No valid languages selected.';
$string['error:subtitle:timeout'] = 'Subtitle generation timed out.';
$string['error:video:notfound'] = 'Video not found: {$a}';
$string['subtitle:status:pending'] = 'Pending';
$string['subtitle:status:processing'] = 'Processing';
$string['subtitle:status:failed'] = 'Failed';
$string['subtitle:modal:pending'] = 'Pending subtitles';
$string['subtitle:modal:processing'] = 'Processing subtitles';
$string['subtitle:modal:failed'] = 'Failed subtitles';
$string['subtitle:retry'] = 'Retry';
$string['subtitle:retry_failed'] = 'Retry failed subtitles';
$string['subtitle:some_skipped'] = 'Some languages were skipped (already requested): {$a}';
$string['filter:text'] = 'Filter';
$string['filter:folder'] = 'Folder';

// Setup Wizard strings
$string['setup:wizard:title'] = 'Video Lesson Setup Wizard';
$string['setup:step1:title'] = 'Choose Hosting';
$string['setup:step1:heading'] = 'Step 1: Choose Your Hosting Option';
$string['setup:step1:description'] = 'Video Lesson uses AWS for video storage, processing, and streaming. Choose the option that best fits your current setup — you can change this later.';
$string['setup:step1:complete'] = 'Hosting option has been selected.';
$string['setup:step1:option1:recommended'] = 'Recommended';
$string['setup:step1:option1:title'] = 'I\'m just trying it out';
$string['setup:step1:option1:description'] = 'Get started instantly with our <strong>free managed hosting plan</strong>. This includes <strong>5 GB of storage</strong> and a <strong>1 GB/month upload limit</strong>, so you can explore the Video Library, video lessons, and completion tracking without any AWS setup.';
$string['setup:step1:option1:feature1'] = '5 GB storage';
$string['setup:step1:option1:feature2'] = '1 GB/month upload limit';
$string['setup:step1:option1:feature3'] = 'No AWS setup required';
$string['setup:step1:option1:link'] = 'Explore other MooPlugins hosting plans';
$string['setup:step1:option1:upgrade_note'] = 'You can upgrade or switch to another option at any time.';
$string['setup:step1:option1:select'] = 'Select Free Hosting';
$string['setup:step1:option2:title'] = 'Use my own AWS account (Self-managed)';
$string['setup:step1:option2:description'] = 'Use your own AWS infrastructure for video storage, transcoding, and delivery. You may want to choose this option if you already manage AWS services.';
$string['setup:step1:option2:note'] = 'You\'ll be guided to configure the required AWS settings in the next steps.';
$string['setup:step1:option2:select'] = 'Select Self-Managed';
$string['setup:step1:option3:title'] = 'Continue without hosting (External links only)';
$string['setup:step1:option3:description'] = 'Proceed without a hosting plan and use <strong>external video links</strong> (for example, YouTube, Vimeo or self-hosted MP4 URLs) as the video source.';
$string['setup:step1:option3:limitations:title'] = 'Limitations:';
$string['setup:step1:option3:limitation1'] = 'Video Library not available';
$string['setup:step1:option3:limitation2'] = 'Video uploads not available';
$string['setup:step1:option3:limitation3'] = 'Subtitles not available';
$string['setup:step1:option3:link'] = 'Read more about using external video links';
$string['setup:step1:option3:select'] = 'Select External Links Only';

$string['setup:step2:title'] = 'Configure';
$string['setup:step2:complete'] = 'Configuration completed.';
$string['setup:step2:selfmanaged:title'] = 'Self-Managed';
$string['setup:step2:selfmanaged:description'] = 'Use your own AWS account to host videos. You will need to provision AWS resources yourself.';
$string['setup:step2:selfmanaged:subtitle'] = 'You will manage your own AWS infrastructure. Choose how you want to proceed:';
$string['setup:step2:selfmanaged:optiona:title'] = 'Option A: Provision by Yourself';
$string['setup:step2:selfmanaged:optiona:description'] = 'Set up AWS resources manually using our documentation.';
$string['setup:step2:selfmanaged:optiona:button'] = 'View Provisioning Documentation';
$string['setup:step2:selfmanaged:optionb:title'] = 'Option B: One-Time Setup Service';
$string['setup:step2:selfmanaged:optionb:promo:title'] = 'One-Time Setup with 6 Months Support';
$string['setup:step2:selfmanaged:optionb:promo:description'] = 'Let our experts set up your AWS infrastructure. Includes 6 months of support.';
$string['setup:step2:selfmanaged:optionb:button'] = 'Purchase Setup Service';
$string['setup:step2:mooplugins:title'] = 'Mooplugins Hosting';
$string['setup:step2:mooplugins:description'] = 'Use Mooplugins managed AWS infrastructure. No need to set up or manage AWS resources yourself.';
$string['setup:step2:mooplugins:subtitle'] = 'Let us handle all the infrastructure management for you.';
$string['setup:step2:mooplugins:promo:title'] = 'Managed Video Hosting';
$string['setup:step2:mooplugins:promo:description'] = 'Let us handle all the infrastructure. Focus on creating great video content.';
$string['setup:step2:mooplugins:button'] = 'View Hosting Plans';
$string['setup:step2:mooplugins:info'] = 'A hosting license key is required for this option. You can purchase one from our website.';
$string['setup:step2:mooplugins:license:info'] = 'License Key Settings';
$string['setup:step2:mooplugins:license:description'] = 'If you already have a license key, you can enter it here.';
$string['setup:step2:mooplugins:license:save'] = 'Save License Key';
$string['setup:step2:license:saved'] = 'License key saved successfully';
$string['setup:step2:self:heading'] = 'Configure Your AWS Settings';
$string['setup:step2:self:description'] = 'Video Lesson will use your AWS account for video storage, processing, and delivery. Please provide the required details below.';
$string['setup:step2:self:validate'] = 'Validate & Continue';
$string['setup:step2:self:validation:success'] = 'AWS connection validated successfully!';
$string['setup:step2:self:validation:error'] = 'AWS connection validation failed';
$string['setup:step2:self:validation:input_bucket_failed'] = 'Failed to access input bucket:';
$string['setup:step2:self:validation:output_bucket_failed'] = 'Failed to access output bucket:';
$string['setup:step2:self:validation:check_info'] = 'Please check if the information provided is correct.';
$string['setup:step2:selfmanaged:settings:info'] = 'AWS Configuration';
$string['setup:step2:selfmanaged:settings:description'] = 'If you already have AWS credentials and configuration details, you can enter them here. Otherwise, you can set them up later in the plugin settings.';
$string['setup:step2:selfmanaged:settings:help'] = 'If you need help with AWS provisioning, managed hosting, or have any questions about this setup, please';
$string['setup:step2:contactus'] = 'contact us';
$string['setup:step2:selfmanaged:settings:save'] = 'Save AWS Settings';
$string['setup:step2:settings:saved'] = 'AWS settings saved successfully';
$string['setup:step2:hosted:title'] = 'Free Hosting Activated';
$string['setup:step2:hosted:message'] = 'Your free MooPlugins hosting plan is now active. You can start uploading videos and creating Video Lessons right away.';
$string['setup:step2:hosted:selection:title'] = 'You Selected Free Hosting';
$string['setup:step2:hosted:selection:message'] = 'You have selected the free managed hosting option. Click the button below to activate your free hosting plan.';
$string['setup:step2:hosted:activate'] = 'Activate Free Hosting';
$string['setup:step2:hosted:activate:existing'] = 'Activate';
$string['setup:step2:hosted:details:title'] = 'Hosting Details';
$string['setup:step2:hosted:details:type'] = 'Hosting type';
$string['setup:step2:hosted:details:type:value'] = 'Managed by MooPlugins';
$string['setup:step2:hosted:details:storage'] = 'Storage available';
$string['setup:step2:hosted:details:storage:value'] = '5 GB';
$string['setup:step2:hosted:details:upload'] = 'Monthly upload limit';
$string['setup:step2:hosted:details:upload:value'] = '1 GB';
$string['setup:step2:hosted:features:title'] = 'Features';
$string['setup:step2:hosted:feature1'] = 'Video Library';
$string['setup:step2:hosted:feature2'] = 'Transcoding & streaming';
$string['setup:step2:hosted:feature3'] = 'Subtitles & analytics';
$string['setup:step2:hosted:documentation:title'] = 'Documentation';
$string['setup:step2:hosted:documentation:uploading'] = 'Uploading Videos to the Video Library';
$string['setup:step2:hosted:documentation:creating'] = 'Creating a Video Lesson Activity';
$string['setup:step2:hosted:gotolibrary'] = 'Go to Video Library';
$string['setup:step2:hosted:activation:success'] = 'Hosting activated successfully!';
$string['setup:step2:hosted:activation:error'] = 'Failed to activate free hosting. Please try again.';
$string['setup:step2:hosted:license:key:label'] = 'License Key';
$string['setup:step2:hosted:license:key:copy'] = 'Copy';
$string['setup:step2:hosted:license:key:help'] = 'Your license key has been automatically saved. You can also find it in the plugin settings.';
$string['setup:step2:hosted:license:key:copied'] = 'License key copied to clipboard!';
$string['setup:step2:hosted:has:existing:license'] = 'I already have a license';
$string['setup:step2:hosted:existing:license:key:label'] = 'License Key';
$string['setup:step2:hosted:existing:license:key:placeholder'] = 'Enter your license key';
$string['setup:step2:hosted:existing:license:key:help'] = 'If you already have a license key, enter it here to activate it instead of creating a new one.';
$string['setup:step2:hosted:existing:license:key:required'] = 'Please enter your license key.';
$string['setup:step2:external:heading'] = 'External Video Sources';
$string['setup:step2:external:description'] = 'You\'re continuing without a hosting plan. In this mode, Video Lesson will work with external video links only.';
$string['setup:step2:external:limitations:title'] = 'Limitations';
$string['setup:step2:external:limitation1'] = 'Video Library: Not available';
$string['setup:step2:external:limitation2'] = 'Video uploads: Not available';
$string['setup:step2:external:limitation3'] = 'Subtitles & transcoding: Not available';
$string['setup:step2:external:limitation4'] = 'Completion tracking: Limited (based on playback only)';
$string['setup:step2:external:documentation:title'] = 'Documentation';
$string['setup:step2:external:documentation:creating'] = 'Creating a Video Lesson Activity';
$string['setup:step2:external:documentation:external'] = 'Using the External link as Video Source';
$string['setup:step2:external:complete'] = 'Complete Setup';

$string['setup:step3:title'] = 'Complete';
$string['setup:step3:complete:title'] = 'Setup Complete!';
$string['setup:step3:complete:description'] = 'Congratulations! Your Video Lesson plugin is now set up and ready to use.';
$string['setup:step3:documentation:title'] = 'Documentation';
$string['setup:step3:documentation:uploading'] = 'Uploading Videos to the Video Library';
$string['setup:step3:documentation:creating'] = 'Creating a Video Lesson Activity';
$string['setup:step3:gotolibrary'] = 'Go to Video Library';
$string['setup:step3:not_applicable'] = 'Setup is complete. For free hosting and external link options, completion is handled in Step 2.';

$string['setup:next'] = 'Next';
$string['setup:previous'] = 'Previous';
$string['setup:consent:title'] = 'Data Sharing Consent';
$string['setup:consent:description'] = 'To generate a free license, we need to register your site with <a href="https://mooplugins.com" target="_blank">Mooplugins.com</a>. This requires sharing the following information:';
$string['setup:consent:warning:title'] = 'Information to be shared:';
$string['setup:consent:warning:item1'] = 'Your Moodle administrator name and email address';
$string['setup:consent:warning:item2'] = 'Your Moodle site URL';
$string['setup:consent:warning:item3'] = 'The generated license key';
$string['setup:consent:warning:footer'] = 'This information will be used to create your account on <a href="https://mooplugins.com" target="_blank">Mooplugins.com</a> and manage your license.';
$string['setup:consent:agree'] = 'I agree to share this information with <a href="https://mooplugins.com" target="_blank">Mooplugins.com</a>';
$string['setup:consent:accept'] = 'Accept & Generate License';
$string['setup:required'] = 'Please complete the Video Lesson plugin setup before accessing this page.';

// Action handler strings
$string['upload:new:video'] = 'Upload new video';
$string['error:invalidsesskey'] = 'Invalid session key. Please try again.';
$string['error:file_not_found'] = 'File not found.';
$string['retry:scheduled'] = 'The file is scheduled for a retry upload. Please wait as we work to complete the process.';
$string['error:action_not_found'] = 'Action not found: {$a}';
$string['error:nocap:access'] = 'You are not allowed to access that page!';
$string['error:library:external:not_available'] = 'Video Library is not available when using external video links only. Please change your hosting option in the <a href="{$a}" target="_blank">settings</a> page to enable Video Library.';
$string['error:upload:external:not_available'] = 'Video uploads are not available when using external video links only. Please change your hosting option in the <a href="{$a}" target="_blank">settings</a> page to enable video uploads.';
$string['error:invalid:option'] = 'Invalid hosting option selected.';
$string['error:aws:required:fields'] = 'Please fill in all required AWS settings.';
$string['error:aws:connection:failed'] = 'Failed to validate AWS connection. Please check your credentials and try again.';

// Capabilities
$string['videolesson:addinstance'] = 'Add a new Video Lesson activity';
$string['videolesson:manage'] = 'Manage Video Lesson library and settings';
$string['videolesson:reports'] = 'View Video Lesson reports';
