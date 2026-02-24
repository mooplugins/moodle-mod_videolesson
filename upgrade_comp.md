# Video Lesson Plugin - Upgrade & Compatibility Notes

## 1️⃣ Upgrade Philosophy (Important Context)

### Backward Compatibility
- Plugin upgrades are designed to be backward-compatible wherever possible
- Database schema changes are handled automatically through upgrade scripts
- Data migrations are performed automatically during upgrade
- No data loss occurs during standard upgrades

### Why Release Notes Matter
- Breaking changes are clearly documented in release notes
- New features may require configuration changes
- Deprecations are announced in advance
- Security fixes are highlighted

### Data Safety Commitment
- No automatic deletion of user data
- Video files stored in AWS S3 are preserved
- Activity instances remain intact
- Analytics and usage data are retained

---

## 2️⃣ Supported Upgrade Paths

### Plugin Version Compatibility
- **Current Version:** `1.0.0` (version code: `2025121104`)
- **Minimum Moodle Version:** `4.1+` (requires: `2022112800`)
- **Plugin Dependency:** `local_aws` version `2022011301` or higher

### Recommended Upgrade Order

**Option 1: Plugin First (Recommended)**
1. Upgrade Video Lesson plugin
2. Run database upgrades
3. Upgrade Moodle core
4. Verify functionality

**Option 2: Moodle First**
1. Upgrade Moodle core
2. Verify Moodle compatibility
3. Upgrade Video Lesson plugin
4. Run database upgrades

**Why Plugin First?**
- Plugin upgrades are tested against current Moodle versions
- Database changes are isolated
- Easier to troubleshoot plugin-specific issues

---

## 3️⃣ Data Safety & Retention

### Data Stored Inside Moodle Database

**Core Activity Data** (`videoaws` table):
- Activity instances (name, description, settings)
- Course associations
- Completion progress settings
- Player options and configurations
- ✅ **Preserved during upgrades**

**Video Metadata** (`videoaws_data` table):
- File contenthash (unique identifier)
- Video duration, bitrate, dimensions
- Stream information (video/audio)
- Full metadata in JSON format
- ✅ **Preserved during upgrades**

**Conversion Tracking** (`videoaws_conv` table):
- Upload and processing status
- MediaConvert vs Elastic Transcoder flags
- Subtitle information
- Processing timestamps
- ✅ **Preserved during upgrades** (with status code migrations)

**External Video Data** (`videoaws_data_external` table):
- YouTube/Vimeo video IDs
- Direct URL sources
- External video metadata
- ✅ **Preserved during upgrades**

**Analytics & Usage** (`videoaws_usage` table):
- User watch duration
- Session tracking
- Platform/browser information
- Geographic data
- ✅ **Preserved during upgrades** (with format migrations)

**Progress Tracking** (`videoaws_cm_progress` table):
- User completion progress per activity
- ✅ **Preserved during upgrades**

**Folder Organization** (`videoaws_folders`, `videoaws_folder_items`):
- Video folder hierarchy
- Folder assignments
- Sort orders
- ✅ **Preserved during upgrades**

**Subtitle Tracking** (`videoaws_subtitles` table):
- Subtitle generation requests
- Language codes
- Processing status
- ✅ **Preserved during upgrades** (migrated from old format)

### Data Stored in AWS S3

**Video Files:**
- Original uploaded files (input bucket)
- Transcoded HLS streams (output bucket)
- Thumbnails and preview images
- Subtitle files (.vtt)
- ✅ **Never deleted automatically**
- ✅ **Independent of Moodle upgrades**

**Important:** Video files in S3 are not affected by Moodle or plugin upgrades. They remain accessible as long as:
- AWS credentials remain valid
- S3 buckets are not manually deleted
- CloudFront distribution is active

### What is Never Deleted Automatically

- ✅ Video files in AWS S3
- ✅ Activity instances in courses
- ✅ User analytics and watch data
- ✅ Video metadata
- ✅ Folder organization
- ✅ Subtitle files

### What May Change During Upgrades

