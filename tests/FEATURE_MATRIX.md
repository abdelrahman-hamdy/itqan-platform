# Feature Matrix - Itqan Platform

## Overview
This document provides a comprehensive inventory of all testable features organized by module/domain.

---

## 1. Core Models & Domain Logic

### 1.1 Session System (CRITICAL)
| Feature | Location | Type | Priority | Status |
|---------|----------|------|----------|--------|
| BaseSession abstract model | app/Models/BaseSession.php | Model | Critical | Pending |
| QuranSession model | app/Models/QuranSession.php | Model | Critical | Pending |
| AcademicSession model | app/Models/AcademicSession.php | Model | Critical | Pending |
| InteractiveCourseSession model | app/Models/InteractiveCourseSession.php | Model | Critical | Pending |
| SessionStatus enum transitions | app/Enums/SessionStatus.php | Enum | Critical | Pending |
| CountsTowardsSubscription trait | app/Models/Traits/CountsTowardsSubscription.php | Trait | High | Pending |

### 1.2 User System (CRITICAL)
| Feature | Location | Type | Priority | Status |
|---------|----------|------|----------|--------|
| User model with roles | app/Models/User.php | Model | Critical | Pending |
| StudentProfile model | app/Models/StudentProfile.php | Model | High | Pending |
| QuranTeacherProfile model | app/Models/QuranTeacherProfile.php | Model | High | Pending |
| AcademicTeacherProfile model | app/Models/AcademicTeacherProfile.php | Model | High | Pending |
| ParentProfile model | app/Models/ParentProfile.php | Model | High | Pending |
| SupervisorProfile model | app/Models/SupervisorProfile.php | Model | Medium | Pending |
| ParentStudentRelationship model | app/Models/ParentStudentRelationship.php | Model | High | Pending |

### 1.3 Subscription System (CRITICAL)
| Feature | Location | Type | Priority | Status |
|---------|----------|------|----------|--------|
| BaseSubscription model | app/Models/BaseSubscription.php | Model | Critical | Pending |
| QuranSubscription model | app/Models/QuranSubscription.php | Model | Critical | Pending |
| AcademicSubscription model | app/Models/AcademicSubscription.php | Model | Critical | Pending |
| CourseSubscription model | app/Models/CourseSubscription.php | Model | High | Pending |
| SubscriptionStatus enum | app/Enums/SubscriptionStatus.php | Enum | Critical | Pending |

### 1.4 Academy & Multi-tenancy (CRITICAL)
| Feature | Location | Type | Priority | Status |
|---------|----------|------|----------|--------|
| Academy model | app/Models/Academy.php | Model | Critical | Existing |
| AcademySettings model | app/Models/AcademySettings.php | Model | High | Pending |
| Tenant scoping | Global Scopes | Feature | Critical | Pending |

### 1.5 Attendance System (HIGH)
| Feature | Location | Type | Priority | Status |
|---------|----------|------|----------|--------|
| BaseSessionAttendance model | app/Models/BaseSessionAttendance.php | Model | High | Pending |
| QuranSessionAttendance model | app/Models/QuranSessionAttendance.php | Model | High | Pending |
| AcademicSessionAttendance model | app/Models/AcademicSessionAttendance.php | Model | High | Pending |
| InteractiveSessionAttendance model | app/Models/InteractiveSessionAttendance.php | Model | High | Pending |
| MeetingAttendance model | app/Models/MeetingAttendance.php | Model | High | Pending |
| AttendanceStatus enum | app/Enums/AttendanceStatus.php | Enum | High | Pending |

### 1.6 Course System (HIGH)
| Feature | Location | Type | Priority | Status |
|---------|----------|------|----------|--------|
| InteractiveCourse model | app/Models/InteractiveCourse.php | Model | High | Pending |
| RecordedCourse model | app/Models/RecordedCourse.php | Model | Medium | Pending |
| CourseSection model | app/Models/CourseSection.php | Model | Medium | Pending |
| Lesson model | app/Models/Lesson.php | Model | Medium | Pending |
| InteractiveCourseEnrollment model | app/Models/InteractiveCourseEnrollment.php | Model | High | Pending |

### 1.7 Quiz & Homework System (MEDIUM)
| Feature | Location | Type | Priority | Status |
|---------|----------|------|----------|--------|
| Quiz model | app/Models/Quiz.php | Model | Medium | Pending |
| QuizQuestion model | app/Models/QuizQuestion.php | Model | Medium | Pending |
| QuizAttempt model | app/Models/QuizAttempt.php | Model | Medium | Pending |
| HomeworkSubmission model | app/Models/HomeworkSubmission.php | Model | Medium | Pending |
| AcademicHomework model | app/Models/AcademicHomework.php | Model | Medium | Pending |

