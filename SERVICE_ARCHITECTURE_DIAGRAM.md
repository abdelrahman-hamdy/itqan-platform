# Service Architecture Diagram

## Overview

This document provides visual representations of the refactored service architecture, showing how services interact and delegate responsibilities.

---

## 1. Session Status Service Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    FACADE LAYER (Public API)                    │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │      UnifiedSessionStatusService (103 lines)              │ │
│  │  Implements: UnifiedSessionStatusServiceInterface         │ │
│  │             SessionStatusServiceInterface                 │ │
│  │                                                             │ │
│  │  • transitionToReady()                                    │ │
│  │  • transitionToOngoing()                                  │ │
│  │  • transitionToCompleted()                                │ │
│  │  • transitionToCancelled()                                │ │
│  │  • processStatusTransitions()                             │ │
│  └───────────────┬─────────────────┬───────────────────────────┘ │
│                  │                 │                             │
└──────────────────┼─────────────────┼─────────────────────────────┘
                   │                 │
                   ▼                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                      SERVICE LAYER (Implementation)              │
│                                                                  │
│  ┌──────────────────────────┐  ┌─────────────────────────────┐ │
│  │ SessionTransitionService │  │ SessionSchedulerService     │ │
│  │      (536 lines)         │  │      (320 lines)            │ │
│  │                          │  │                             │ │
│  │ Responsibilities:        │  │ Responsibilities:           │ │
│  │ • State validation       │  │ • Batch processing          │ │
│  │ • Transition logic       │  │ • Status checks             │ │
│  │ • Event broadcasting     │  │ • Automated transitions     │ │
│  │ • Error handling         │  │ • Scheduling logic          │ │
│  │ • Status updates         │  │ • Performance optimization  │ │
│  └──────────────────────────┘  └─────────────────────────────┘ │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                      USAGE PATTERN                               │
│                                                                  │
│  Controller/Command                                              │
│       │                                                          │
│       ├─→ inject UnifiedSessionStatusService                    │
│       │                                                          │
│       └─→ call transitionToOngoing($session)                    │
│                │                                                 │
│                ├─→ delegates to SessionTransitionService        │
│                │        │                                        │
│                │        ├─→ validates transition                │
│                │        ├─→ updates database                    │
│                │        ├─→ broadcasts event                    │
│                │        └─→ returns result                      │
│                │                                                 │
│                └─→ returns to controller                        │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 2. Earnings Calculation Service Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    FACADE LAYER (Public API)                    │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │      EarningsCalculationService (42 lines)                │ │
│  │  Implements: EarningsCalculationServiceInterface          │ │
│  │                                                             │ │
│  │  • calculateSessionEarnings(session)                      │ │
│  │  • clearTeacherCache(type, id)                            │ │
│  └───────────────┬─────────────────┬───────────────────────────┘ │
│                  │                 │                             │
└──────────────────┼─────────────────┼─────────────────────────────┘
                   │                 │
                   ▼                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                      SERVICE LAYER (Implementation)              │
│                                                                  │
│  ┌──────────────────────────┐  ┌─────────────────────────────┐ │
│  │ EarningsCalculatorService│  │ EarningsReportService       │ │
│  │      (434 lines)         │  │      (280 lines)            │ │
│  │                          │  │                             │ │
│  │ Responsibilities:        │  │ Responsibilities:           │ │
│  │ • Hourly rate calc       │  │ • Database operations       │ │
│  │ • Attendance validation  │  │ • Report persistence        │ │
│  │ • Bonus calculation      │  │ • Cache management          │ │
│  │ • Deduction logic        │  │ • Transaction safety        │ │
│  │ • Business rules         │  │ • Query optimization        │ │
│  │ • Pure calculations      │  │ • Eager loading             │ │
│  └──────────────────────────┘  └─────────────────────────────┘ │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                      DATA FLOW                                   │
│                                                                  │
│  Job: CalculateSessionEarningsJob                               │
│       │                                                          │
│       ├─→ inject EarningsCalculationService                     │
│       │                                                          │
│       └─→ call calculateSessionEarnings($session)               │
│                │                                                 │
│                ├─→ EarningsReportService                        │
│                │        │                                        │
│                │        ├─→ validates session                   │
│                │        │                                        │
│                │        ├─→ EarningsCalculatorService           │
│                │        │        │                               │
│                │        │        ├─→ calculate base earnings    │
│                │        │        ├─→ apply bonuses              │
│                │        │        └─→ return amount              │
│                │        │                                        │
│                │        ├─→ create TeacherEarning record        │
│                │        ├─→ clear cache                         │
│                │        └─→ return TeacherEarning               │
│                │                                                 │
│                └─→ returns to job                               │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Meeting Attendance Service Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    FACADE LAYER (Public API)                    │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │      MeetingAttendanceService (199 lines)                 │ │
│  │  Implements: MeetingAttendanceServiceInterface            │ │
│  │                                                             │ │
│  │  • handleUserJoin(session, user)                          │ │
│  │  • handleUserLeave(session, user)                         │ │
│  │  • calculateFinalAttendance(session)                      │ │
│  └───────────────┬─────────────────┬───────────────────────────┘ │
│                  │                 │                             │
└──────────────────┼─────────────────┼─────────────────────────────┘
                   │                 │
                   ▼                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                      SERVICE LAYER (Implementation)              │
