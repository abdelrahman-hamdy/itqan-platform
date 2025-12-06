# PHASE 5: SESSION MODELS ANALYSIS
## Common Patterns Across QuranSession, AcademicSession, InteractiveCourseSession

**Analysis Date:** November 11, 2024

---

## MODEL SIZES

- **QuranSession.php:** 1,858 lines
- **AcademicSession.php:** 889 lines
- **InteractiveCourseSession.php:** 347 lines
- **Total:** 3,094 lines

---

## COMMON FIELDS (To Be Moved to BaseSession)

### Core Session Fields:
```php
'academy_id',              // Present in all 3
'session_code',            // Present in all 3
'status',                  // Present in all 3 (SessionStatus enum)
'title',                   // Present in all 3
'description',             // Present in all 3
'scheduled_at',            // Present in QuranSession + AcademicSession
'started_at',              // Present in all 3
'ended_at',                // Present in all 3
'duration_minutes',        // Present in all 3
'actual_duration_minutes', // Present in QuranSession + AcademicSession
```

### Meeting Fields:
```php
'meeting_link',            // Present in all 3
'meeting_id',              // Present in all 3
'meeting_password',        // Present in QuranSession + AcademicSession
'meeting_source',          // Present in all 3
'meeting_platform',        // Present in QuranSession + AcademicSession
'meeting_data',            // Present in all 3
'meeting_room_name',       // Present in QuranSession + AcademicSession
'meeting_auto_generated',  // Present in QuranSession + AcademicSession
'meeting_expires_at',      // Present in QuranSession + AcademicSession
```

### Attendance Fields:
```php
'attendance_status',       // Present in all 3
'participants_count',      // Present in QuranSession + AcademicSession
'attendance_notes',        // Present in QuranSession + AcademicSession
```

### Feedback Fields:
```php
'session_notes',           // Present in QuranSession + AcademicSession
'teacher_feedback',        // Present in QuranSession + AcademicSession
'student_feedback',        // Present in QuranSession + AcademicSession
'parent_feedback',         // Present in QuranSession + AcademicSession
'overall_rating',          // Present in QuranSession + AcademicSession
```

### Cancellation Fields:
```php
'cancellation_reason',     // Present in QuranSession + AcademicSession
'cancelled_by',            // Present in all 3
'cancelled_at',            // Present in QuranSession + AcademicSession
```

### Tracking Fields:
```php
'created_by',              // Present in QuranSession + AcademicSession
'updated_by',              // Present in QuranSession + AcademicSession
'scheduled_by',            // Present in QuranSession + AcademicSession
```

### Rescheduling Fields:
```php
'reschedule_reason',       // Present in QuranSession + AcademicSession
'rescheduled_from',        // Present in QuranSession + AcademicSession
'rescheduled_to',          // Present in QuranSession + AcademicSession
```

### Homework Fields (Optional - needs discussion):
```php
'homework_assigned',       // Present in all 3 (but different types: array vs boolean)
```

---

## COMMON CASTS (To Be Moved to BaseSession)

```php
'status' => SessionStatus::class,
'scheduled_at' => 'datetime',
'started_at' => 'datetime',
'ended_at' => 'datetime',
'cancelled_at' => 'datetime',
'rescheduled_from' => 'datetime',
'rescheduled_to' => 'datetime',
'meeting_expires_at' => 'datetime',
'duration_minutes' => 'integer',
'actual_duration_minutes' => 'integer',
'participants_count' => 'integer',
'overall_rating' => 'integer',
'meeting_data' => 'array',
'meeting_auto_generated' => 'boolean',
```

---

## COMMON RELATIONSHIPS (To Be Moved to BaseSession)

```php
public function academy(): BelongsTo
{
    return $this->belongsTo(Academy::class);
}

public function meeting(): MorphOne
{
    return $this->morphOne(Meeting::class, 'meetable');
}

public function meetingAttendances(): HasMany
{
    return $this->hasMany(MeetingAttendance::class, 'session_id');
}

public function cancelledBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'cancelled_by');
}

public function createdBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updatedBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'updated_by');
}

public function scheduledBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'scheduled_by');
}
```

