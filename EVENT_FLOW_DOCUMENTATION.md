# Video Player Event Flow Documentation

## Initialization Phase

### 1. **Plyr Instance Created** (`init()` function called)
**Location:** Line 837

**What happens:**
- Sets `tracking = params.tracking`
- Gets video element: `video = document.getElementById("player")`
- Determines if native video: `mediaElement = video.tagName === 'VIDEO' ? video : null`
- Initializes DOM references (chart, percentageEl, etc.)
- Creates `videoData` object with initial values:
  - `watchduration: 0`
  - `ranges: []`
  - `playbackEvents: []`
  - `previousTime = 0` (global variable)
  - `isPlaying = false` (global variable)
  - `fromSeek = false` (global variable)
  - `playbackRanges = []` (global variable for manual tracking)

**State after:**
- Video element found
- `videoData` initialized
- No ranges tracked yet

---

### 2. **Player Initialized** (`initializePlayer()` promise resolves)
**Location:** Line 907-915

**What happens:**
- Plyr player instance created: `videoPlayer = videoPlyr`
- Player container shown, placeholder hidden
- Event handlers set up (see below)

**State after:**
- `videoPlayer` available
- Event listeners attached

---

## Event Handlers Setup

### 3. **Event Listeners Attached** (`setupEventHandlers()`)
**Location:** Line 817-824

**Events registered:**
- `ready`
- `play`
- `pause`
- `ended`
- `seeking`
- `seeked`
- `timeupdate`
- `volumechange`
- `qualitychange`
- `error`
- `visibilitychange` (document-level)

---

## Event Flow Details

### 4. **`ready` Event**
**Location:** Line 555-567

**What happens:**
1. Calls `mediaData()` to get duration (async)
2. Logs media data
3. Calls `sendDataToServer(videoData)` immediately

**State changes:**
- `videoData.duration` may be updated
- Data sent to server (watchduration = 0 at this point)

**Note:** No ranges tracked yet

---

### 5. **`play` Event**
**Location:** Line 573-594