│                                                                  │
│  ┌──────────────────────────┐  ┌─────────────────────────────┐ │
│  │AttendanceCalculationSvc  │  │ AttendanceNotificationSvc   │ │
│  │      (434 lines)         │  │      (180 lines)            │ │
│  │                          │  │                             │ │
│  │ Responsibilities:        │  │ Responsibilities:           │ │
│  │ • Track join/leave times │  │ • Broadcast updates         │ │
│  │ • Calculate duration     │  │ • Send notifications        │ │
│  │ • Update attendance      │  │ • Parent notifications      │ │
│  │ • Handle reconnections   │  │ • Teacher notifications     │ │
│  │ • Generate statistics    │  │ • Real-time events          │ │
│  └──────────────────────────┘  └─────────────────────────────┘ │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │      UnifiedSessionStatusService                         │  │
│  │  • transitionToOngoing() when first participant joins    │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                      EVENT FLOW                                  │
│                                                                  │
│  LiveKit Webhook → LiveKitWebhookController                     │
│                          │                                       │
│                          ├─→ MeetingAttendanceService           │
│                          │        │                              │
│                          │        ├─→ AttendanceCalculationSvc  │
│                          │        │        │                     │
│  participant_joined ─────┤        │        ├─→ create/update    │
│                          │        │        │   MeetingAttendance │
│                          │        │        │                     │
│                          │        │        ├─→ calculate time   │
│                          │        │        │                     │
│                          │        │        └─→ return attendance│
│                          │        │                              │
│                          │        ├─→ UnifiedSessionStatusSvc   │
│                          │        │        │                     │
│                          │        │        └─→ transitionOngoing │
│                          │        │                              │
│                          │        └─→ AttendanceNotificationSvc │
│                          │                 │                     │
│  participant_left ───────┤                 ├─→ broadcast update │
│                          │                 │                     │
│                          └─────────────────┴─→ notify parent    │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 4. Quran Circle Report Service Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    FACADE LAYER (Public API)                    │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │      QuranCircleReportService (66 lines)                  │ │
│  │                                                             │ │
│  │  • getIndividualCircleReport(circle, dateRange)           │ │
│  │  • getGroupCircleReport(circle)                           │ │
│  │  • getStudentReportInGroupCircle(circle, student)         │ │
│  └───────────────┬───────────────────────────────────────────┘ │
│                  │                                             │
└──────────────────┼─────────────────────────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────────────────────────┐
│                      SERVICE LAYER (Implementation)              │
│                                                                  │
│  ┌──────────────────────────┐  ┌─────────────────────────────┐ │
│  │CircleReportFormatterSvc  │  │  CircleDataFetcherService   │ │
│  │      (193 lines)         │  │      (338 lines)            │ │
│  │                          │  │                             │ │
│  │ Responsibilities:        │  │ Responsibilities:           │ │
│  │ • Format report data     │  │ • Fetch circle sessions     │ │
│  │ • Calculate aggregates   │  │ • Query session reports     │ │
│  │ • Build chart data       │  │ • Calculate attendance      │ │
│  │ • Position formatting    │  │ • Calculate progress        │ │
│  │ • Student summaries      │  │ • Generate trend data       │ │
│  └──────────────┬───────────┘  └─────────────┬───────────────┘ │
│                 │                             │                 │
│                 └─────────────┬───────────────┘                 │
│                               │                                 │
└───────────────────────────────┼─────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│                      DATA SOURCES                                │
│                                                                  │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐  │
│  │ QuranCircle      │  │ QuranSession     │  │ StudentRpt   │  │
│  │ • students       │  │ • scheduled_at   │  │ • attendance │  │
│  │ • teacher        │  │ • status         │  │ • degrees    │  │
│  │ • progress       │  │ • homework       │  │ • notes      │  │
│  └──────────────────┘  └──────────────────┘  └──────────────┘  │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                      REPORT GENERATION FLOW                      │
│                                                                  │
│  Controller                                                      │
│       │                                                          │
│       ├─→ QuranCircleReportService::getIndividualCircleReport() │
│       │            │                                             │
│       │            ├─→ CircleReportFormatterService             │
│       │            │            │                                │
│       │            │            ├─→ CircleDataFetcherService    │
│       │            │            │            │                   │
│       │            │            │            ├─→ fetch data     │
│       │            │            │            ├─→ calc stats     │
│       │            │            │            └─→ return data    │
│       │            │            │                                │
│       │            │            ├─→ format data                 │
│       │            │            ├─→ build charts                │
│       │            │            └─→ return report               │
│       │            │                                             │
│       │            └─→ return formatted report                  │
│       │                                                          │
│       └─→ render view with report                               │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 5. Unified Homework Service Architecture (Strategy Pattern)

