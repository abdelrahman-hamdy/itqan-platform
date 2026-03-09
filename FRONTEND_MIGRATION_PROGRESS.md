# Frontend Migration Progress

## Overview
Replacing Teacher Filament Panels with Blade+Alpine.js frontend pages, and building the foundation for Supervisor/Admin education frontend.

## Status Legend
- [ ] Not started
- [~] In progress
- [x] Complete
- [-] Deferred

## Part A: Teacher Panel Replacement

### A1: Scheduling Calendar (Priority 1 — Critical)
- [ ] `CalendarController.php` — index, getEvents, getSchedulableItems, createSchedule, checkConflicts
- [ ] `teacher/calendar/index.blade.php` — two-column layout with calendar grid
- [ ] `components/teacher/calendar-scheduling-panel.blade.php` — Alpine.js scheduling panel
- [ ] Routes added to `routes/web/teacher.php`
- [ ] Sidebar nav-item for both teacher types
- [ ] Translation keys (ar + en)
- [ ] Manual test: RTL, mobile responsive
- [ ] Data matches Filament output

### A2: Quiz Management (Priority 2)
- [ ] `QuizManagementController.php` — full CRUD + assign/revoke
- [ ] `StoreQuizRequest.php` + `AssignQuizRequest.php`
- [ ] `teacher/quizzes/index.blade.php` — list with stats
- [ ] `teacher/quizzes/create.blade.php` + `edit.blade.php` — form with question repeater
- [ ] `teacher/quizzes/show.blade.php` — details + assignments + assign form
- [ ] `components/teacher/quiz-question-repeater.blade.php` — Alpine.js repeater
- [ ] `components/teacher/quiz-assignment-form.blade.php` — dynamic selects
- [ ] Routes added
- [ ] Sidebar nav-item
- [ ] Integration into circle/course detail pages
- [ ] Translation keys (ar + en)

### A3: Session/Circle/Lesson Create/Edit (Priority 3)
- [ ] Quran session create/edit
- [ ] Academic session create
- [ ] Interactive course session create/edit
- [ ] Group circle create/edit
- [ ] Academic lesson create/edit
- [ ] Routes added
- [ ] Translation keys (ar + en)

### A4: Session Reports List (Priority 4)
- [ ] `SessionReportListController.php`
- [ ] `teacher/session-reports/index.blade.php`
- [ ] Routes added
- [ ] Translation keys (ar + en)

### A5: Session Recordings (Priority 5 — Academic only)
- [ ] `RecordingListController.php`
- [ ] `teacher/recordings/index.blade.php`
- [ ] Routes added
- [ ] Translation keys (ar + en)

### A6: Certificate List (Priority 6)
- [ ] `CertificateListController.php`
- [ ] `teacher/certificates/index.blade.php`
- [ ] Routes added
- [ ] Translation keys (ar + en)

### A7: Migration Path
- [ ] Both Filament and frontend accessible during transition
- [ ] Remove Filament nav items (`$shouldRegisterNavigation = false`) after confidence period
- [ ] Set `canAccess() → false` on Teacher/AcademicTeacher panels (final step)

## Part B: Supervisor & Admin Foundation

### B0: Foundation (This Phase)
- [ ] `components/layouts/supervisor.blade.php`
- [ ] `components/layouts/admin-education.blade.php`
- [ ] `components/sidebar/supervisor-sidebar.blade.php`
- [ ] `components/sidebar/admin-education-sidebar.blade.php`
- [ ] `BaseSupervisorWebController.php`
- [ ] `BaseAdminEducationController.php`
- [ ] viewType extension on session components (supervisor/admin)
- [ ] `routes/web/supervisor-education.php` (empty, ready)
- [ ] `routes/web/admin-education.php` (empty, ready)
- [ ] Navigation update for supervisor/admin roles

## Verification Checklist (per feature)
1. Manual test in browser (RTL Arabic layout, mobile responsive)
2. Data matches Filament panel output (same queries, same scoping)
3. AJAX endpoints correct JSON responses
4. Teacher scoping (teacher A cannot see teacher B's data)
5. NO student contact info exposed in teacher views
6. ALL strings localized (no hardcoded text)
7. `composer test` passes
8. Both Filament and frontend paths work simultaneously

## Last Updated
2026-03-09 — Initial creation