- Status code values (e.g., `uploaded = 1` → `uploaded = 200`)
- Data format normalization (e.g., embed codes: `youtube:VIDEO_ID`)
- Table structure additions (new fields, indexes)
- Configuration value migrations

---

## 4️⃣ Compatibility Matrix

### Moodle Version Support

| Moodle Version | Plugin Support | Notes |
|---------------|----------------|-------|
| 4.1.x (2022112800+) | ✅ Full Support | Minimum required version |
| 4.2.x | ✅ Full Support | Tested and compatible |
| 4.3.x | ✅ Full Support | Tested and compatible |
| 4.4.x | ✅ Full Support | Tested and compatible |
| 5.0.x | ✅ Full Support | Current development target |

### PHP Compatibility

- **PHP 7.4+** (Moodle 4.1 requirement)
- **PHP 8.0+** (Recommended)
- **PHP 8.1+** (Recommended for Moodle 4.2+)
- **PHP 8.2+** (Recommended for Moodle 4.4+)

**Required PHP Extensions:**
- `curl` (for AWS API calls)
- `json` (for API responses)
- `ffprobe` binary (for video metadata extraction)

### Hosting Plan Compatibility

**Self-Managed Hosting:**
- Full control over upgrades
- Manual upgrade process required
- Customer responsible for backups
- AWS credentials must be maintained

**MooPlugins Managed Hosting:**
- Automatic plugin upgrades (when available)
- MooPlugins handles AWS infrastructure
- Customer responsible for Moodle core upgrades
- Coordinated upgrade windows

---

## 5️⃣ Upgrading the Plugin

### Method 1: Via Moodle Plugin Installer (Recommended)

1. **Download Latest Version**
   - Get plugin ZIP from MooPlugins
   - Ensure version compatibility with your Moodle version

2. **Access Plugin Installer**
   - Go to: **Site administration → Plugins → Install plugins**
   - Upload the plugin ZIP file
   - Moodle will detect it's an upgrade

3. **Automatic Database Upgrade**
   - Moodle runs `db/upgrade.php` automatically
   - Progress shown on screen
   - Check for any error messages

4. **Verify Upgrade**
   - Check plugin version in **Site administration → Plugins → Plugins overview**
   - Verify version shows: `1.0.0` (or latest)
   - Test video playback in a course

### Method 2: Manual Upgrade

1. **Backup First**
   ```bash
   # Backup Moodle database
   mysqldump -u username -p moodle_db > backup_before_upgrade.sql

   # Backup plugin directory (optional, for rollback)
   cp -r mod/videoaws mod/videoaws_backup
   ```

2. **Extract New Version**
   ```bash
   cd /path/to/moodle/mod
   # Remove old version (or rename)
   rm -rf videoaws
   # Extract new version
   unzip videoaws_new_version.zip
   ```

3. **Run Database Upgrade**
   - Visit: `https://yoursite.com/admin/index.php`
   - Moodle will detect the upgrade
   - Click "Upgrade Moodle database now"
   - Or run CLI: `php admin/cli/upgrade.php`

4. **Clear Caches**
   ```bash
   php admin/cli/purge_caches.php
   ```

### Database Upgrade Process

The upgrade script (`db/upgrade.php`) performs:

1. **Schema Changes**
   - Adds new tables (folders, subtitles, etc.)
   - Adds new fields to existing tables
   - Creates indexes for performance
   - Removes deprecated fields

2. **Data Migrations**
   - Status code updates (`uploaded: 1 → 200`, `0 → 202`)
   - Embed code normalization (`youtube:VIDEO_ID` format)
   - Subtitle data migration (from `videoaws_conv.subtitle` to `videoaws_subtitles` table)
   - Usage data format updates
   - Configuration value migrations

3. **Savepoint Creation**
   - Each upgrade step creates a savepoint
   - Allows rollback if needed
   - Version tracking in `config_plugins` table

### Verifying Successful Upgrade

**Check Plugin Version:**
- **Site administration → Plugins → Plugins overview → Activity modules → Video Lesson**
- Should show: `1.0.0` (version `2025121104`)

