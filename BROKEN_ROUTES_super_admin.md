# Broken Routes Report - super_admin

**Generated:** 2025-12-23 08:55:33
**Authenticated as:** super@test.itqan.com (ID: 326)
**User Type:** super_admin

## Summary

| Status | Count |
|--------|-------|
| ✅ Success (2xx) | 76 |
| ↪️ Redirects (3xx) | 0 |
| ⚠️ Client Errors (4xx) | 60 |
| ❌ Server Errors (5xx) | 0 |

## ⚠️ Client Errors (May Need Attention)

- [403] /supervisor-panel → App\Filament\Supervisor\Pages\Dashboard
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
- [403] /broadcasting/auth → \Illuminate\Broadcasting\BroadcastController@authenticate
- [422] /livekit/participants → App\Http\Controllers\LiveKitController@getRoomParticipants
- [422] /livekit/rooms/permissions → App\Http\Controllers\LiveKitController@getRoomPermissions
- [404] / → App\Http\Controllers\PlatformController@home
- [404] /about → Closure
- [404] /contact → Closure
- [404] /features → Closure
- [404] /business-services → App\Http\Controllers\BusinessServiceController@index
- [404] /portfolio → App\Http\Controllers\BusinessServiceController@portfolio
- [404] /business-services/categories → App\Http\Controllers\BusinessServiceController@getCategories
- [404] /business-services/portfolio → App\Http\Controllers\BusinessServiceController@getPortfolioItems
- [404] /old-home → Closure

