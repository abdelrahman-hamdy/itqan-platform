# Academy Dropdown Completion Guide

## Overview
This guide explains how to complete the academy dropdown functionality for all Filament resources. The system now supports viewing data from all academies when "All Academies" is selected.

## What's Already Done

### ✅ Core Infrastructure
- **ScopedToAcademy** and **ScopedToAcademyViaRelationship** traits updated to conditionally apply scoping
- **BaseResource** and **BaseSettingsResource** classes created
- All resources updated to extend the appropriate base class
- Academy column functionality implemented in BaseResource

### ✅ Example Resources Completed
- **StudentProfileResource**: Academy column added (via gradeLevel.academy relationship)
- **SubjectResource**: Academy column added (direct academy relationship)

## Next Steps: Add Academy Columns to Remaining Resources

For each **data resource** (not settings), you need to add the academy column to the table. Here's the pattern:

### 1. Add Academy Column to Table

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            static::getAcademyColumn(), // Add this line at the beginning
            // ... existing columns ...
        ])
        // ... rest of table configuration
}
```

### 2. Override Academy Relationship Path (if needed)

If the model doesn't have a direct `academy` relationship, override the relationship path:

```php
protected static function getAcademyRelationshipPath(): string
{
    return 'user.academy'; // or whatever the correct path is
}
```

## Resource Relationship Mapping

| Resource | Academy Relationship Path | Notes |
|----------|--------------------------|-------|
| StudentProfileResource | `gradeLevel.academy` | ✅ Done |
| SubjectResource | `academy` | ✅ Done |
| ParentProfileResource | `academy` (likely direct) | Need to verify |
| AcademicTeacherProfileResource | `academy` (likely direct) | Need to verify |
| QuranTeacherProfileResource | `user.academy` (via relationship) | ✅ Implemented |
| SupervisorProfileResource | `academy` (likely direct) | Need to verify |
| QuranCircleResource | `academy` (likely direct) | Need to verify |
| QuranPackageResource | `academy` (likely direct) | Need to verify |
| QuranSubscriptionResource | `academy` (likely direct) | Need to verify |
| RecordedCourseResource | `academy` (likely direct) | Need to verify |
| AdminResource | `academy` (likely direct) | Need to verify |
| AcademyManagementResource | N/A - This might be academy itself | Need special handling |
| GradeLevelResource | `academy` (likely direct) | Need to verify |
| InteractiveCourseResource | `academy` (likely direct) | Need to verify |

## Settings Resources (Already Handled)

These resources extend `BaseSettingsResource` and will automatically hide when "All Academies" is selected:
- **AcademicSettingsResource** ✅ Done

## Testing Checklist

### When Specific Academy is Selected:
- [ ] All resources show in navigation
- [ ] No academy column is visible in tables
- [ ] Data is filtered to selected academy only
- [ ] All CRUD operations work normally

### When "All Academies" is Selected:
- [ ] Data resources show in navigation
- [ ] Settings resources hidden from navigation  
- [ ] Academy column visible and shows correct academy names
- [ ] Data shows from all academies
- [ ] Create buttons disabled (no academy context)
- [ ] Edit/Delete still work on individual records

## Quick Implementation Script

To quickly add academy columns to all remaining resources, run this in the project root:

```bash
# Search for table methods in resources that need academy columns
grep -l "public static function table" app/Filament/Resources/*Resource.php | while read file; do
    echo "Processing: $file"
    # Check if already has getAcademyColumn
    if ! grep -q "getAcademyColumn" "$file"; then
        echo "  → Needs academy column added"
    else
        echo "  → Already has academy column"
    fi
done
```

## Verification Commands

```bash
# Check all resources extend correct base class
grep -l "extends BaseResource\|extends BaseSettingsResource" app/Filament/Resources/*Resource.php

# Check which resources have academy columns
grep -l "getAcademyColumn" app/Filament/Resources/*Resource.php
```

## Next Steps

1. **Add academy columns** to all remaining data resources using the pattern above
2. **Verify relationship paths** for each resource model
3. **Test the functionality** with actual data
4. **Fix any issues** that arise during testing

## Notes

- Settings resources should remain hidden when viewing all academies
- Academy column automatically shows/hides based on context
- The system gracefully handles missing relationships
- All existing functionality is preserved when specific academy is selected