**Check Database Tables:**
```sql
-- Verify all tables exist
SHOW TABLES LIKE 'videoaws%';

-- Check version in config
SELECT * FROM {config_plugins} WHERE plugin = 'mod_videoaws' AND name = 'version';
```

**Functional Tests:**
- ✅ Access Video Library page
- ✅ Upload a test video
- ✅ Play existing videos
- ✅ Check folder organization
- ✅ Verify analytics/reports
- ✅ Test subtitle functionality

---

## 6️⃣ Upgrading Moodle

### Known Moodle Changes Affecting Video Lesson

**Moodle 4.0 → 4.1:**
- Editor API changes (TinyMCE integration)
- Navigation system updates
- ✅ Plugin compatible

**Moodle 4.1 → 4.2:**
- Completion API refinements
- ✅ Plugin compatible

**Moodle 4.2 → 4.3:**
- File API updates
- ✅ Plugin compatible

**Moodle 4.3 → 4.4:**
- Public directory structure changes
- ✅ Plugin compatible (uses standard Moodle file API)

**Moodle 4.4 → 5.0:**
- Major version upgrade
- ⚠️ Test thoroughly in staging environment first

### Pre-Upgrade Checklist

**Before Upgrading Moodle:**

1. ✅ **Backup Everything**
   - Full Moodle database backup
   - Moodle data directory backup
   - Plugin directory backup

2. ✅ **Check Plugin Compatibility**
   - Verify Video Lesson supports target Moodle version
   - Check `version.php`: `$plugin->requires = 2022112800` (Moodle 4.1+)

3. ✅ **Review Dependencies**
   - Ensure `local_aws` plugin is compatible
   - Check PHP version compatibility
   - Verify FFProbe is still accessible

4. ✅ **Test in Staging**
   - Always test Moodle upgrade in staging first
   - Test video upload/playback
   - Verify analytics still work
   - Check folder organization

### Post-Upgrade Verification

**After Upgrading Moodle:**

1. **Run Moodle Database Upgrade**
   - Visit `/admin/index.php`
   - Follow upgrade prompts

2. **Verify Plugin Still Works**
   - Check plugin is still enabled
   - Test video library access
   - Verify AWS connections still work

3. **Check for Deprecation Warnings**
   - Review Moodle error logs
   - Check for PHP deprecation notices
   - Address any compatibility issues

---

## 7️⃣ Breaking Changes & Deprecations

### How Breaking Changes are Communicated

- **Release Notes:** Published with each version
- **Changelog:** Available in plugin repository
- **Email Notifications:** For major version releases
- **Documentation Updates:** Reflected in user guides

### Recent Breaking Changes

**Version 1.0.0 (2025121104):**
- ✅ None - Stable release

**Previous Versions:**
- Removed deprecated AWS Rekognition fields (2024080800)
- Removed `videoaws_presets` table (2024080800)
- Status code changes: `uploaded = 1` → `200`, `0` → `202` (2025061800)
- Embed code format normalization (2025120802)
- Source type change: `embed` → `external` (2025120900)

### Support Policy

- **Current Version:** Full support
- **Previous Major Version:** Security fixes only (6 months)
- **Older Versions:** No support (upgrade required)

### Finding Changelogs

- Plugin repository: Check version history
- Release notes: Included with download
- GitHub (if applicable): Commit history
- MooPlugins support: Contact for specific version notes

---

## 8️⃣ Rollback & Recovery

### If Plugin Upgrade Fails

**Step 1: Check Error Messages**
- Review upgrade output for specific errors
- Check Moodle error logs: `$CFG->dataroot/error_log`
- Check database for failed savepoints

**Step 2: Manual Rollback**

```bash
# Restore plugin directory
cd /path/to/moodle/mod
rm -rf videoaws
cp -r videoaws_backup videoaws

# Restore database (if needed)
mysql -u username -p moodle_db < backup_before_upgrade.sql

# Clear caches
php admin/cli/purge_caches.php
```

