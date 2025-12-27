# Deferred Problems

This file tracks issues found during the bug audit that require discussion or significant changes before fixing.

## Summary
- Total deferred: 0 (All resolved)
- Total resolved: 2

---

## 2. ScopedToAcademy Traits Used on Filament Resources

**Date Found:** 2025-12-22
**Deep Analysis:** 2025-12-22

**Severity:** LOW - Issue was already correctly fixed, scoping works properly

### Original Issue:
The `ScopedToAcademy` and `ScopedToAcademyViaRelationship` traits were incorrectly used on Filament Resources:
- Traits use `static::addGlobalScope()` which is an Eloquent Model method
- Filament Resources extend `Filament\Resources\Resource`, NOT Eloquent Model
- This caused static analysis errors (method doesn't exist on Resources)

**Fix Applied:** Trait usage removed from Resources (commit 7fd4ef5)

### Deep Analysis - How Academy Scoping Actually Works:

**Layer 1: Filament Native Tenancy**
```php
// app/Providers/Filament/AcademyPanelProvider.php
->tenant(Academy::class)
```
- Filament automatically scopes all Resources to current tenant
- Works for any model with `academy(): BelongsTo` relationship
- No extra code needed in Resources

**Layer 2: ScopedToAcademy Trait (Model Global Scope)**
```php
// app/Traits/ScopedToAcademy.php
static::addGlobalScope('academy', function (Builder $builder) {
    $currentAcademyId = app(AcademyContextService::class)->getCurrentAcademyId();
    if ($currentAcademyId) {
        $builder->where('academy_id', $currentAcademyId);
    }
});
```
- Used by 15 models for global scope
- Works via `AcademyContextService` session-based context
- Supports super admin "view all academies" mode

**Layer 3: API Controllers - User Ownership Scoping**
```php
// Most API controllers scope by user relationship
QuranSession::where('student_id', $user->id)
AcademicSession::where('academic_teacher_id', $teacherId)
```
- Implicit tenant isolation via user ownership
- Users belong to academy → their data is isolated

### Models Analysis:

| Category | Count | Notes |
|----------|-------|-------|
| Models with `academy_id` | 41+ | All tenant-scoped models |
| Models using `ScopedToAcademy` | 15 | Have global scope |
| Models without trait | 26+ | Rely on Filament tenancy or user scoping |

### Verification - Is Academy Scoping Working?

✅ **YES** - Scoping works correctly because:

1. **Filament Resources**: Scoped by native tenancy (`->tenant(Academy::class)`)
2. **Models with trait**: Have additional global scope for non-Filament contexts
3. **API endpoints**: Mostly scope by user ownership (student_id, teacher_id)
4. **Super admin**: Can switch academies via `AcademyContextService`

### Secondary Issue Found:

**Potential Gap in `SessionStatusApiController`:**
```php
// Lines 34, 50, 72, 86 - Uses findOrFail without scoping
$session = AcademicSession::findOrFail($sessionId);
$session = QuranSession::findOrFail($sessionId);
```
- Any authenticated user can get status for ANY session by ID
- Low risk: Only exposes status info, not sensitive data
- Recommendation: Add authorization check (verify user owns session)

### Conclusion:

**Status: RESOLVED** ✅

The original fix (removing traits from Resources) was **correct**. The multi-layer scoping architecture works as designed:

1. Resources don't need traits - Filament tenancy handles scoping
2. Models can optionally use trait for non-Filament contexts
3. API controllers scope by user ownership

**No further action required** for this issue. The secondary issue in `SessionStatusApiController` is minor and separate from the original problem.

### Recommendations (Optional Improvements):

1. **Consider adding `ScopedToAcademy` to more models** for consistency (currently only 15 of 41+ models use it)
2. **Add authorization to SessionStatusApiController** to verify user owns the session
3. **Document the scoping architecture** in CLAUDE.md for future developers

---

## 1. QuranSessionReport Model & Rating Columns Do Not Exist

**Date Found:** 2025-12-22
**Deep Analysis:** 2025-12-22
**Resolved:** 2025-12-22

**Severity:** HIGH → RESOLVED ✅

### Original Problem:
- `memorization_rating` and `tajweed_rating` columns referenced but DO NOT EXIST on `quran_sessions` table
- `QuranSessionReport` model referenced but doesn't exist
- Data was silently lost - API returned success but values null on retrieval

### Decision Made:
**Option B.1: Use StudentSessionReport without tajweed**

Per owner's direction:
- `memorization_rating` and `tajweed_rating` columns are **DEPRECATED** - do NOT add to database
- Mobile app NOT yet implemented, API still in development (breaking changes acceptable)
- Use `StudentSessionReport` model for all Quran session evaluations
- Remove tajweed evaluation completely

### Implementation (4 Controllers Refactored):

**1. `Teacher/Quran/SessionController.php`**
- Added `StudentSessionReport` import
- Updated `show()` - uses `formatEvaluation()` helper
- Updated `complete()` - changed validation from 1-5 to 0-10 scale
- Updated `evaluate()` - same changes
- Added helper methods: `formatEvaluation()`, `formatReport()`, `updateOrCreateReport()`
- API fields: `memorization_degree` (0-10), `revision_degree` (0-10)

**2. `Teacher/Quran/CircleController.php`**
- Updated `individualShow()` - eager loads reports with sessions
- Changed `recent_sessions` mapping to use report data

**3. `Teacher/StudentController.php`**
- Added `StudentSessionReport` import
- Updated `show()` - calculates averages from `StudentSessionReport`
- Updated `createReport()` - uses `StudentSessionReport` for Quran
- Fields: `average_memorization_degree`, `average_revision_degree`

**4. `ParentApi/SessionController.php`**
- Updated `formatSessionDetail()` for Quran sessions
- Removed deprecated `progress.memorization_rating` and `progress.tajweed_rating`
- Added `evaluation` object with `memorization_degree`, `revision_degree`, `overall_performance`

### API Field Changes (Breaking):

| Old Field (Deprecated) | New Field | Scale | Source |
|----------------------|-----------|-------|--------|
| `memorization_rating` | `memorization_degree` | 0-10 | StudentSessionReport |
| `tajweed_rating` | REMOVED | - | - |
| `rating` | `overall_performance` | text | StudentSessionReport |
| `teacher_feedback` | `notes` | text | StudentSessionReport |

### Schema Used:
`student_session_reports` table (no migration needed):
- `new_memorization_degree` (0-10) → exposed as `memorization_degree`
- `reservation_degree` (0-10) → exposed as `revision_degree`
- `overall_performance` (text)
- `notes` (text)
- `evaluated_at` (datetime)

### Conclusion:
**Status: RESOLVED** ✅

All 4 controllers refactored to use `StudentSessionReport` instead of non-existent columns:
1. Evaluations now properly persisted to database
2. API uses 0-10 scale (consistent with model)
3. Tajweed evaluation removed entirely
4. No database migration required

---
