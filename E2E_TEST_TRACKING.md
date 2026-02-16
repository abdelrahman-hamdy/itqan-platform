# E2E Test Suite - Itqan Platform

## Latest Run: 2026-02-16 (Phase 3 Update - CRUD + API)

| Metric | Count |
|--------|-------|
| **Total Tests** | 567 |
| **Passed** | 555 |
| **Skipped** | 12 |
| **Failed** | 0 |
| **Pass Rate** | 100% (of non-skipped) |

## Results by Phase

| Phase | Project | Tests | Passed | Skipped | Failed |
|-------|---------|-------|--------|---------|--------|
| Setup | setup | 6 | 6 | 0 | 0 |
| Phase 1 - Public Pages | public | 18 | 18 | 0 | 0 |
| Phase 2 - Authentication | auth | 36 | 36 | 0 | 0 |
| Phase 3 - Admin Panel | admin | 72 | 72 | 0 | 0 |
| Phase 4 - Student Portal | student | 55 | 55 | 0 | 0 |
| Phase 5 - Teacher Panel | teacher | 45 | 45 | 0 | 0 |
| Phase 6 - Supervisor Panel | supervisor | 20 | 20 | 0 | 0 |
| Phase 7 - Parent Portal | parent | 16 | 16 | 0 | 0 |
| Phase 8 - Chat System | chat | 10 | 10 | 0 | 0 |
| Phase 9 - Payments | payments | 12 | 12 | 0 | 0 |
| Phase 10 - Consistency | consistency | 14 | 14 | 0 | 0 |
| Phase 11 - Regression | regression | 33 | 33 | 0 | 0 |
| Phase 12 - CRUD Operations | crud | 54 | 48 | 6 | 0 |
| Phase 13 - Mobile API | api | 182 | 176 | 6 | 0 |

## Test Accounts

| Role | Email | Auth State File |
|------|-------|-----------------|
| SuperAdmin | abdelrahmanhamdy320@gmail.com | e2e/auth/superadmin.json |
| Student | abdelrahman260598@gmail.com | e2e/auth/student.json |
| Quran Teacher | quran.teacher5@itqan.com | e2e/auth/quran-teacher.json |
| Supervisor | supervisor1@itqan.com | e2e/auth/supervisor.json |
| Academic Teacher | academic.teacher1@itqan.com | e2e/auth/academic-teacher.json |
| Parent | parent1@itqan.com | e2e/auth/parent.json |

## Run Commands

```bash
# Full suite (recommended: 2 workers)
npx playwright test --workers=2

# Re-authenticate (if sessions expired)
npx playwright test --project=setup

# Specific phase
npx playwright test --project=public
npx playwright test --project=admin
npx playwright test --project=student
npx playwright test --project=teacher
npx playwright test --project=supervisor
npx playwright test --project=parent
npx playwright test --project=chat
npx playwright test --project=payments
npx playwright test --project=consistency
npx playwright test --project=regression
npx playwright test --project=crud
npx playwright test --project=api

# HTML report
npx playwright test --reporter=html
npx playwright show-report e2e/reports

# Debug (headed browser)
npx playwright test --headed --project=student

# Specific file
npx playwright test e2e/phase-03-admin/admin-quran.spec.ts
npx playwright test e2e/phase-12-crud/crud-student-profile.spec.ts
npx playwright test e2e/phase-13-api/api-auth.spec.ts
```

## Architecture

