# Remaining Files for Enum Refactoring

## High Priority Files (Common Patterns)

### Subscription Resources
- app/Filament/Resources/QuranSubscriptionResource.php
- app/Filament/Resources/AcademicSubscriptionResource.php
- app/Filament/Resources/QuranSubscriptionResource/Pages/CreateQuranSubscription.php

### Trial Request Files
- app/Filament/Resources/QuranTrialRequestResource.php
- app/Filament/Teacher/Resources/QuranTrialRequestResource.php

### Individual Lesson Resources
- app/Filament/Resources/AcademicIndividualLessonResource.php
- app/Filament/AcademicTeacher/Resources/AcademicIndividualLessonResource.php

### Widget Files
- app/Filament/AcademicTeacher/Widgets/AcademicTeacherOverviewWidget.php
- app/Filament/Teacher/Widgets/QuranTeacherOverviewWidget.php
- app/Filament/Widgets/SuperAdminStatsWidget.php
- app/Filament/Widgets/SuperAdminMonthlyStatsWidget.php
- app/Filament/Widgets/RecentBusinessRequestsWidget.php
- app/Filament/AcademicTeacher/Widgets/PendingHomeworkWidget.php

### Interactive Course Files
- app/Filament/Resources/InteractiveCourseResource.php
- app/Filament/AcademicTeacher/Resources/InteractiveCourseResource.php

### Business Service Files
- app/Filament/Resources/BusinessServiceRequestResource.php

### Shared Components
- app/Filament/Shared/Pages/UnifiedTeacherCalendar.php
- app/Filament/Shared/Traits/FormatsCalendarData.php

## Common Patterns to Look For

1. `'scheduled'` → SessionStatus::SCHEDULED->value
2. `'pending'` → SubscriptionStatus::PENDING->value
3. `'active'` → SubscriptionStatus::ACTIVE->value
4. `'completed'` → SessionStatus::COMPLETED->value
5. `'cancelled'` → SessionStatus::CANCELLED->value / SubscriptionStatus::CANCELLED->value
6. `'ongoing'` → SessionStatus::ONGOING->value

## Search Commands

Find remaining occurrences:
```bash
# SessionStatus
grep -r "'scheduled'" app/Filament/ | wc -l
grep -r "'ongoing'" app/Filament/ | wc -l
grep -r "'completed'" app/Filament/ | wc -l

# SubscriptionStatus
grep -r "'pending'" app/Filament/ | wc -l
grep -r "'active'" app/Filament/ | wc -l
grep -r "'expired'" app/Filament/ | wc -l

# AttendanceStatus
grep -r "'attended'" app/Filament/ | wc -l
grep -r "'late'" app/Filament/ | wc -l
```