```
┌─────────────────────────────────────────────────────────────────┐
│                UnifiedHomeworkService (593 lines)               │
│                    Strategy Pattern Implementation              │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  PUBLIC API                                               │ │
│  │  • getStudentHomework(studentId, academyId, status, type) │ │
│  │  • getStudentHomeworkStatistics(studentId, academyId)     │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  STRATEGY IMPLEMENTATIONS                                 │ │
│  │                                                             │ │
│  │  ┌────────────────────────────────────────────────────┐   │ │
│  │  │ ACADEMIC HOMEWORK STRATEGY (lines 136-291)         │   │ │
│  │  │                                                      │   │ │
│  │  │ Data Source: AcademicHomework model                │   │ │
│  │  │ Submission: HomeworkSubmission (polymorphic)       │   │ │
│  │  │                                                      │   │ │
│  │  │ Methods:                                             │   │ │
│  │  │ • getAcademicHomework()                            │   │ │
│  │  │ • formatAcademicHomework()                         │   │ │
│  │  │ • getOrCreateSubmission()                          │   │ │
│  │  │                                                      │   │ │
│  │  │ Features:                                            │   │ │
│  │  │ • File uploads                                      │   │ │
│  │  │ • Draft saving                                      │   │ │
│  │  │ • Teacher grading                                   │   │ │
│  │  │ • Quality scores                                    │   │ │
│  │  └────────────────────────────────────────────────────┘   │ │
│  │                                                             │ │
│  │  ┌────────────────────────────────────────────────────┐   │ │
│  │  │ INTERACTIVE HOMEWORK STRATEGY (lines 173-361)      │   │ │
│  │  │                                                      │   │ │
│  │  │ Data Source: InteractiveCourseSession              │   │ │
│  │  │ Submission: HomeworkSubmission (polymorphic)       │   │ │
│  │  │                                                      │   │ │
│  │  │ Methods:                                             │   │ │
│  │  │ • getInteractiveHomework()                         │   │ │
│  │  │ • formatInteractiveSessionHomework()               │   │ │
│  │  │ • getOrCreateInteractiveSubmission()               │   │ │
│  │  │                                                      │   │ │
│  │  │ Features:                                            │   │ │
│  │  │ • Session-based homework                            │   │ │
│  │  │ • Auto due dates                                    │   │ │
│  │  │ • Course integration                                │   │ │
│  │  └────────────────────────────────────────────────────┘   │ │
│  │                                                             │ │
│  │  ┌────────────────────────────────────────────────────┐   │ │
│  │  │ QURAN HOMEWORK STRATEGY (lines 204-509)            │   │ │
│  │  │                                                      │   │ │
│  │  │ Data Source: QuranSession                          │   │ │
│  │  │ Submission: None (view-only)                       │   │ │
│  │  │                                                      │   │ │
│  │  │ Methods:                                             │   │ │
│  │  │ • getQuranHomework()                               │   │ │
│  │  │ • formatQuranHomework()                            │   │ │
│  │  │ • buildQuranHomeworkDescription()                  │   │ │
│  │  │                                                      │   │ │
│  │  │ Features:                                            │   │ │
│  │  │ • Memorization tracking                             │   │ │
│  │  │ • Review tracking                                   │   │ │
│  │  │ • Oral evaluation                                   │   │ │
│  │  │ • No submission (evaluated in session)             │   │ │
│  │  └────────────────────────────────────────────────────┘   │ │
│  │                                                             │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  UNIFIED DATA MODEL (output)                             │ │
│  │                                                             │ │
│  │  All strategies return normalized array with:             │ │
│  │  • id, type, submission_id                                │ │
│  │  • title, description, instructions                       │ │
│  │  • due_date, created_at                                   │ │
│  │  • submission_status, submitted_at, is_late               │ │
│  │  • score, max_score, score_percentage, grade_letter       │ │
│  │  • teacher_feedback, graded_at                            │ │
│  │  • view_url, submit_url                                   │ │
│  │  • type-specific fields                                   │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                      POLYMORPHIC SUBMISSION MODEL                │
│                                                                  │
│  HomeworkSubmission                                              │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ • submitable_type (AcademicHomework / InteractiveCourse... │ │
│  │ • submitable_id                                            │ │
│  │ • student_id                                               │ │
│  │ • academy_id                                               │ │
│  │ • homework_type (academic / interactive)                   │ │
│  │ • submission_status (not_started / draft / submitted...)   │ │
│  │ • submission_text                                          │ │
│  │ • submission_files (JSON)                                  │ │
│  │ • score, max_score                                         │ │
│  │ • teacher_feedback                                         │ │
│  │ • submitted_at, graded_at                                  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  Relationships:                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ submitable → AcademicHomework or InteractiveCourseSession  │ │
│  │ student → User                                             │ │
│  │ academy → Academy                                          │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 6. Subscription Service Architecture (Already Well-Designed)

```
┌─────────────────────────────────────────────────────────────────┐
│                    SubscriptionService (538 lines)              │
│                    Facade + Factory Pattern                     │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  PUBLIC API (Unified Interface)                           │ │
│  │                                                             │ │
│  │  • create(type, data)                                     │ │
│  │  • activate(subscription, amount)                         │ │
│  │  • cancel(subscription, reason)                           │ │
│  │  • getStudentSubscriptions(studentId, academyId)          │ │
│  │  • getAcademyStatistics(academyId)                        │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  FACTORY METHOD PATTERN                                   │ │
│  │                                                             │ │
│  │  getModelClass(type) returns:                             │ │
│  │  • TYPE_QURAN    → QuranSubscription::class              │ │
│  │  • TYPE_ACADEMIC → AcademicSubscription::class           │ │
│  │  • TYPE_COURSE   → CourseSubscription::class             │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  TYPE-SPECIFIC OPERATIONS                                 │ │
│  │                                                             │ │
│  │  ┌──────────────────────────────────────────────────────┐ │ │
│  │  │ QURAN SUBSCRIPTIONS                                  │ │ │
│  │  │ • Individual circles (1-on-1)                        │ │ │
│  │  │ • Group circles                                      │ │ │
│  │  │ • Session-based with auto-renewal                    │ │ │
│  │  └──────────────────────────────────────────────────────┘ │ │
│  │                                                             │ │
│  │  ┌──────────────────────────────────────────────────────┐ │ │
│  │  │ ACADEMIC SUBSCRIPTIONS                               │ │ │
│  │  │ • Private lessons                                    │ │ │
│  │  │ • Interactive courses                                │ │ │
│  │  │ • Session-based with auto-renewal                    │ │ │
│  │  └──────────────────────────────────────────────────────┘ │ │
│  │                                                             │ │
│  │  ┌──────────────────────────────────────────────────────┐ │ │
│  │  │ COURSE SUBSCRIPTIONS                                 │ │ │
│  │  │ • Pre-recorded courses                               │ │ │
│  │  │ • Lifetime or timed access                           │ │ │
│  │  │ • One-time payment                                   │ │ │
│  │  └──────────────────────────────────────────────────────┘ │ │
│  │                                                             │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                      MODEL HIERARCHY                             │
│                                                                  │
│                    BaseSubscription (Abstract)                   │
│                            │                                     │
│              ┌─────────────┼─────────────┐                      │
│              │             │             │                      │
│     QuranSubscription  Academic...  CourseSubscription          │
│                                                                  │
│  Shared Fields:                                                  │
│  • subscription_code, status, payment_status                    │
│  • student_id, academy_id, starts_at, ends_at                   │
│  • billing_cycle, auto_renew, final_price                       │
│                                                                  │
│  Shared Methods:                                                 │
│  • activate(), cancel(), renew()                                │
│  • isActive(), isPending(), isExpired()                         │
│  • getSubscriptionSummary()                                     │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## Service Communication Patterns

