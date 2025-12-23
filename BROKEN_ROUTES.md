# Broken Routes Report

**Generated:** 2025-12-23 07:50:19
**Authenticated as:** admin@itqan.com (ID: 1)

## Summary

| Status | Count |
|--------|-------|
| ✅ Success (2xx) | 78 |
| ↪️ Redirects (3xx) | 7 |
| ⚠️ Client Errors (4xx) | 164 |
| ❌ Server Errors (5xx) | 6 |

## ❌ Server Errors (MUST FIX)

### #1 [500] /teacher-panel/login

- **Route Name:** filament.teacher.auth.login
- **Action:** Filament\Pages\Auth\Login
- **Middleware:** panel:teacher, Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse, Illuminate\Session\Middleware\StartSession, Illuminate\Session\Middleware\AuthenticateSession, Illuminate\View\Middleware\ShareErrorsFromSession, Illuminate\Foundation\Http\Middleware\VerifyCsrfToken, Illuminate\Routing\Middleware\SubstituteBindings, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent

---

### #2 [500] /teacher-panel

- **Route Name:** filament.teacher.pages.dashboard
- **Action:** App\Filament\Teacher\Pages\Dashboard
- **Middleware:** panel:teacher, Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse, Illuminate\Session\Middleware\StartSession, Illuminate\Session\Middleware\AuthenticateSession, Illuminate\View\Middleware\ShareErrorsFromSession, Illuminate\Foundation\Http\Middleware\VerifyCsrfToken, Illuminate\Routing\Middleware\SubstituteBindings, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\IdentifyTenant

---

### #3 [500] /teacher-panel

- **Route Name:** filament.teacher.tenant
- **Action:** Filament\Http\Controllers\RedirectToTenantController
- **Middleware:** panel:teacher, Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse, Illuminate\Session\Middleware\StartSession, Illuminate\Session\Middleware\AuthenticateSession, Illuminate\View\Middleware\ShareErrorsFromSession, Illuminate\Foundation\Http\Middleware\VerifyCsrfToken, Illuminate\Routing\Middleware\SubstituteBindings, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Http\Middleware\Authenticate

---

### #4 [500] /panel

- **Route Name:** academy.admin.dashboard
- **Action:** Closure
- **Middleware:** web, auth, role:academy_admin

---

### #5 [500] /livekit/participants

- **Route Name:** -
- **Action:** App\Http\Controllers\LiveKitController@getRoomParticipants
- **Middleware:** web, auth

---

### #6 [500] /livekit/rooms/permissions

- **Route Name:** -
- **Action:** App\Http\Controllers\LiveKitController@getRoomPermissions
- **Middleware:** web, auth

---

## ⚠️ Client Errors (May Need Attention)