---

## COMMON SCOPES (To Be Moved to BaseSession)

```php
public function scopeScheduled($query)
public function scopeCompleted($query)
public function scopeCancelled($query)
public function scopeOngoing($query)
public function scopeToday($query)
public function scopeUpcoming($query)
public function scopePast($query)
```

---

## COMMON METHODS (To Be Moved to BaseSession)

### Meeting Management:
```php
public function generateMeetingLink(array $options = []): string
public function getMeetingInfo(): ?array
public function isMeetingValid(): bool
public function getMeetingJoinUrl(): ?string
public function generateParticipantToken(User $user, array $permissions = []): string
public function getRoomInfo(): ?array
public function endMeeting(): bool
public function isUserInMeeting(User $user): bool
public function getMeetingStats(): array
```

### Status Checks (Common Pattern):
```php
public function isScheduled(): bool
public function isCompleted(): bool
public function isCancelled(): bool
public function isOngoing(): bool
```

---

## MEETINGCAPABLE INTERFACE (To Be Implemented in BaseSession)

```php
// MeetingCapable interface methods (already in QuranSession + AcademicSession):
public function canUserJoinMeeting(User $user): bool
public function canUserManageMeeting(User $user): bool
public function getMeetingType(): string                      // ABSTRACT - each child defines
public function getAcademy(): ?Academy
public function getMeetingStartTime(): ?Carbon
public function getMeetingEndTime(): ?Carbon
public function getMeetingDurationMinutes(): int
public function isMeetingActive(): bool
public function getParticipants(): array                      // ABSTRACT - each child defines
public function getMeetingParticipants(): Collection          // ABSTRACT - each child defines
public function getMeetingConfiguration(): array              // ABSTRACT - each child defines
public function getMeetingSessionType(): string               // ABSTRACT - each child defines
```

---

## MODEL-SPECIFIC FIELDS (To Remain in Child Classes)

### QuranSession Specific:
```php
'quran_teacher_id',
'quran_subscription_id',
'circle_id',
'individual_circle_id',
'student_id',
'trial_request_id',
'current_surah',
'current_verse',
'current_page',
'current_face',
'verses_memorized_today',
'papers_memorized_today',
'recitation_quality',
'tajweed_accuracy',
// ... ~40 more Quran-specific fields
```

### AcademicSession Specific:
```php
'academic_teacher_id',
'academic_subscription_id',
'academic_individual_lesson_id',
'interactive_course_session_id',
'student_id',
'session_sequence',
'is_template',
'is_generated',
'homework_description',
'homework_file',
'session_grade',
// ... ~15 more academic-specific fields
```

### InteractiveCourseSession Specific:
```php
'course_id',
'session_number',
'scheduled_date',           // Note: Uses separate date/time instead of scheduled_at
'scheduled_time',
'google_meet_link',         // Note: Direct Google Meet field
'homework_due_date',
'homework_max_score',
'allow_late_submissions',
'materials_uploaded',
```

---

## TRAITS IN USE

- **HasFactory** - All 3 models
- **HasMeetings** - QuranSession, AcademicSession (NOT InteractiveCourseSession)
- **SoftDeletes** - QuranSession, AcademicSession (NOT InteractiveCourseSession)

---

## INTERFACES IMPLEMENTED

- **MeetingCapable** - QuranSession, AcademicSession (NOT InteractiveCourseSession)

---

## DESIGN DECISIONS FOR BASESESSION

### 1. BaseSession Should Include:
✅ All common fields (~30 fields)
✅ All common relationships (7 relationships)
✅ All common scopes (7 scopes)
✅ All common methods (~15 methods)
✅ MeetingCapable interface implementation
✅ HasFactory trait
✅ HasMeetings trait
✅ SoftDeletes trait

### 2. Abstract Methods (Child Must Implement):
```php
abstract public function getMeetingType(): string;
abstract public function getParticipants(): array;
abstract public function getMeetingConfiguration(): array;
```