**Step 3: Identify Issue**
- Check PHP error logs
- Verify database permissions
- Check disk space
- Review Moodle version compatibility

### Restoring from Moodle Backup

**Full Site Restore:**
1. Restore Moodle database backup
2. Restore `moodledata` directory
3. Restore plugin files
4. Run Moodle upgrade if needed
5. Clear all caches

**Partial Restore (Plugin Only):**
- Restore `mod/videoaws` directory
- Restore plugin-specific config: `SELECT * FROM {config_plugins} WHERE plugin = 'mod_videoaws'`
- Note: Video files in S3 are unaffected

### When to Contact MooPlugins Support

**Contact Support If:**
- ❌ Database upgrade fails with errors
- ❌ Videos stop playing after upgrade
- ❌ AWS connections break
- ❌ Data appears to be missing
- ❌ Upgrade script hangs or times out
- ❌ Incompatibility with specific Moodle version

**Support Information to Provide:**
- Moodle version
- Plugin version (before and after)
- Error messages/logs
- Database type and version
- PHP version
- Steps to reproduce issue

---

## 9️⃣ Managed Hosting Considerations

### What MooPlugins Handles Automatically

**Infrastructure:**
- ✅ AWS S3 bucket management
- ✅ CloudFront distribution
- ✅ MediaConvert job processing
- ✅ SQS queue management
- ✅ SNS notifications

**Plugin Updates:**
- ✅ Automatic plugin upgrades (when available)
- ✅ Database upgrade execution
- ✅ Post-upgrade verification
- ✅ Rollback if issues detected

**Monitoring:**
- ✅ Video processing status
- ✅ Error detection and alerting
- ✅ Performance monitoring

### What Customers Are Responsible For

**Moodle Core:**
- ⚠️ Moodle core upgrades (customer responsibility)
- ⚠️ PHP version updates
- ⚠️ Server maintenance
- ⚠️ Moodle database backups

**Configuration:**
- ⚠️ License key management
- ⚠️ Plugin settings configuration
- ⚠️ Course and activity management

**Testing:**
- ⚠️ Staging environment testing (if available)
- ⚠️ User acceptance testing
- ⚠️ Reporting issues to support

### Downtime Expectations

**Plugin Upgrades:**
- **Typical Downtime:** 0-5 minutes
- **Database Upgrades:** Usually < 1 minute
- **Large Migrations:** May take longer (notified in advance)

**Moodle Core Upgrades:**
- **Customer Responsibility:** Plan downtime window
- **Recommendation:** Schedule during low-traffic periods
- **Testing:** Always test in staging first

**Coordinated Upgrades:**
- MooPlugins can coordinate with customer for major upgrades
- Advance notice provided for planned maintenance
- Emergency upgrades: Immediate notification

---

## 📋 Quick Reference Checklist

### Before Any Upgrade

- [ ] Full database backup
- [ ] Moodle data directory backup
- [ ] Plugin directory backup
- [ ] Verify disk space available
- [ ] Check PHP version compatibility
- [ ] Review release notes
- [ ] Test in staging environment (if available)

### During Upgrade

- [ ] Follow upgrade prompts carefully
- [ ] Monitor for error messages
- [ ] Note any warnings
- [ ] Wait for completion confirmation

### After Upgrade

- [ ] Verify plugin version
- [ ] Test video upload
- [ ] Test video playback
- [ ] Check analytics/reports
- [ ] Verify folder organization
- [ ] Clear all caches
- [ ] Check error logs

---

## 🔗 Additional Resources

- **MooPlugins Support:** [Contact Information]
- **Plugin Documentation:** [Documentation URL]
- **Moodle Upgrade Guide:** https://docs.moodle.org/en/Upgrading
- **Release Notes:** [Release Notes URL]

---

**Last Updated:** Based on plugin version `1.0.0` (2025121104)
**Moodle Compatibility:** 4.1+ (2022112800+)
**Plugin Dependency:** `local_aws` 2022011301+

This documentation is based on the current codebase structure. For the most up-to-date information, always refer to the official release notes and changelog for your specific version.