### 1. Facade Pattern (Most Services)
```
Controller → Facade Service → [Implementation Service 1]
                            → [Implementation Service 2]
                            → [Implementation Service 3]
```

**Benefits**:
- Single entry point for clients
- Backward compatibility
- Simplified API
- Easy to mock in tests

### 2. Strategy Pattern (UnifiedHomeworkService)
```
Client → Context (UnifiedHomeworkService)
              │
              ├─→ Strategy 1 (Academic Homework)
              ├─→ Strategy 2 (Interactive Homework)
              └─→ Strategy 3 (Quran Homework)
```

**Benefits**:
- Eliminates conditional logic
- Easy to add new strategies
- Each strategy independently testable
- Clear separation of algorithms

### 3. Repository Pattern (CircleDataFetcherService)
```
Service → Repository → Database
                    → Query Builder
                    → Eloquent Models
```

**Benefits**:
- Database logic centralized
- Easy to swap data sources
- Consistent query patterns
- Optimized eager loading

### 4. Observer Pattern (AttendanceNotificationService)
```
Event → Notification Service → [Notification Channel 1]
                             → [Notification Channel 2]
                             → [Notification Channel 3]
```

**Benefits**:
- Loose coupling
- Multiple observers
- Real-time updates
- Easy to add channels

---

