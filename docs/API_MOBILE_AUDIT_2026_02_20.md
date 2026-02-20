# API & Mobile App Audit - February 20, 2026

## Overview
Comprehensive audit of all API endpoints and mobile app alignment.
Goal: Make the mobile app production-ready and error-free like the web app.

## Status: COMPLETED

---

## Phase 1: API Endpoint Testing (DONE)

### Results
- **91 GET endpoints** tested across all roles (student, parent, teacher, academic teacher, supervisor, admin)
- **420 requests** made via API scanner
- **0 server errors** across all roles
- All authentication, authorization, and tenant isolation working correctly

### Endpoints Tested by Role
| Role | Endpoints | Status |
|------|-----------|--------|
| Student | ~45 | All passing |
| Parent | ~40 | All passing |
| Teacher (Quran) | ~20 | All passing |
| Teacher (Academic) | ~15 | All passing |
| Supervisor | ~5 | All passing (web-only panels return 404 as expected) |
| Admin | ~5 | All passing (web-only panels return 404 as expected) |
| Common (Chat/Notifications/Meetings) | ~30 | All passing |

---

## Phase 2: API Bugs Found & Fixed

### Batch 1 (Commit: 05611577) - Deployed
| # | Bug | Fix |
|---|-----|-----|
| A | `AcademicSession` missing `attendances()` relationship | Added alias method |
| B | Parent API `student.user` caused "undefined relationship on User" | Changed to `student` (StudentProfile = User in this context) |
| C | `academic_teacher_packages` table doesn't exist | Removed from eager loading |
| D | `CalculateSessionEarningsJob` tenant error | Added to `not_tenant_aware_jobs` |

### Batch 2 (Commit: e48c8b43) - Deployed
| # | Bug | Fix |
|---|-----|-----|
| E | Student API `notes` key but mobile reads `session_notes` | Changed API key to `session_notes` |
| F | `AcademicSessionController::show()` eager-loads `attendanceRecords` but trait accesses `attendances` | Fixed eager loading key |
| G | Parent `formatBaseSession()` missing 7 fields mobile needs | Added: `student_id`, `student_name`, `session_code`, `teacher`, `meeting_url`, `can_join`, `has_meeting` |
| H | Inconsistent `meeting_link` vs `meeting_url` JSON keys across 13 controllers | Standardized all to `meeting_url` |
| I | `MobilePurchaseController` required UUID validation for non-UUID IDs | Removed UUID validation, added `user_id` lookup fallback |

### Batch 3 (Commit: f22c2b2b) - Deployed
| # | Bug | Fix |
|---|-----|-----|
| J | Archived conversations missing `user_type` in participant mapping | Added `user_type` field (was already present in regular conversations) |

---

## Phase 3: Mobile App Fixes (DONE)

### Commit: bfaedb2 (mobile repo, local)

| # | Issue | Fix |
|---|-------|-----|
| 1 | Hardcoded 'ر.س' currency symbol in 5 places | Created `CurrencyHelper` class with `format()` and `getSymbol()`. Updated `PaymentModel`, `TeacherEarningsRecord`, `TeacherEarningsSummary`, `SessionEarning`, `PaymentSummary` |
| 2 | `TeacherPackageModel` default currency was 'ر.س' (symbol) | Changed to 'SAR' (ISO code) |
| 3 | `meetingCreate` endpoint: `/meetings/create` | Fixed to `/teacher/meetings/create` (backend route is under teacher prefix) |
| 4 | `AcademyModel.itqan()` hardcoded `https://itqan-academy.com` | Changed to `https://itqanway.com` (actual production domain) |
| 5 | `getPurchaseUrl()` used POST with body params | Changed to GET with URL path segments `/student/purchase-url/{type}/{id}` |

### Commit: 7b2e888 (mobile repo, local) - Deep Field Alignment

| # | Issue | Fix |
|---|-------|-----|
| 6 | Quiz: 6 field name mismatches (total_questions, time_limit_minutes, etc.) | Added fallback parsing for both API and mobile field names |
| 7 | Homework: structural mismatch (flat vs nested teacher/submission) | Handle both nested API objects and flat fields |
| 8 | Homework: `HomeworkSubmission.submittedAt` null crash | Changed from `DateTime` to `DateTime?` |
| 9 | Teacher: `bio` vs `bio_arabic`, `bio_en` vs `bio_english` | Added fallbacks for API field names |
| 10 | Teacher: `subjects` array of objects vs strings | Handle both `{id, name}` objects and plain strings |
| 11 | Subscription: `startDate`/`endDate` default to `DateTime.now()` for pending | Made nullable `DateTime?`, updated 5 UI files |

