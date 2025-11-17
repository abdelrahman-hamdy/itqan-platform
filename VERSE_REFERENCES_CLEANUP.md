# Verse References Cleanup Documentation

This document identifies all remaining verse-based references in the Quran tracking system and provides recommendations for cleanup.

## Status: Phase 9 - Code Cleanup (Verse References)

**Generated**: 2025-11-17
**Purpose**: Track transition from verse-based to pages-only measurement system

## Summary

The Quran progress tracking system has been refactored to use **pages-only measurement** instead of verses. This document catalogs remaining verse references that need to be addressed.

---

## Critical Files - Already Updated ✅

These files have been successfully updated to use pages-only:

1. **app/Services/QuranProgressService.php** ✅
   - Uses pages calculation from degrees
   - No verse tracking in new progress records

2. **app/Services/QuranCircleReportService.php** ✅
   - Refactored to use QuranProgress with pages
   - Removed verse display from report data

3. **resources/views/teacher/individual-circles/report.blade.php** ✅
   - Removed "الآيات المحفوظة" display
   - Uses pages-only in stats cards

4. **resources/views/student/circle-report.blade.php** ✅
   - Removed verse counter
   - Displays pages instead

5. **resources/views/components/reports/*.blade.php** ✅
   - All new report components use pages-only

---

## Database Models - Verse Fields Still Present

These models have verse-related fields in their database schema. These fields should be **deprecated** but not removed immediately to avoid breaking existing data:

### 1. **app/Models/QuranSubscription.php**
**Fields**:
- `current_verse` (integer)
- `verses_memorized` (integer)

**Status**: ⚠️ **Deprecated - Keep for backward compatibility**

**Recommendation**:
- Mark fields as deprecated in PHPDoc
- Stop writing to these fields in new code
- Continue reading for legacy data migration
- Plan removal in future major version

**Code Example**:
```php
/**
 * @deprecated Use pages-only measurement instead
 */
public $current_verse;

/**
 * @deprecated Use pages_memorized calculation instead
 */
public $verses_memorized;
```

---

### 2. **app/Models/QuranIndividualCircle.php**
**Fields**:
- `verses_memorized` (integer)
- Probably other verse-related fields

**Status**: ⚠️ **Deprecated - Keep for backward compatibility**

**Recommendation**: Same as QuranSubscription

---

### 3. **app/Models/QuranProgress.php**
**Fields**:
- Multiple verse-tracking fields (this is the primary progress model)

**Status**: ⚠️ **Needs Review**

**Recommendation**:
- Keep legacy verse fields for historical data
- Primary tracking should use pages fields
- QuranProgressService already uses pages-only
- Add migration to calculate pages from verse data for old records

---

## Controllers - Verse References

### 1. **app/Http/Controllers/QuranProgressController.php**
**Status**: ⚠️ **Needs Review**

**Recommendation**:
- Check if this controller is still used
- If used, ensure it uses pages-only for new progress tracking
- May need refactoring to use QuranProgressService

---

### 2. **app/Http/Controllers/QuranHomeworkController.php**
**Status**: ⚠️ **Needs Review**

**Recommendation**:
- Homework should specify pages, not verses
- Check if verse references exist in homework assignment logic

---

## Filament Resources - Admin Interface

### 1. **app/Filament/Resources/QuranSubscriptionResource.php**
**Status**: ⚠️ **Needs Update**

**Recommendation**:
- Update form fields to use pages instead of verses
- Hide verse fields or mark as read-only (legacy)
- Update table columns to show pages

---

### 2. **app/Filament/Resources/QuranIndividualCircleResource.php**
### 3. **app/Filament/Teacher/Resources/QuranIndividualCircleResource.php**
**Status**: ⚠️ **Needs Update**

**Recommendation**:
- Same as QuranSubscriptionResource
- Ensure circle management uses pages-only

---

### 4. **app/Filament/Resources/QuranProgressResource.php**
**Status**: ⚠️ **Needs Update**

**Recommendation**:
- Update display to show pages prominently
- Verse fields should be in a "Legacy Data" section if shown at all

---

## Public Controllers

### **app/Http/Controllers/PublicQuranTeacherController.php**
**Status**: ℹ️ **Low Priority**

**Recommendation**:
- Check if verse data is exposed in public teacher profiles
- If so, update to show pages instead

---

## Implementation Plan

### Phase 9A: Documentation (Completed)
- [x] Identify all verse references
- [x] Create this documentation
- [x] Categorize by priority

### Phase 9B: Critical Updates (High Priority)
- [ ] Update QuranSubscription fillable to mark verse fields as deprecated
- [ ] Update Filament resources to use pages-only
- [ ] Add database migration to calculate pages from verses for legacy data
- [ ] Update all controllers to use QuranProgressService

### Phase 9C: Database Cleanup (Medium Priority)
- [ ] Add migration to backfill pages for old verse-based records
- [ ] Add `@deprecated` tags to verse fields in models
- [ ] Create accessor methods to calculate pages from verses

### Phase 9D: Complete Removal (Low Priority - Future)
- [ ] Plan for major version release
- [ ] Create migration to drop verse columns
- [ ] Remove all deprecated code

---

## Migration Strategy

### Backward Compatibility Approach

1. **Keep existing verse fields** in database for now
2. **Stop writing** to verse fields in new code
3. **Continue reading** verse fields for legacy data display
4. **Calculate pages** from verses where needed: `pages ≈ verses / 15` (rough approximation)
5. **Gradually migrate** old data to pages-based system

### Data Migration Command

A backfill command has been created: `php artisan quran:backfill-progress`

**Additional migration needed**:
```bash
php artisan make:command QuranLegacyDataMigrationCommand
```

This command should:
- Find all records with verse data but no page data
- Calculate approximate pages from verses
- Update records with calculated page values
- Log any records that couldn't be migrated

---

## Testing Checklist

- [ ] Verify auto-attendance still works (Phase Final)
- [ ] Test QuranProgressService with real data
- [ ] Verify reports display pages correctly
- [ ] Test subscription widgets show correct billing cycles
- [ ] Ensure no verse data is displayed in student/teacher views
- [ ] Check Filament admin interfaces use pages
- [ ] Test backfill command on staging environment

---

## Notes

- The system now uses **pages-only measurement** as the primary tracking method
- **Verse data** is considered **legacy** and should not be used for new features
- All new UI components and reports have been built with pages-only
- The auto-attendance system (LiveKit webhooks → StudentSessionReport) remains unchanged
- QuranProgress integration happens via StudentSessionReportObserver **after** attendance tracking

---

## Questions for Product Owner

1. Should we continue to display verse counts anywhere for historical comparison?
2. What is the conversion factor from verses to pages we should use for legacy data?
3. Can we plan a migration window to backfill all legacy data?
4. Should the Filament admin still show verse fields (read-only) or hide them completely?

---

## Conclusion

The transition to pages-only measurement is **substantially complete** for:
- ✅ Report views (student & teacher)
- ✅ Progress tracking service
- ✅ Shared UI components
- ✅ Report data aggregation

**Remaining work** focuses on:
- ⚠️ Filament admin interfaces
- ⚠️ Legacy data migration
- ⚠️ Deprecation warnings in code
- ⚠️ Controller updates

**Priority**: Medium (system works, but admin interface needs update)
**Risk**: Low (changes are backward compatible)
**Effort**: ~4-8 hours for Filament updates and legacy data migration