**What happens:**
1. Sets `isPlaying = true`
2. Adds playback event: `{event_type: 'play', timestamp, position}`
3. Sets `previousTime = videoPlayer.currentTime` (line 585)
4. If `fromSeek === true`:
   - Sets `fromSeek = false`
   - Returns early (doesn't send data)
5. If not from seek: calls `sendDataToServer(videoData)`

**State changes:**
- `isPlaying = true`
- `previousTime` updated to current position
- `fromSeek` cleared if it was set

**⚠️ Issue:** `previousTime` is set here, but if play happens after a seek, this might overwrite the value set in `seeked`

---

### 6. **`pause` Event**
**Location:** Line 596-613

**What happens:**
1. Sets `isPlaying = false`
2. If `fromSeek === true`:
   - Sets `fromSeek = false`
   - Returns early (doesn't log or send)
3. If not from seek:
   - Adds playback event: `{event_type: 'pause', timestamp, position}`
   - Calls `sendDataToServer(videoData)`

**State changes:**
- `isPlaying = false`
- `fromSeek` cleared if it was set

---

### 7. **`seeking` Event** (User starts seeking)
**Location:** Line 634-646

**What happens:**
1. Logs seeking event
2. Sets `isPlaying = false`
3. If `seekStart === null`:
   - Sets `seekStart = previousTime` (captures where seek started from)
4. Sets `fromSeek = true`

**State changes:**
- `isPlaying = false`
- `seekStart = previousTime` (e.g., 418.078)
- `fromSeek = true`

**⚠️ Critical:** `previousTime` is NOT updated here. It still holds the old value.

---

### 8. **`seeked` Event** (User finishes seeking)
**Location:** Line 648-676

**What happens:**
1. Logs seeked event
2. Gets `seekPosition = videoPlayer.currentTime` (e.g., 798.032)
3. Adds seek event: `{start: seekStart, position: seekPosition, progress}`
4. Updates `video.dataset.maxwatched` if seeked to new maximum
5. Sets `previousTime = seekPosition` (line 670)
6. Sets `fromSeek = false`
7. Sets `seekStart = null`
8. Sets `isPlaying = !videoPlayer.paused`

**State changes:**
- `previousTime = seekPosition` (e.g., 798.032)
- `fromSeek = false`
- `seekStart = null`
- `isPlaying` updated based on paused state

**⚠️ Critical:** `previousTime` is updated here, but there's a race condition with `play` event

---

### 9. **`timeupdate` Event** (Fires continuously during playback)
**Location:** Line 683-731

**What happens:**
1. Gets `newTime = videoPlayer.currentTime`
2. Debug logging (every 5 seconds)
3. Manual range tracking (for embedded videos only):
   ```javascript
   if (isPlaying && !fromSeek && !mediaElement) {
       addPlaybackRange(previousTime, newTime);
   }
   ```
   - Only runs for embedded videos (YouTube/Vimeo)
   - Only if `isPlaying === true` AND `fromSeek === false`
   - Adds range from `previousTime` to `newTime`
4. Calls `handleTimeUpdate()` (debounced chart update)
5. If `fromSeek === true`:
   - Sets `fromSeek = false`
   - Sets `previousTime = newTime`
   - Returns early
6. Updates `video.dataset.maxwatched` if new maximum reached
7. Sets `previousTime = newTime` (line 721)
8. If interval reached (10 seconds): calls `sendDataToServer(videoData)`

**State changes:**
- `previousTime = newTime` (updated every timeupdate)
- `fromSeek` cleared if it was set
- Range added to `playbackRanges` (embedded videos only)

**⚠️ Issues:**
- Line 700: Manual range tracking only for embedded videos
- Line 708: `previousTime` updated when `fromSeek` is true, but this might conflict with `seeked` handler
- Line 721: `previousTime` always updated at end, which might overwrite seeked position

---

### 10. **`handleTimeUpdate()` Function** (Debounced chart update)
**Location:** Line 178-197

**What happens:**
1. Clears existing timeout
2. Sets timeout (debounced, CHART_UPDATE_DEBOUNCE ms)
3. When timeout fires:
   - Gets current ranges:
     - If `mediaElement.played` exists: uses `copyRanges(mediaElement.played)` (native API)
     - Otherwise: uses `getPlaybackRangesSnapshot()` (manual tracking)
   - Calls `updateChart([currentRanges], existingRanges)`

**State changes:**
- Chart updated with current ranges
- `videoData.watchduration` recalculated in `updateChart`

---

### 11. **`updateChart()` Function**
**Location:** Line 201-278

**What happens:**
1. Gets video duration
2. Checks if data changed (hash comparison)
3. Updates chart DOM
4. Recalculates ranges:
   - If `mediaElement.played` exists: uses native API
   - Otherwise: uses `getPlaybackRangesSnapshot()`
5. Calculates `watchduration`:
   ```javascript
   videoData.watchduration = calculateProgress(vduration, [currentRanges], false);
   ```
6. Updates `videoData.ranges = [currentRanges]`
7. Calls `updateProgressOnly()`

**State changes:**
- `videoData.watchduration` updated
- `videoData.ranges` updated
- Chart DOM updated

---

### 12. **`sendDataToServer()` Function**
**Location:** Line 384-450

**What happens:**
1. Checks if tracking enabled
2. Recalculates `watchduration` before sending:
   - If `mediaElement.played` exists: uses native API
   - Otherwise: uses `getPlaybackRangesSnapshot()`
   - Calculates: `data.watchduration = calculateProgress(duration, [currentRanges], false)`
   - Sets: `data.ranges = [currentRanges]`
3. Queues data if another request is in progress
4. Sends AJAX request
5. On success: processes queue, shows notifications
6. On failure: retries with exponential backoff

**State changes:**
- `watchduration` recalculated from current ranges
- Data sent to server

---

## Problem Analysis

### Issue 1: Race Condition Between `seeked` and `play` Events

**Scenario:**
1. User seeks from 418.078 to 798.032
2. `seeking` fires → `fromSeek = true`, `seekStart = 418.078`
3. `seeked` fires → `previousTime = 798.032`, `fromSeek = false`
4. `play` fires → `previousTime = 798.032` (overwrites, but same value)
5. `timeupdate` fires → if `fromSeek` was somehow still true, it sets `previousTime = newTime`

**Problem:** `play` handler sets `previousTime` again (line 585), which might conflict with `seeked` handler.

---

### Issue 2: `timeupdate` Clears `fromSeek` Flag

**Scenario:**
1. `seeking` fires → `fromSeek = true`
2. `seeked` fires → `fromSeek = false`, `previousTime = 798.032`
3. `timeupdate` fires → checks `fromSeek` (line 706)
   - If somehow `fromSeek` is still true, it sets `previousTime = newTime` and returns
   - This might overwrite the value set in `seeked`

**Problem:** Multiple places are clearing `fromSeek` and updating `previousTime`, causing conflicts.

---

### Issue 3: Manual Range Tracking for Embedded Videos

**Scenario (Embedded Video - YouTube/Vimeo):**
1. User watches 1-5 seconds
2. `timeupdate` fires → `addPlaybackRange(0, 1)`, `addPlaybackRange(1, 2)`, etc.
3. User seeks to 20 seconds
4. `seeking` fires → `fromSeek = true`, `seekStart = 5`
5. `seeked` fires → `previousTime = 20`, `fromSeek = false`
6. `play` fires → `previousTime = 20` (overwrites, but same value)
7. `timeupdate` fires → if `fromSeek` is false, it might add range `[5, 20]` if `previousTime` wasn't updated correctly

**Problem:** For embedded videos, manual tracking depends on `previousTime` being correct, but multiple events update it.

---

### Issue 4: Native API vs Manual Tracking

**For Native Video Elements:**
- Uses `mediaElement.played` API (browser tracks ranges automatically)
- Should handle seeks correctly
- But `timeupdate` still updates `previousTime` which might not be needed

**For Embedded Videos:**
- Uses manual `playbackRanges` array
- Depends on `previousTime` being accurate
- Multiple events update `previousTime`, causing conflicts

---

## Recommendations

1. **Remove `previousTime` update from `play` handler** - Let `seeked` handle it
2. **Remove `fromSeek` clearing from `timeupdate`** - Let `seeked` handle it
3. **Ensure `previousTime` is only updated in `seeked` and `timeupdate` (during normal playback)**
4. **Add validation to prevent adding ranges with large time jumps (likely seeks)**
5. **Consider using `video.played` API for all video types if possible, or improve manual tracking logic**

---

## Key Variables

### Global State Variables:
- `previousTime` - Last known playback position (used for range tracking)
- `isPlaying` - Whether video is currently playing
- `fromSeek` - Flag indicating if we're in a seek operation
- `seekStart` - Position where seek started from
- `playbackRanges` - Array of watched ranges `[[start, end], ...]` (for embedded videos)
- `lastSendTime` - Last time data was sent to server
- `videoData` - Object containing all tracking data
- `mediaElement` - Native video element (null for embedded videos)
- `videoPlayer` - Plyr player instance

### Range Tracking Methods:
- **Native Video:** Uses `mediaElement.played` TimeRanges API (automatic, accurate)
- **Embedded Video:** Uses manual `playbackRanges` array (requires careful `previousTime` management)

---

## Event Sequence Example: User Seeks During Playback

```
1. User watching at 418.078s
   - previousTime = 418.078
   - isPlaying = true
   - playbackRanges = [[0, 12.066], [369.629, 417.901]]

2. User seeks to 798.032s
   - seeking event fires
     → isPlaying = false
     → seekStart = 418.078
     → fromSeek = true

3. Video jumps to 798.032s
   - seeked event fires
     → previousTime = 798.032
     → fromSeek = false
     → seekStart = null
     → isPlaying = !videoPlayer.paused (true if auto-play)

4. play event fires (if auto-play)
   → isPlaying = true
   → previousTime = 798.032 (overwrites, but same value)
   → fromSeek = false (already false)

5. timeupdate fires
   → Checks: isPlaying=true, fromSeek=false, !mediaElement
   → If embedded: addPlaybackRange(798.032, 798.032 + delta)
   → previousTime = newTime (continues updating)
```

**Problem:** If `previousTime` wasn't updated correctly in step 3, step 5 might add incorrect range `[418.078, 798.032]` for embedded videos.

---

This documentation should help identify where the data is going wrong.