- [404] /academic-teacher-panel/unified-teacher-calendar → App\Filament\Shared\Pages\UnifiedTeacherCalendar
- [404] /academic-teacher-panel/academic-individual-lessons → App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages\ListAcademicIndividualLessons
- [404] /academic-teacher-panel/academic-individual-lessons/create → App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages\CreateAcademicIndividualLesson
- [404] /academic-teacher-panel/academic-session-reports → App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages\ListAcademicSessionReports
- [404] /academic-teacher-panel/academic-session-reports/create → App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages\CreateAcademicSessionReport
- [404] /academic-teacher-panel/academic-sessions → App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages\ListAcademicSessions
- [404] /academic-teacher-panel/academic-sessions/create → App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages\CreateAcademicSession
- [404] /academic-teacher-panel/certificates → App\Filament\AcademicTeacher\Resources\CertificateResource\Pages\ListCertificates
- [404] /academic-teacher-panel/homework-submissions → App\Filament\AcademicTeacher\Resources\HomeworkSubmissionResource\Pages\ListHomeworkSubmissions
- [404] /academic-teacher-panel/interactive-courses → App\Filament\AcademicTeacher\Resources\InteractiveCourseResource\Pages\ListInteractiveCourses
- [404] /academic-teacher-panel/interactive-course-sessions → App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages\ListInteractiveCourseSessions
- [404] /academic-teacher-panel/interactive-course-sessions/create → App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages\CreateInteractiveCourseSession
- [404] /academic-teacher-panel/interactive-session-reports → App\Filament\AcademicTeacher\Resources\InteractiveSessionReportResource\Pages\ListInteractiveSessionReports
- [404] /academic-teacher-panel/interactive-session-reports/create → App\Filament\AcademicTeacher\Resources\InteractiveSessionReportResource\Pages\CreateInteractiveSessionReport
- [404] /academic-teacher-panel/quiz-assignments → App\Filament\AcademicTeacher\Resources\QuizAssignmentResource\Pages\ListQuizAssignments
- [404] /academic-teacher-panel/quiz-assignments/create → App\Filament\AcademicTeacher\Resources\QuizAssignmentResource\Pages\CreateQuizAssignment
- [404] /academic-teacher-panel/quizzes → App\Filament\AcademicTeacher\Resources\QuizResource\Pages\ListQuizzes
- [404] /academic-teacher-panel/quizzes/create → App\Filament\AcademicTeacher\Resources\QuizResource\Pages\CreateQuiz
- [403] /supervisor-panel → App\Filament\Supervisor\Pages\Dashboard
- [404] /teacher-panel/unified-teacher-calendar → App\Filament\Shared\Pages\UnifiedTeacherCalendar
- [404] /teacher-panel/certificates → App\Filament\Teacher\Resources\CertificateResource\Pages\ListCertificates
- [404] /teacher-panel/homework-submissions → App\Filament\Teacher\Resources\HomeworkSubmissionResource\Pages\ListHomeworkSubmissions
- [404] /teacher-panel/quiz-assignments → App\Filament\Teacher\Resources\QuizAssignmentResource\Pages\ListQuizAssignments
- [404] /teacher-panel/quiz-assignments/create → App\Filament\Teacher\Resources\QuizAssignmentResource\Pages\CreateQuizAssignment
- [404] /teacher-panel/quizzes → App\Filament\Teacher\Resources\QuizResource\Pages\ListQuizzes
- [404] /teacher-panel/quizzes/create → App\Filament\Teacher\Resources\QuizResource\Pages\CreateQuiz
- [404] /teacher-panel/quran-circles → App\Filament\Teacher\Resources\QuranCircleResource\Pages\ListQuranCircles
- [404] /teacher-panel/quran-circles/create → App\Filament\Teacher\Resources\QuranCircleResource\Pages\CreateQuranCircle
- [404] /teacher-panel/quran-individual-circles → App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages\ListQuranIndividualCircles
- [404] /teacher-panel/quran-individual-circles/create → App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages\CreateQuranIndividualCircle
- [404] /teacher-panel/quran-sessions → App\Filament\Teacher\Resources\QuranSessionResource\Pages\ListQuranSessions
- [404] /teacher-panel/quran-sessions/create → App\Filament\Teacher\Resources\QuranSessionResource\Pages\CreateQuranSession
- [404] /teacher-panel/quran-trial-requests → App\Filament\Teacher\Resources\QuranTrialRequestResource\Pages\ListQuranTrialRequests
- [404] /teacher-panel/student-session-reports → App\Filament\Teacher\Resources\StudentSessionReportResource\Pages\ListStudentSessionReports
- [404] /teacher-panel/teacher-earnings → App\Filament\Teacher\Resources\TeacherEarningsResource\Pages\ListTeacherEarnings
- [400] /api/sessions/meeting/info → App\Http\Controllers\UnifiedMeetingController@getRoomInfo
- [403] /api/v1/token/validate → App\Http\Controllers\Api\V1\Auth\TokenController@validateToken
- [403] /api/v1/me → App\Http\Controllers\Api\V1\Auth\LoginController@me
- [403] /api/v1/student/dashboard → App\Http\Controllers\Api\V1\Student\DashboardController@index
- [403] /api/v1/student/sessions → App\Http\Controllers\Api\V1\Student\SessionController@index
- [403] /api/v1/student/sessions/upcoming → App\Http\Controllers\Api\V1\Student\SessionController@upcoming
- [403] /api/v1/student/sessions/today → App\Http\Controllers\Api\V1\Student\SessionController@today
- [403] /api/v1/student/subscriptions → App\Http\Controllers\Api\V1\Student\SubscriptionController@index
- [403] /api/v1/student/homework → App\Http\Controllers\Api\V1\Student\HomeworkController@index
- [403] /api/v1/student/quizzes → App\Http\Controllers\Api\V1\Student\QuizController@index
- [403] /api/v1/student/certificates → App\Http\Controllers\Api\V1\Student\CertificateController@index
- [403] /api/v1/student/payments → App\Http\Controllers\Api\V1\Student\PaymentController@index
- [403] /api/v1/student/calendar → App\Http\Controllers\Api\V1\Student\CalendarController@index
- [403] /api/v1/student/profile → App\Http\Controllers\Api\V1\Student\ProfileController@show
- [403] /api/v1/student/teachers/quran → App\Http\Controllers\Api\V1\Student\TeacherController@quranTeachers
- [403] /api/v1/student/teachers/academic → App\Http\Controllers\Api\V1\Student\TeacherController@academicTeachers
- [403] /api/v1/student/courses/interactive → App\Http\Controllers\Api\V1\Student\CourseController@index
- [403] /api/v1/parent/dashboard → App\Http\Controllers\Api\V1\ParentApi\DashboardController@index
- [403] /api/v1/parent/children → App\Http\Controllers\Api\V1\ParentApi\ChildrenController@index
- [403] /api/v1/parent/payments → App\Http\Controllers\Api\V1\ParentApi\PaymentController@index
- [403] /api/v1/parent/subscriptions → App\Http\Controllers\Api\V1\ParentApi\SubscriptionController@index
- [403] /api/v1/parent/reports/progress → App\Http\Controllers\Api\V1\ParentApi\ReportController@progress
- [403] /api/v1/parent/reports/attendance → App\Http\Controllers\Api\V1\ParentApi\ReportController@attendance
- [403] /api/v1/parent/sessions → App\Http\Controllers\Api\V1\ParentApi\SessionController@index
- [403] /api/v1/parent/sessions/today → App\Http\Controllers\Api\V1\ParentApi\SessionController@today
- [403] /api/v1/parent/sessions/upcoming → App\Http\Controllers\Api\V1\ParentApi\SessionController@upcoming
- [403] /api/v1/parent/quizzes → App\Http\Controllers\Api\V1\ParentApi\QuizController@index
- [403] /api/v1/parent/certificates → App\Http\Controllers\Api\V1\ParentApi\CertificateController@index
- [403] /api/v1/parent/profile → App\Http\Controllers\Api\V1\ParentApi\ProfileController@show
- [403] /api/v1/teacher/dashboard → App\Http\Controllers\Api\V1\Teacher\DashboardController@index
- [403] /api/v1/teacher/schedule → App\Http\Controllers\Api\V1\Teacher\ScheduleController@index
- [403] /api/v1/teacher/quran/circles/individual → App\Http\Controllers\Api\V1\Teacher\Quran\CircleController@individualIndex
- [403] /api/v1/teacher/quran/circles/group → App\Http\Controllers\Api\V1\Teacher\Quran\CircleController@groupIndex
- [403] /api/v1/teacher/quran/sessions → App\Http\Controllers\Api\V1\Teacher\Quran\SessionController@index
- [403] /api/v1/teacher/academic/lessons → App\Http\Controllers\Api\V1\Teacher\Academic\LessonController@index
- [403] /api/v1/teacher/academic/courses → App\Http\Controllers\Api\V1\Teacher\Academic\CourseController@index
- [403] /api/v1/teacher/academic/sessions → App\Http\Controllers\Api\V1\Teacher\Academic\SessionController@index
- [403] /api/v1/teacher/students → App\Http\Controllers\Api\V1\Teacher\StudentController@index
- [403] /api/v1/teacher/homework → App\Http\Controllers\Api\V1\Teacher\HomeworkController@index
- [403] /api/v1/teacher/earnings → App\Http\Controllers\Api\V1\Teacher\EarningsController@summary
- [403] /api/v1/teacher/earnings/history → App\Http\Controllers\Api\V1\Teacher\EarningsController@history
- [403] /api/v1/teacher/payouts → App\Http\Controllers\Api\V1\Teacher\EarningsController@payouts
- [403] /api/v1/teacher/profile → App\Http\Controllers\Api\V1\Teacher\ProfileController@show
- [403] /api/v1/notifications → App\Http\Controllers\Api\V1\Common\NotificationController@index
- [403] /api/v1/notifications/unread-count → App\Http\Controllers\Api\V1\Common\NotificationController@unreadCount
- [403] /api/v1/chat/conversations → App\Http\Controllers\Api\V1\Common\ChatController@conversations
- [403] /api/v1/chat/unread-count → App\Http\Controllers\Api\V1\Common\ChatController@unreadCount
- [404] /login → App\Http\Controllers\Auth\AuthController@showLoginForm
- [404] /forgot-password → App\Http\Controllers\Auth\AuthController@showForgotPasswordForm
- [404] /register → App\Http\Controllers\Auth\AuthController@showStudentRegistration
- [404] /teacher/register → App\Http\Controllers\Auth\AuthController@showTeacherRegistration
- [404] /teacher/register/step2 → App\Http\Controllers\Auth\AuthController@showTeacherRegistrationStep2
- [404] /teacher/register/success → App\Http\Controllers\Auth\AuthController@showTeacherRegistrationSuccess
- [404] /parent/register → App\Http\Controllers\ParentRegistrationController@showRegistrationForm
- [404] /profile/edit → App\Http\Controllers\StudentProfileController@edit
- [404] /subscriptions → App\Http\Controllers\StudentProfileController@subscriptions
- [404] /my-assignments → Closure
- [404] /profile → App\Http\Controllers\StudentProfileController@index
- [404] /my-children → Closure
- [404] /payments → App\Http\Controllers\StudentProfileController@payments
- [404] /reports → Closure
- [404] /supervisor → Closure
- [404] /teacher → Closure
- [404] /teacher/panel-redirect → Closure
- [404] /teacher/profile → App\Http\Controllers\TeacherProfileController@index
- [404] /teacher/profile/edit → App\Http\Controllers\TeacherProfileController@edit
- [404] /teacher/earnings → App\Http\Controllers\TeacherProfileController@earnings
- [404] /teacher/schedule → App\Http\Controllers\TeacherProfileController@schedule
- [404] /teacher/students → App\Http\Controllers\TeacherProfileController@students
- [404] /teacher/meetings/platforms → App\Http\Controllers\MeetingLinkController@getMeetingPlatforms
- [404] /teacher/academic/lessons → App\Http\Controllers\AcademicIndividualLessonController@index
- [403] /broadcasting/auth → \Illuminate\Broadcasting\BroadcastController@authenticate
- [404] / → App\Http\Controllers\PlatformController@home
- [404] /about → Closure
- [404] /contact → Closure
- [404] /features → Closure
- [404] /business-services → App\Http\Controllers\BusinessServiceController@index
- [404] /portfolio → App\Http\Controllers\BusinessServiceController@portfolio
- [404] /business-services/categories → App\Http\Controllers\BusinessServiceController@getCategories
- [404] /business-services/portfolio → App\Http\Controllers\BusinessServiceController@getPortfolioItems
- [404] /old-home → Closure
- [404] / → App\Http\Controllers\AcademyHomepageController@show
- [404] /terms → App\Http\Controllers\StaticPageController@terms
- [404] /refund-policy → App\Http\Controllers\StaticPageController@refundPolicy
- [404] /privacy-policy → App\Http\Controllers\StaticPageController@privacyPolicy
- [404] /about-us → App\Http\Controllers\StaticPageController@aboutUs
- [404] /courses → App\Http\Controllers\RecordedCourseController@index
- [404] /courses/create → App\Http\Controllers\RecordedCourseController@create
- [404] /certificates/preview → App\Http\Controllers\CertificateController@preview
- [404] /notifications → App\Http\Controllers\NotificationController@index
- [404] /payments/history → App\Http\Controllers\PaymentController@history
- [404] /certificates → App\Http\Controllers\CertificateController@index
- [404] /search → App\Http\Controllers\StudentProfileController@search
- [404] /my-quran-teachers → \Illuminate\Routing\RedirectController
- [404] /my-quran-circles → \Illuminate\Routing\RedirectController
- [404] /my-academic-teachers → \Illuminate\Routing\RedirectController
- [404] /my-interactive-courses → \Illuminate\Routing\RedirectController
- [404] /homework → App\Http\Controllers\Student\HomeworkController@index
- [404] /quizzes → Closure
- [404] /quran-teachers → App\Http\Controllers\UnifiedQuranTeacherController@index
- [404] /academic-teachers → App\Http\Controllers\UnifiedAcademicTeacherController@index
- [404] /academic-packages → App\Http\Controllers\PublicAcademicPackageController@index
- [404] /quran-circles → App\Http\Controllers\UnifiedQuranCircleController@index
- [404] /interactive-courses → App\Http\Controllers\UnifiedInteractiveCourseController@index
- [404] /teacher/homework → App\Http\Controllers\Teacher\HomeworkGradingController@index
- [404] /teacher/homework/statistics → App\Http\Controllers\Teacher\HomeworkGradingController@statistics
- [404] /teacher/academic-sessions → App\Http\Controllers\AcademicSessionController@index
- [404] /teacher/interactive-courses → App\Http\Controllers\AcademicIndividualLessonController@interactiveCoursesIndex
- [404] /teacher/individual-circles → App\Http\Controllers\QuranIndividualCircleController@index
- [404] /teacher/group-circles → App\Http\Controllers\QuranGroupCircleScheduleController@index
- [404] /student/calendar → App\Http\Controllers\StudentCalendarController@index
- [404] /student/calendar/events → App\Http\Controllers\StudentCalendarController@getEvents
- [404] /csrf-token → Closure
- [404] /chats → App\Livewire\Pages\Chats
- [404] /parent → App\Http\Controllers\ParentProfileController@index
- [404] /parent/profile → App\Http\Controllers\ParentProfileController@index
- [404] /parent/profile/edit → App\Http\Controllers\ParentProfileController@edit
- [404] /parent/children → App\Http\Controllers\ParentChildrenController@index
- [404] /parent/sessions/upcoming → App\Http\Controllers\ParentSessionController@upcoming
- [404] /parent/sessions/history → App\Http\Controllers\ParentSessionController@history
- [404] /parent/calendar → App\Http\Controllers\ParentCalendarController@index
- [404] /parent/calendar/events → App\Http\Controllers\ParentCalendarController@getEvents
- [404] /parent/subscriptions → App\Http\Controllers\ParentSubscriptionController@index
- [404] /parent/payments → App\Http\Controllers\ParentPaymentController@index
- [404] /parent/certificates → App\Http\Controllers\ParentCertificateController@index
- [404] /parent/reports/progress → App\Http\Controllers\ParentReportController@progressReport
- [404] /parent/reports/attendance → Closure
- [404] /parent/homework → App\Http\Controllers\ParentHomeworkController@index
- [404] /parent/quizzes → App\Http\Controllers\ParentQuizController@index