### 1.8 Payment System (HIGH)
| Feature | Location | Type | Priority | Status |
|---------|----------|------|----------|--------|
| Payment model | app/Models/Payment.php | Model | High | Pending |
| PaymentWebhookEvent model | app/Models/PaymentWebhookEvent.php | Model | Medium | Pending |
| PaymentAuditLog model | app/Models/PaymentAuditLog.php | Model | Medium | Pending |

### 1.9 Circle System (HIGH)
| Feature | Location | Type | Priority | Status |
|---------|----------|------|----------|--------|
| QuranCircle model | app/Models/QuranCircle.php | Model | High | Pending |
| QuranIndividualCircle model | app/Models/QuranIndividualCircle.php | Model | High | Pending |
| QuranCircleSchedule model | app/Models/QuranCircleSchedule.php | Model | High | Pending |

---

## 2. Services Layer

### 2.1 Session Services (CRITICAL)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| CalendarService | app/Services/CalendarService.php | Critical | Existing |
| SessionStatusService | app/Services/UnifiedSessionStatusService.php | Critical | Existing |
| SessionMeetingService | app/Services/SessionMeetingService.php | Critical | Pending |
| AcademicSessionMeetingService | app/Services/AcademicSessionMeetingService.php | Critical | Pending |
| AutoMeetingCreationService | app/Services/AutoMeetingCreationService.php | High | Pending |
| SessionManagementService | app/Services/SessionManagementService.php | High | Pending |

### 2.2 Scheduling Services (HIGH)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| QuranSessionSchedulingService | app/Services/QuranSessionSchedulingService.php | High | Pending |
| AcademicSessionSchedulingService | app/Services/AcademicSessionSchedulingService.php | High | Pending |
| SessionSchedule validators | app/Services/Scheduling/Validators/ | High | Pending |

### 2.3 Subscription Services (HIGH)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| SubscriptionService | app/Services/SubscriptionService.php | High | Pending |
| SubscriptionRenewalService | app/Services/SubscriptionRenewalService.php | High | Pending |
| QuranSubscriptionDetailsService | app/Services/QuranSubscriptionDetailsService.php | High | Pending |
| AcademicSubscriptionDetailsService | app/Services/AcademicSubscriptionDetailsService.php | High | Pending |