### 3. Optional Override Methods:
```php
// Children can override if needed:
protected function getExtendedMeetingConfiguration(): array
public function canUserJoinMeeting(User $user): bool
public function canUserManageMeeting(User $user): bool
```

### 4. InteractiveCourseSession Special Handling:
- Currently doesn't implement MeetingCapable
- Uses scheduled_date + scheduled_time instead of scheduled_at
- Uses google_meet_link instead of meeting_link
- **Decision:** Add scheduled_at accessor/mutator to map date+time
- **Decision:** Add meeting_link accessor/mutator to map google_meet_link
- **Decision:** Implement MeetingCapable interface methods

---

## MIGRATION REQUIREMENTS

### No Database Changes Needed:
- All common fields already exist in all tables
- We're only refactoring code, not changing schema
- **Exception:** InteractiveCourseSession may benefit from:
  - Adding scheduled_at column (nullable, computed from date+time)
  - Adding meeting_link column (nullable, alias for google_meet_link)
  - BUT these can be handled with accessors/mutators (no migration needed)

---

## IMPLEMENTATION PLAN

### Step 1: Create BaseSession Abstract Model
- Include all common fields
- Include all common relationships
- Include all common methods
- Implement MeetingCapable interface
- Define abstract methods for child implementation

### Step 2: Refactor QuranSession
- Extend BaseSession
- Remove common fields from $fillable
- Remove common casts
- Remove common relationships
- Remove common methods
- Keep only Quran-specific code
- Override abstract methods

### Step 3: Refactor AcademicSession
- Extend BaseSession
- Remove common fields from $fillable
- Remove common casts
- Remove common relationships
- Remove common methods
- Keep only Academic-specific code
- Override abstract methods

### Step 4: Refactor InteractiveCourseSession
- Extend BaseSession
- Add MeetingCapable implementation
- Add accessors for scheduled_at (computed from date+time)
- Add accessors for meeting_link (mapped to google_meet_link)
- Remove common code
- Override abstract methods

---

## ESTIMATED CODE REDUCTION

### Before:
- QuranSession: 1,858 lines
- AcademicSession: 889 lines
- InteractiveCourseSession: 347 lines
- **Total: 3,094 lines**

### After:
- BaseSession: ~800 lines (all common code)
- QuranSession: ~600 lines (Quran-specific only)
- AcademicSession: ~300 lines (Academic-specific only)
- InteractiveCourseSession: ~200 lines (Interactive-specific only)
- **Total: ~1,900 lines**

### Code Reduction:
- **~1,194 lines eliminated** (38.6% reduction)
- **~800 lines** of duplicate code moved to BaseSession
- **Maintenance:** Changes to common logic only need to be made once

---

## BENEFITS

✅ **DRY Principle:** Eliminate ~800 lines of duplicate code
✅ **Maintainability:** Common logic in one place
✅ **Consistency:** All sessions behave the same way
✅ **Type Safety:** Abstract methods enforce implementation
✅ **Future-Proof:** Easy to add new session types
✅ **Testing:** Test common logic once in BaseSession
✅ **Bug Fixes:** Fix common bugs in one place

---

## RISKS & MITIGATION

### Risk 1: Breaking Existing Code
- **Mitigation:** Keep all public methods with same signatures
- **Mitigation:** Run extensive testing after refactoring
- **Mitigation:** Keep soft deletes for rollback capability

### Risk 2: InteractiveCourseSession Compatibility
- **Mitigation:** Use accessors/mutators for date/time mapping
- **Mitigation:** Test thoroughly with existing interactive course sessions

### Risk 3: Performance Impact
- **Mitigation:** Inheritance has minimal performance impact in PHP
- **Mitigation:** No additional database queries introduced

---

## NEXT STEPS

1. ✅ Complete this analysis
2. Create BaseSession abstract model
3. Refactor QuranSession
4. Refactor AcademicSession
5. Refactor InteractiveCourseSession
6. Run tests and verify
7. Create Phase 5 completion report

---

**Analysis Complete:** Ready to proceed with implementation