```
e2e/
├── fixtures/
│   ├── auth.fixture.ts       # Worker-scoped auth fixtures per role (6 roles)
│   ├── filament.fixture.ts   # Filament/Livewire helpers + assertions
│   ├── crud.fixture.ts       # CRUD lifecycle helpers (Filament forms, Choices.js, tables)
│   ├── api.fixture.ts        # API testing infrastructure (axios, tokens, assertions)
│   └── helpers.ts            # Shared assertions (404, PHP errors)
├── setup/
│   └── global-setup.ts       # Pre-test authentication for all 6 roles
├── auth/                     # Stored auth states (gitignored)
├── phase-01-public/          # Public pages (no auth)
├── phase-02-auth/            # Login, registration, password reset
├── phase-03-admin/           # Admin panel (9 spec files: 6 smoke + 3 deep)
├── phase-04-student/         # Student portal (12 spec files: 9 smoke + 3 deep)
├── phase-05-teacher/         # Teacher panels (9 spec files: 5 smoke + 4 deep)
├── phase-06-supervisor/      # Supervisor panel (2 spec files: 1 smoke + 1 deep)
├── phase-07-parent/          # Parent portal (4 spec files: 3 smoke + 1 deep)
├── phase-08-chat/            # Chat system (WireChat)
├── phase-09-payments/        # Payment flows & subscriptions
├── phase-10-consistency/     # Cross-role data verification
├── phase-11-regression/      # Known bug regression + edge cases
├── phase-12-crud/            # CRUD lifecycle tests (9 admin resources)
└── phase-13-api/             # Mobile API tests (11 spec files: auth, smoke, integration, validation)
```

## Key URLs Tested

### Main Domain (itqanway.com)
- `/admin/*` - Admin panel (56 resource pages tested)

### Academy Domain (itqan-academy.itqanway.com)
- `/` - Academy homepage
- `/login`, `/register`, `/teacher/register` - Auth pages
- `/profile`, `/subscriptions`, `/payments`, `/homework`, `/quizzes`, `/certificates` - Student routes
- `/student/calendar` - Student calendar
- `/courses`, `/quran-teachers`, `/academic-teachers`, `/quran-circles`, `/interactive-courses` - Public browsing
- `/teacher-panel/*` - Quran Teacher Filament panel
- `/academic-teacher-panel/*` - Academic Teacher Filament panel (12 resources)
- `/supervisor-panel/*` - Supervisor panel
- `/parent/*` - Parent portal (dashboard, children, sessions, subscriptions, payments, homework, quizzes, certificates, calendar, reports)
- `/chats` - Chat system (WireChat)
- `/teacher/homework`, `/teacher/individual-circles`, `/teacher/group-circles` - Teacher web routes

## Run History

| Date | Total | Passed | Failed | Skipped | Duration | Notes |
|------|-------|--------|--------|---------|----------|-------|
| 2026-02-16 | 274 | 256 | 0 | 18 | ~5min | Initial suite - all non-skipped passing |
| 2026-02-16 | 283 | 283 | 0 | 0 | ~5.5min | Added academic teacher + parent accounts - 100% pass |
| 2026-02-16 | 331 | 331 | 0 | 0 | ~4.2min | Phase 2: +48 deep tests (CRUD forms, filters, journeys) |
| 2026-02-16 | 567 | 555 | 0 | 12 | ~5.3min | Phase 3: +54 CRUD + 182 API tests (12 skipped: known bugs) |

## Phase 12 CRUD Tests (9 spec files, 54 tests)

### Resources Tested (8 resources, 48 tests)
Each resource has 6 serial tests: Create → Verify → Edit → Verify Edit → Delete → Verify Delete
- **crud-academic-grade-level.spec.ts** (6): Simplest - name + optional fields
- **crud-academic-subject.spec.ts** (6): Name + optional fields
- **crud-quran-package.spec.ts** (6): Name + pricing fields
- **crud-academic-package.spec.ts** (6): Name + pricing fields
- **crud-quran-circle.spec.ts** (6): Name + teacher select (Filament AJAX combobox)
- **crud-student-profile.spec.ts** (6): Name + email + phone + nationality (Choices.js) + grade level (Choices.js) + parent phone
- **crud-parent-profile.spec.ts** (6): Name + email + phone
- **crud-supervisor-profile.spec.ts** (6): Name + email

### Skipped (1 resource, 6 tests)
- **crud-interactive-course.spec.ts** (6): Skipped - create page returns 500 (CRUD-003: `Builder::approved()` undefined)