### Files Changed (Mobile) - All Commits
- `lib/core/utils/currency_helper.dart` (NEW)
- `lib/models/payment/payment_model.dart`
- `lib/models/user/teacher_model.dart`
- `lib/models/package/teacher_package_model.dart`
- `lib/models/quiz/quiz_model.dart`
- `lib/models/homework/homework_model.dart`
- `lib/models/subscription/subscription_model.dart`
- `lib/core/api/api_endpoints.dart`
- `lib/models/academy/academy_model.dart`
- `lib/data/repositories/payment_repository_impl.dart`
- `lib/domain/repositories/payment_repository.dart`
- `lib/features/teacher/screens/homework_grading_screen.dart`
- `lib/features/student/screens/subscriptions_screen.dart`
- `lib/features/parent/screens/parent_subscriptions_screen.dart`
- `lib/widgets/cards/subscription_card.dart`
- `lib/widgets/resource_detail/subscription_info_widget.dart`
- `lib/widgets/section_detail/subscription_info_card.dart`

---

## Phase 4: Audit Improvements (DONE)

### Commit: 94633538 - Deployed
Backend code quality improvements found during audit:
- Subscription renewal processor and command: proper tenant handling
- `EnsureSubscriptionAccess` middleware: `academy_id` column alignment
- `Payment` model: replaced `str_contains()` type checks with `instanceof`
- `HandlesSubscriptionRenewal`: grace period logic fix
- `BaseSubscriptionObserver`: safer activation handling
- `EasyKashSignatureService`: signature fix
- 6 test suites updated for schema/API compatibility

---

## Model Field Mapping Verification

### Mobile Models vs API Response - Status
| Model | Status | Notes |
|-------|--------|-------|
| SessionModel | ALIGNED | `meeting_url` standardized, `session_notes` fixed |
| SubscriptionModel | FIXED | Nullable dates for pending subscriptions, 5 UI files updated |
| PaymentModel | FIXED | Currency now dynamic via CurrencyHelper |
| UserModel | ALIGNED | `avatar_url` → `avatar` fallback works |
| TeacherModel | FIXED | Bio fallbacks (bio/bio_en), subjects handles objects+strings |
| HomeworkModel | FIXED | Nested teacher/submission objects, submittedAt null safety |
| QuizModel | FIXED | 6 field name fallbacks (total_questions, time_limit_minutes, etc.) |
| ConversationModel | FIXED | `user_type` now in archived participants |
| AcademyModel | FIXED | Production URL corrected |
| ParticipantModel | ALIGNED | Reads `user_type` from JSON |

---

## Known Gaps (Future Work)

### 1. Teacher Trial Request API (Feature Gap)
Mobile defines 5 teacher trial request endpoints that don't exist in the backend:
- `GET /teacher/trial-requests` (list)
- `GET /teacher/trial-requests/{id}` (detail)
- `POST /teacher/trial-requests/{id}/approve`
- `POST /teacher/trial-requests/{id}/reject`
- `POST /teacher/trial-requests/{id}/schedule`

**Impact:** Low - Teachers currently manage trial requests via the web panel.
**Action:** Create these API endpoints when teacher mobile panel is prioritized.

### 2. Backend-Only Routes Not in Mobile (~34)
Routes available in the backend but not used by mobile. These include:
- Recorded course bookmarks, materials, notes, rating
- Chat typing indicators and message reactions
- Teacher homework update
- Various admin/supervisor endpoints

**Impact:** None - these are additional features the mobile could adopt later.

### 3. Missing Academy Logo PNGs
Two academy logos return 404 on admin pages. Low priority cosmetic issue.

---

## Deployment History

| Date | Commit | Changes | Status |
|------|--------|---------|--------|
| Feb 20 | `05611577` | 4 API bugs (A-D) | Deployed |
| Feb 20 | `e48c8b43` | API-mobile alignment (E-I) | Deployed |
| Feb 20 | `94633538` | Code audit improvements | Deployed |
| Feb 20 | `5496def8` | Orphaned Supervisor files cleanup | Deployed |
| Feb 20 | `f22c2b2b` | Archived chat user_type fix (J) | Deployed |
| Feb 20 | `bfaedb2` | Mobile: currency, endpoints, URLs | Local (mobile repo) |
| Feb 20 | `7b2e888` | Mobile: quiz, homework, teacher, subscription model fixes | Local (mobile repo) |

---

## Test Results
- **Backend:** 205 tests pass, 22 skipped, 0 failures
- **Production API:** 420 requests across 7 roles, 0 server errors
- **Mobile:** All 10 critical models verified - 0 Dart analyzer errors
- **Mobile fixes:** 18 files changed across 2 commits (currency, endpoints, field names, null safety)