### 2.4 Attendance Services (HIGH)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| MeetingAttendanceService | app/Services/MeetingAttendanceService.php | High | Pending |
| AttendanceEventService | app/Services/AttendanceEventService.php | High | Pending |
| Attendance Report Services | app/Services/Attendance/*.php | Medium | Pending |

### 2.5 Notification Services (HIGH)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| NotificationService | app/Services/NotificationService.php | High | Existing |
| SessionNotificationService | app/Services/SessionNotificationService.php | High | Pending |
| ParentNotificationService | app/Services/ParentNotificationService.php | Medium | Pending |

### 2.6 Payment Services (HIGH)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| PaymentService | app/Services/PaymentService.php | High | Pending |
| PaymentGatewayManager | app/Services/Payment/PaymentGatewayManager.php | High | Pending |
| PaymobGateway | app/Services/Payment/Gateways/PaymobGateway.php | High | Pending |
| PaymentStateMachine | app/Services/Payment/PaymentStateMachine.php | High | Pending |

### 2.7 Meeting Services (HIGH)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| LiveKitService | app/Services/LiveKitService.php | High | Pending |
| MeetingDataChannelService | app/Services/MeetingDataChannelService.php | Medium | Pending |
| RoomPermissionService | app/Services/RoomPermissionService.php | Medium | Pending |

### 2.8 Student Services (MEDIUM)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| StudentDashboardService | app/Services/StudentDashboardService.php | Medium | Pending |
| StudentStatisticsService | app/Services/StudentStatisticsService.php | Medium | Pending |
| StudentProfileService | app/Services/StudentProfileService.php | Medium | Pending |
| StudentSearchService | app/Services/StudentSearchService.php | Medium | Pending |
| StudentSubscriptionService | app/Services/StudentSubscriptionService.php | Medium | Pending |

### 2.9 Parent Services (MEDIUM)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| ParentDashboardService | app/Services/ParentDashboardService.php | Medium | Pending |
| ParentDataService | app/Services/ParentDataService.php | Medium | Pending |

### 2.10 Other Services (MEDIUM)
| Service | Location | Priority | Status |
|---------|----------|----------|--------|
| HomeworkService | app/Services/HomeworkService.php | Medium | Pending |
| QuizService | app/Services/QuizService.php | Medium | Pending |
| CertificateService | app/Services/CertificateService.php | Medium | Pending |
| ReviewService | app/Services/ReviewService.php | Low | Pending |
| ChatPermissionService | app/Services/ChatPermissionService.php | Medium | Pending |

---

## 3. Policies (Authorization)

| Policy | Location | Priority | Status |
|--------|----------|----------|--------|
| SessionPolicy | app/Policies/SessionPolicy.php | Critical | Existing |
| CertificatePolicy | app/Policies/CertificatePolicy.php | Medium | Pending |
| ParentPolicy | app/Policies/ParentPolicy.php | Medium | Pending |
| PaymentPolicy | app/Policies/PaymentPolicy.php | High | Pending |
| SubscriptionPolicy | app/Policies/SubscriptionPolicy.php | High | Pending |
| StudentProfilePolicy | app/Policies/StudentProfilePolicy.php | Medium | Pending |
| TeacherProfilePolicy | app/Policies/TeacherProfilePolicy.php | Medium | Pending |

---

## 4. API Controllers

### 4.1 Authentication API (CRITICAL)
| Controller | Location | Priority | Status |
|------------|----------|----------|--------|
| LoginController | app/Http/Controllers/Api/V1/Auth/LoginController.php | Critical | Pending |
| RegisterController | app/Http/Controllers/Api/V1/Auth/RegisterController.php | Critical | Pending |
| ForgotPasswordController | app/Http/Controllers/Api/V1/Auth/ForgotPasswordController.php | High | Pending |
| TokenController | app/Http/Controllers/Api/V1/Auth/TokenController.php | High | Pending |

### 4.2 Student API (HIGH)
| Controller | Location | Priority | Status |
|------------|----------|----------|--------|
| Student DashboardController | app/Http/Controllers/Api/V1/Student/DashboardController.php | High | Pending |
| Student SessionController | app/Http/Controllers/Api/V1/Student/SessionController.php | High | Pending |
| Student HomeworkController | app/Http/Controllers/Api/V1/Student/HomeworkController.php | Medium | Pending |
| Student QuizController | app/Http/Controllers/Api/V1/Student/QuizController.php | Medium | Pending |
| Student PaymentController | app/Http/Controllers/Api/V1/Student/PaymentController.php | High | Pending |
| Student SubscriptionController | app/Http/Controllers/Api/V1/Student/SubscriptionController.php | High | Pending |

### 4.3 Parent API (HIGH)
| Controller | Location | Priority | Status |
|------------|----------|----------|--------|
| Parent DashboardController | app/Http/Controllers/Api/V1/ParentApi/DashboardController.php | High | Pending |
| Parent ChildrenController | app/Http/Controllers/Api/V1/ParentApi/ChildrenController.php | High | Pending |
| Parent SessionController | app/Http/Controllers/Api/V1/ParentApi/SessionController.php | High | Pending |
| Parent SubscriptionController | app/Http/Controllers/Api/V1/ParentApi/SubscriptionController.php | High | Pending |

### 4.4 Teacher API (HIGH)
| Controller | Location | Priority | Status |
|------------|----------|----------|--------|
| Teacher DashboardController | app/Http/Controllers/Api/V1/Teacher/DashboardController.php | High | Pending |
| Teacher Quran SessionController | app/Http/Controllers/Api/V1/Teacher/Quran/SessionController.php | High | Pending |
| Teacher Academic SessionController | app/Http/Controllers/Api/V1/Teacher/Academic/SessionController.php | High | Pending |
| Teacher MeetingController | app/Http/Controllers/Api/V1/Teacher/MeetingController.php | High | Pending |
| Teacher HomeworkController | app/Http/Controllers/Api/V1/Teacher/HomeworkController.php | Medium | Pending |

### 4.5 Common API (MEDIUM)
| Controller | Location | Priority | Status |
|------------|----------|----------|--------|
| NotificationController | app/Http/Controllers/Api/V1/Common/NotificationController.php | Medium | Pending |
| ChatController | app/Http/Controllers/Api/V1/Common/ChatController.php | Medium | Pending |
| MeetingTokenController | app/Http/Controllers/Api/V1/Common/MeetingTokenController.php | High | Pending |

---

## 5. Web Controllers

### 5.1 Session Controllers (HIGH)
| Controller | Location | Priority | Status |
|------------|----------|----------|--------|
| QuranSessionController | app/Http/Controllers/QuranSessionController.php | High | Pending |
| AcademicSessionController | app/Http/Controllers/AcademicSessionController.php | High | Pending |
| UnifiedMeetingController | app/Http/Controllers/UnifiedMeetingController.php | High | Pending |

### 5.2 Parent Web Controllers (HIGH)
| Controller | Location | Priority | Status |
|------------|----------|----------|--------|
| ParentDashboardController | app/Http/Controllers/ParentDashboardController.php | High | Pending |
| ParentRegistrationController | app/Http/Controllers/ParentRegistrationController.php | High | Pending |
| ParentSubscriptionController | app/Http/Controllers/ParentSubscriptionController.php | High | Pending |
| ParentPaymentController | app/Http/Controllers/ParentPaymentController.php | High | Pending |

### 5.3 Payment Controllers (HIGH)
| Controller | Location | Priority | Status |
|------------|----------|----------|--------|
| PaymentController | app/Http/Controllers/PaymentController.php | High | Pending |
| PaymobWebhookController | app/Http/Controllers/PaymobWebhookController.php | High | Pending |

---

## 6. Middleware

| Middleware | Location | Priority | Status |
|------------|----------|----------|--------|
| TenantMiddleware | app/Http/Middleware/TenantMiddleware.php | Critical | Pending |
| AcademyContext | app/Http/Middleware/AcademyContext.php | Critical | Pending |
| RoleMiddleware | app/Http/Middleware/RoleMiddleware.php | High | Pending |
| CheckMaintenanceMode | app/Http/Middleware/CheckMaintenanceMode.php | Medium | Existing |
| API Middleware Stack | app/Http/Middleware/Api/*.php | High | Pending |

---

## 7. Livewire Components

| Component | Location | Priority | Status |
|-----------|----------|----------|--------|
| NotificationCenter | app/Livewire/NotificationCenter.php | Medium | Pending |
| AcademySelector | app/Livewire/AcademySelector.php | Medium | Pending |
| ReviewForm | app/Livewire/ReviewForm.php | Low | Pending |
| QuizzesWidget | app/Livewire/QuizzesWidget.php | Medium | Pending |
| IssueCertificateModal | app/Livewire/IssueCertificateModal.php | Medium | Pending |
| Student Search | app/Livewire/Student/Search.php | Medium | Pending |
| Student AttendanceStatus | app/Livewire/Student/AttendanceStatus.php | Medium | Pending |

---

## 8. Jobs

| Job | Location | Priority | Status |
|-----|----------|----------|--------|
| CalculateSessionAttendance | app/Jobs/CalculateSessionAttendance.php | High | Pending |
| CalculateSessionEarningsJob | app/Jobs/CalculateSessionEarningsJob.php | Medium | Pending |
| RetryAttendanceOperation | app/Jobs/RetryAttendanceOperation.php | Medium | Pending |
| ReconcileOrphanedAttendanceEvents | app/Jobs/ReconcileOrphanedAttendanceEvents.php | Low | Pending |

---

## 9. Events & Listeners

| Event/Listener | Location | Priority | Status |
|----------------|----------|----------|--------|
| AttendanceUpdated Event | app/Events/AttendanceUpdated.php | High | Pending |
| SessionCompletedEvent | app/Events/SessionCompletedEvent.php | High | Pending |
| NotificationSent Event | app/Events/NotificationSent.php | Medium | Pending |
| MeetingCommandEvent | app/Events/MeetingCommandEvent.php | Medium | Pending |
| FinalizeAttendanceListener | app/Listeners/FinalizeAttendanceListener.php | High | Pending |

---

## Summary Statistics

| Category | Total | Critical | High | Medium | Low | Tested |
|----------|-------|----------|------|--------|-----|--------|
| Models | 77+ | 15 | 25 | 30 | 7 | 0 |
| Services | 84+ | 10 | 25 | 35 | 14 | 3 |
| Policies | 7 | 1 | 2 | 4 | 0 | 1 |
| API Controllers | 30+ | 4 | 15 | 11 | 0 | 0 |
| Web Controllers | 40+ | 0 | 20 | 15 | 5 | 0 |
| Middleware | 20 | 2 | 5 | 10 | 3 | 1 |
| Livewire Components | 11 | 0 | 0 | 8 | 3 | 0 |
| Jobs | 4 | 0 | 1 | 2 | 1 | 0 |
| Events/Listeners | 5 | 0 | 2 | 2 | 1 | 0 |

---

*Last Updated: 2025-12-21*