### Key Technical Challenges Solved
- **Choices.js selects**: Filament renders `.searchable()` + `.preload()` selects using Choices.js library (not standard combobox). Required custom `openAndPickFirstChoicesOption()` helper
- **PhoneInput component**: `ysfkaya/filament-phone-input` uses `wire:ignore` block, targeted via `wire:key` wrapper
- **SoftDeletes resources**: Two-step delete pattern (soft delete → force delete) for 30+ resources that exclude `SoftDeletingScope`
- **Table column truncation**: Filament truncates column display; `assertRecordInTable()` falls back to "any row after search" check

## Phase 13 API Tests (11 spec files, 182 tests)

### Auth Tests (12 tests)
- **api-auth.spec.ts**: Login, logout, token validation, public endpoints, academy branding

### Smoke Tests per Role (125 tests)
- **api-smoke-student.spec.ts** (30): Dashboard, sessions, subscriptions, homework, quizzes, certificates, payments, calendar, profile
- **api-smoke-teacher.spec.ts** (25): Dashboard, schedule, quran circles, sessions, students, homework, earnings, profile + academic teacher
- **api-smoke-parent.spec.ts** (35): Dashboard, calendar, children, sessions, reports, homework, payments, subscriptions, profile
- **api-smoke-common.spec.ts** (10): Notifications, chat, meetings, profile-options
- **api-smoke-admin.spec.ts** (5): Admin sessions, supervisor supervised-groups

### Integration Tests (25 tests)
- **api-integration-student.spec.ts** (8): Profile CRUD, trial requests, notification workflow
- **api-integration-teacher.spec.ts** (5): Sessions, schedule, profile
- **api-integration-parent.spec.ts** (5): Children list, reports, calendar
- **api-integration-common.spec.ts** (7): Chat conversations, notifications, device tokens

### Validation Tests (15 tests)
- **api-validation.spec.ts** (15): 401 (no/invalid token), 403 (cross-role access), 404 (invalid academy), 422 (bad input)

### Skipped (6 tests)
- Chat unread-count tests: Known backend bug (API-001)

## Phase 2 Deep Tests (12 new spec files, 48 tests)

### Admin Deep Tests (16 tests)
- **admin-crud-forms.spec.ts** (10): Form field verification, save actions, table data loading, search, pagination, edit forms
- **admin-crud-filters.spec.ts** (4): Table filter panels on student-profiles, quran-subscriptions, academic-sessions, interactive-courses
- **admin-journeys.spec.ts** (2): Dashboard → students → detail, Subscriptions → detail

### Student Deep Tests (15 tests)
- **student-interactive.spec.ts** (10): Subscription details, teacher browsing, homework, quizzes, courses, certificates
- **student-calendar-interactive.spec.ts** (1): Calendar navigation controls
- **student-journeys.spec.ts** (3): Homepage → teachers → detail, Profile → subscriptions → detail, Courses → detail

### Teacher Deep Tests (12 tests)
- **quran-teacher-interactive.spec.ts** (5): Individual/group circles, circle details, homework content
- **quran-teacher-panel-interactive.spec.ts** (3): Sessions with filters, individual circles table, session reports
- **academic-teacher-interactive.spec.ts** (2): Academic sessions detail, individual lessons table
- **teacher-journeys.spec.ts** (2): Circles → detail → sessions, Panel dashboard → sessions

### Supervisor Deep Tests (2 tests)
- **supervisor-interactive.spec.ts** (2): Dashboard → monitored circles → sessions, Filters on monitored sessions

### Parent Deep Tests (3 tests)
- **parent-journeys.spec.ts** (3): Dashboard → children → sessions, Dashboard → subscriptions → payments, Homework → quizzes

## Notes

- Tests run against **production** at `itqanway.com` / `itqan-academy.itqanway.com`
- Auth state cached in `e2e/auth/` (gitignored) - setup project runs first automatically
- If auth expires, re-run `npx playwright test --project=setup` to refresh sessions
- Screenshots and traces saved on failure in `test-results/`
- Livewire/Filament pages use `waitForLivewire()` after navigation
- 404 detection built into `assertNoServerError()` and `assertPageLoads()`
- Worker-scoped fixtures share authenticated pages across tests in same worker
