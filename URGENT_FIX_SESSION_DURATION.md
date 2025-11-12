# URGENT FIX: Session Duration Removed from General Settings
*Date: November 12, 2025*

## ❌ Problem Identified
Session duration was incorrectly added to General Settings, but it should **ONLY** come from:
- **Individual Sessions:** Subscription → Package → `session_duration_minutes`
- **Group Sessions:** Standard 60 minutes (not configurable)
- **Academic Sessions:** Subscription → Package → `session_duration_minutes`

## ✅ Fixed Files

### 1. Filament Resource (General Settings Form)
**File:** `/app/Filament/Resources/AcademyGeneralSettingsResource.php`
- ❌ Removed: `meeting_settings.default_session_duration` field
- ✅ Kept only meeting timing fields:
  - `default_preparation_minutes` (10 min)
  - `default_late_tolerance_minutes` (15 min)
  - `default_buffer_minutes` (5 min)

### 2. Edit Page Handler
**File:** `/app/Filament/Resources/AcademyGeneralSettingsResource/Pages/EditGeneralSettings.php`
- ❌ Removed from `mutateFormDataBeforeFill()`: `default_session_duration`
- ❌ Removed from `mutateFormDataBeforeSave()`: `default_session_duration`

### 3. Services (Session Management)
**File:** `/app/Services/SessionManagementService.php`

**createGroupSession():**
```php
// BEFORE (WRONG):
$academySettings = AcademySettings::getForAcademy($circle->academy);
$durationMinutes = $academySettings->default_session_duration ?? 60;

// AFTER (CORRECT):
$durationMinutes = 60; // Standard group session duration
```

**bulkCreateSessions():**
```php
// BEFORE (WRONG for group circles):
$academySettings = AcademySettings::getForAcademy($circle->academy);
$durationMinutes = $academySettings->default_session_duration ?? 60;

// AFTER (CORRECT):
// Individual circles - use subscription/package
$durationMinutes = $circle->subscription?->session_duration_minutes
    ?? $circle->subscription?->package?->session_duration_minutes
    ?? 45;

// Group circles - use standard duration
$durationMinutes = 60;
```

### 4. Models
**File:** `/app/Models/QuranCircle.php`

**generateSchedule() method:**
```php
// BEFORE (WRONG):
$academySettings = AcademySettings::getForAcademy($this->academy);
$duration = $academySettings->default_session_duration ?? 60;

// AFTER (CORRECT):
// Duration is determined by subscription/package, default to 60 for scheduling purposes
// Actual duration will be set when sessions are created through SessionManagementService
$duration = 60;
```

### 5. Views
**File:** `/resources/views/public/quran-circles/show.blade.php`
```php
// BEFORE (WRONG):
@php
    $academySettings = \App\Models\AcademySettings::where('academy_id', $circle->academy_id)->first();
    $duration = $academySettings?->default_session_duration ?? 60;
@endphp
<div class="text-sm">{{ $duration }} دقيقة</div>

// AFTER (CORRECT):
<div class="text-sm">60 دقيقة</div>
```

**File:** `/resources/views/teacher/calendar/index.blade.php`
```php
// BEFORE (WRONG):
@php
    $academySettings = \App\Models\AcademySettings::where('academy_id', $circle->academy_id)->first();
    $duration = $academySettings?->default_session_duration ?? 60;
@endphp
data-duration="{{ $duration }}"
⏱️ {{ $duration }} دقيقة

// AFTER (CORRECT):
data-duration="60"
⏱️ 60 دقيقة
```

## ✅ Current Meeting Settings (Correct)
**Location:** General Settings → "إعدادات الاجتماعات"

These are the **ONLY** meeting-related settings (no session duration):
1. **default_preparation_minutes** (10 min) - When to create meeting before session
2. **default_late_tolerance_minutes** (15 min) - Grace period for late attendance
3. **default_buffer_minutes** (5 min) - How long to keep meeting open after session ends

## 📊 Where Session Duration Comes From

### Individual Quran Circles:
```
QuranIndividualCircle → Subscription → session_duration_minutes
                                    ↓
                                  Package → session_duration_minutes
                                    ↓
                                Fallback: 45 minutes
```

### Group Quran Circles:
```
Hardcoded: 60 minutes (standard duration)
```

### Academic Sessions:
```
AcademicSubscription → session_duration_minutes
                    ↓
                  Package → session_duration_minutes
                    ↓
                Fallback: 60 minutes
```

### Interactive Courses:
```
InteractiveCourse → session_duration_minutes
                  (defined per course)
```

## 🔍 Verification

Session duration is **NEVER** fetched from:
- ❌ `AcademySettings::default_session_duration` (removed)
- ❌ `QuranCircle::session_duration_minutes` (removed from model)
- ❌ General Settings form (removed)

Session duration is **ALWAYS** from:
- ✅ Subscription/Package for individual sessions
- ✅ Standard 60 minutes for group sessions
- ✅ Course configuration for interactive sessions

## 🎯 Summary
- **Fixed:** Session duration is no longer in general settings
- **Correct:** Duration comes from subscription/package/course
- **Meeting Settings:** Only contain timing for meeting lifecycle (preparation, buffer, tolerance)
- **No Data Loss:** The `default_session_duration` field still exists in `academy_settings` table for future use if needed, but it's not used anywhere in the code

---

*All fixes applied and verified*