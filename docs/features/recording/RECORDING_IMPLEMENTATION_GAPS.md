# Recording Implementation - Missing Parts Analysis

## Current Situation

The recording feature has **two parallel implementations** that need to be unified:

### System 1: OLD (Incomplete)
- **Controller**: `InteractiveCourseRecordingController`
- **Model**: `CourseRecording`
- **Status**: Has placeholder implementations with TODO comments
- **Lines 227-267**: Simulate recording, don't actually call LiveKit

### System 2: NEW (Complete but not connected)
- **Service**: `RecordingService` ✅ Fully implemented
- **Model**: `SessionRecording` ✅ Polymorphic, feature-complete
- **Trait**: `HasRecording` ✅ Provides recording methods to sessions
- **Interface**: `RecordingCapable` ✅ Implemented by InteractiveCourseSession
- **Webhook**: `LiveKitWebhookController::handleEgressEnded()` ✅ Processes webhooks

## Missing Parts Identified

### 1. Controller Integration ❌
**File**: `app/Http/Controllers/InteractiveCourseRecordingController.php`

**Problem**: Lines 225-267 have placeholder implementations:
```php
private function startLiveKitRecording(CourseRecording $recording): void
{
    // TODO: Implement actual LiveKit recording start
    \Log::info('Starting recording for session: ' . $recording->session_id);
}

private function stopLiveKitRecording(CourseRecording $recording): void
{
    // TODO: Implement actual LiveKit recording stop
    // Simulates file processing - in real implementation this would be done by a job
}
```

**Solution**: Replace placeholder methods with RecordingService calls

---

### 2. Missing Routes ❌
**File**: `routes/web.php`

**Problem**: SessionRecording model expects these routes (lines 340, 353):
```php
route('recordings.download', ['recording' => $this->id])
route('recordings.stream', ['recording' => $this->id])
```

**Current Status**: Routes don't exist ❌

**Found**: Routes reference LiveKitMeetingController methods that don't exist:
```php
Route::post('{sessionId}/recording/start', [\App\Http\Controllers\LiveKitMeetingController::class, 'startRecording'])
Route::post('{sessionId}/recording/stop', [\App\Http\Controllers\LiveKitMeetingController::class, 'stopRecording'])
```

**Solution**: Add recording download/stream routes

---

### 3. Missing LiveKitMeetingController Methods ❌
**File**: `app/Http/Controllers/LiveKitMeetingController.php`

**Problem**: Routes reference `startRecording()` and `stopRecording()` methods that don't exist in the controller

**Solution**: Add recording methods to LiveKitMeetingController OR remove these routes in favor of InteractiveCourseRecordingController

---

### 4. Server Configuration ❌
**Status**: Not yet executed

**Missing**:
1. ✅ Script created: `finalize-recording-setup.sh`
2. ❌ Not yet run on server
3. ❌ `egress.yaml` doesn't have correct API credentials
4. ❌ `livekit.yaml` doesn't have webhook URL configured

**Solution**: Run finalize-recording-setup.sh on server

---

### 5. Two Competing Models ⚠️
**Problem**: Both models exist:
- `CourseRecording` - old, specific to interactive courses
- `SessionRecording` - new, polymorphic, works with any RecordingCapable

**Recommendation**:
- **Short term**: Keep both, update InteractiveCourseRecordingController to use SessionRecording
- **Long term**: Migrate CourseRecording data to SessionRecording, deprecate old model

---

## What Works ✅

1. ✅ **RecordingService** - Fully implemented, ready to use
2. ✅ **SessionRecording Model** - Complete with all helper methods
3. ✅ **HasRecording Trait** - Provides recording methods to sessions
4. ✅ **RecordingCapable Interface** - Implemented by InteractiveCourseSession
5. ✅ **Webhook Handler** - LiveKitWebhookController processes egress_ended events
6. ✅ **LiveKitService** - Has startRecording() and stopRecording() methods
7. ✅ **Database Migration** - session_recordings table exists

---

## Fix Priority

### Priority 1: Make it work (TODAY)
1. Update InteractiveCourseRecordingController to use RecordingService
2. Add recordings download/stream routes
3. Run finalize-recording-setup.sh on server
4. Test end-to-end recording

### Priority 2: Clean up (NEXT SPRINT)
1. Decide on single recording model (recommend SessionRecording)
2. Migrate existing CourseRecording data
3. Consolidate controllers (one recording controller vs two)
4. Remove duplicate routes

---

## Implementation Plan

### Step 1: Fix InteractiveCourseRecordingController
Replace placeholder implementations with RecordingService integration.

### Step 2: Add Missing Routes
Add download/stream routes for SessionRecording model.

### Step 3: Configure Server
Run finalize-recording-setup.sh to configure egress and webhooks.

### Step 4: Test
Create comprehensive integration test.

---

## Testing Checklist

- [ ] Start recording via UI
- [ ] Verify SessionRecording record created with status='recording'
- [ ] Check LiveKit Egress container shows active recording
- [ ] Stop recording via UI
- [ ] Verify egress_ended webhook received
- [ ] Verify SessionRecording updated with file_path and status='completed'
- [ ] Download recording file
- [ ] Stream/play recording in browser

---

## Next Steps

Execute the implementation plan to unify the two systems and complete the recording feature.