## Dependency Injection Example

```php
// Before Refactoring: Fat service with mixed responsibilities
class OldSessionStatusService
{
    public function updateStatus($session)
    {
        // 600+ lines of mixed logic
        // - Validation
        // - State transitions
        // - Broadcasting
        // - Batch processing
        // - Scheduling
    }
}

// After Refactoring: Clean separation with DI
class UnifiedSessionStatusService
{
    public function __construct(
        protected SessionTransitionService $transitionService,
        protected SessionSchedulerService $schedulerService
    ) {}

    public function transitionToOngoing(BaseSession $session): bool
    {
        // Simply delegate to the appropriate service
        return $this->transitionService->transitionToOngoing($session);
    }
}

// Usage in Controller
class SessionController
{
    public function __construct(
        protected UnifiedSessionStatusService $statusService
    ) {}

    public function start(BaseSession $session)
    {
        $this->statusService->transitionToOngoing($session);
    }
}

// Testing becomes easy
class SessionControllerTest
{
    public function test_start_transitions_session()
    {
        // Mock the facade service
        $mockService = Mockery::mock(UnifiedSessionStatusService::class);
        $mockService->shouldReceive('transitionToOngoing')->once()->andReturn(true);

        $controller = new SessionController($mockService);
        // Test...
    }
}
```

---

## Performance Optimizations

### Eager Loading in Data Fetchers
```php
// CircleDataFetcherService
public function fetchIndividualCircleData(QuranIndividualCircle $circle)
{
    // Load all related data in one query
    $sessions = $circle->sessions()
        ->with([
            'meetingAttendances',
            'studentReport',
            'quranTeacher.user',
        ])
        ->get();

    // Prevents N+1 queries
}
```

### Caching in Calculator Services
```php
// EarningsCalculatorService
public function calculateEarnings(BaseSession $session)
{
    return Cache::remember(
        "earnings.{$session->id}",
        3600,
        fn() => $this->performCalculation($session)
    );
}
```

### Transaction Safety
```php
// EarningsReportService
public function calculateSessionEarnings(BaseSession $session)
{
    return DB::transaction(function () use ($session) {
        // Lock row to prevent race conditions
        $session = BaseSession::lockForUpdate()->find($session->id);

        // Create earning record
        $earning = $this->calculator->calculateEarnings($session);

        return TeacherEarning::create($earning);
    });
}
```

---

## Testing Strategy

### Unit Tests (Focused Services)
```php
// Test individual service in isolation
class SessionTransitionServiceTest extends TestCase
{
    public function test_transition_to_ongoing_updates_status()
    {
        $session = QuranSession::factory()->create([
            'status' => SessionStatus::READY
        ]);

        $service = new SessionTransitionService();
        $result = $service->transitionToOngoing($session);

        $this->assertTrue($result);
        $this->assertEquals(
            SessionStatus::ONGOING,
            $session->fresh()->status
        );
    }
}
```

### Integration Tests (Facade Services)
```php
// Test service coordination
class UnifiedSessionStatusServiceTest extends TestCase
{
    public function test_status_service_coordinates_transition()
    {
        $session = QuranSession::factory()->create([
            'status' => SessionStatus::READY
        ]);

        $service = app(UnifiedSessionStatusService::class);
        $result = $service->transitionToOngoing($session);

        $this->assertTrue($result);

        // Verify side effects (broadcasting, etc.)
        Event::assertDispatched(SessionStatusChanged::class);
    }
}
```

---

**Document Version**: 1.0
**Last Updated**: 2025-12-29
**Author**: Service Architecture Team
