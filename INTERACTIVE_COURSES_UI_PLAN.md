# Interactive Courses UI - Complete Implementation Plan

## ✅ IMPLEMENTATION STATUS: Phases 1-6 COMPLETED

**Last Updated:** 2025-11-10

### Completed Phases:
- ✅ **Phase 1:** Session Detail Page Infrastructure (COMPLETE)
- ✅ **Phase 2:** Sessions List in Course Detail (COMPLETE)
- ✅ **Phase 3:** Session Actions & API Endpoints (COMPLETE)
- ✅ **Phase 4:** Progress Tracking UI (COMPLETE)
- ⏸️ **Phase 5:** Teacher Session View (DEFERRED - Teacher functionality exists in Filament)
- ✅ **Phase 6:** Bug Fixes & Cleanup (COMPLETE)
- ⏳ **Phase 7:** Testing & Validation (PENDING)

## Executive Summary

The Interactive Courses feature has a **complete backend infrastructure** but is missing critical UI components. This document outlines all missing parts and provides a comprehensive implementation plan to achieve parity with other education sections (Quran Circles and Academic Subscriptions).

**UPDATE:** Core student-facing functionality (Phases 1-4) has been successfully implemented and is ready for testing.

---

## Current State Analysis

### ✅ What EXISTS and Works:

1. **Database Models** - Complete
   - [InteractiveCourse.php](app/Models/InteractiveCourse.php)
   - [InteractiveCourseSession.php](app/Models/InteractiveCourseSession.php)
   - [InteractiveCourseEnrollment.php](app/Models/InteractiveCourseEnrollment.php)
   - [InteractiveCourseHomework.php](app/Models/InteractiveCourseHomework.php)
   - [InteractiveSessionAttendance.php](app/Models/InteractiveSessionAttendance.php)
   - [InteractiveCourseProgress.php](app/Models/InteractiveCourseProgress.php)

2. **Filament Admin Resources** - Complete
   - Course management in Filament
   - Session scheduling in Filament
   - Teacher dashboard

3. **Public Pages** - Working
   - [Public course listing](resources/views/public/interactive-courses/index.blade.php)
   - [Public course detail](resources/views/public/interactive-courses/show.blade.php)
   - [Enrollment flow](resources/views/public/interactive-courses/enroll.blade.php)

4. **Basic Student Views** - Partial
   - [Student course listing](resources/views/student/interactive-courses.blade.php) (283 lines)
   - [Student course detail](resources/views/student/interactive-course-detail.blade.php) (244 lines)

### ❌ What's MISSING:

#### Critical Missing Components:

1. **Individual Session Detail Pages** 🚨 HIGH PRIORITY
   - No view for `student/interactive-sessions/show.blade.php`
   - No route for `student.interactive-sessions.show`
   - No controller method `showInteractiveCourseSession()`

2. **Sessions List in Course Detail**
   - Course detail page doesn't show the list of sessions
   - No integration with `unified-sessions-section` component
   - Missing upcoming/past sessions tabs

3. **Session Action Endpoints**
   - No feedback submission endpoint
   - No homework submission integration
   - No session join tracking

4. **Progress Tracking UI**
   - Progress model exists but no display component
   - No progress calculation service
   - No visual indicators

5. **Teacher Session View**
   - No teacher-facing session detail page
   - No attendance marking interface
   - No homework review section

6. **Component Integration**
   - Not reusing common components from other sections
   - Hardcoded data in views
   - Duplicate code

---

## Architecture Comparison

### How Other Sections Work (Reference Pattern):

#### Quran Circles Structure:
```
routes/web.php
├── /quran-circles                        → List circles
├── /quran-circles/{circleId}             → Circle detail
│   └── Shows sessions using unified-sessions-section component
└── /individual-circles/{circleId}        → Individual circle with session cards

Components Used:
├── circle/quick-actions.blade.php        → Action buttons
├── circle/info-sidebar.blade.php         → Circle metadata
└── sessions/unified-sessions-section     → Tabbed sessions (upcoming/past)
```

#### Academic Subscriptions Structure:
```
routes/web.php
├── /academic-subscriptions               → List subscriptions
├── /academic-subscriptions/{id}          → Subscription detail
│   └── Shows sessions list
└── /academic-sessions/{sessionId}        → 🔑 INDIVIDUAL SESSION DETAIL

session-detail.blade.php includes:
├── Session header component              → Status, timing, meeting info
├── LiveKit interface                     → Video meeting
├── Homework display                      → Assignment and submission
├── Feedback form                         → Post-session rating
└── Info sidebar                          → Session metadata
```

### What Interactive Courses Should Look Like:

```
routes/web.php
├── /my-courses/interactive                       → List enrolled courses
├── /my-courses/interactive/{course}              → Course detail with sessions list
│   ├── Course overview
│   ├── Progress summary
│   └── Sessions tabs (upcoming/past)            → Uses unified-sessions-section
└── /interactive-sessions/{sessionId}            → 🚨 MISSING - Individual session detail
    ├── Session header
    ├── LiveKit meeting interface
    ├── Homework section
    ├── Feedback form
    └── Info sidebar

Components Needed:
├── interactive/session-info-sidebar.blade.php   → Session metadata
├── interactive/session-quick-actions.blade.php  → Context-aware actions
├── interactive/session-card.blade.php           → Session list item
└── interactive/progress-summary.blade.php       → Progress visualization
```

---

## Detailed Implementation Plan

### Phase 1: Session Detail Page (CRITICAL) 🚨

**Priority:** Highest
**Effort:** 6-8 hours
**Dependency:** None

#### 1.1 Add Route & Controller Method

**File:** [routes/web.php](routes/web.php)
```php
Route::middleware(['auth'])->group(function() {
    Route::get('/interactive-sessions/{session}',
        [StudentProfileController::class, 'showInteractiveCourseSession']
    )->name('student.interactive-sessions.show')
      ->middleware('interactive.course.enrolled');
});
```

**File:** [app/Http/Controllers/StudentProfileController.php](app/Http/Controllers/StudentProfileController.php)
```php
public function showInteractiveCourseSession($subdomain, $sessionId)
{
    $session = InteractiveCourseSession::with([
        'course.teacher.user',
        'homework',
        'attendances' => function($q) {
            $q->where('student_id', Auth::id());
        }
    ])->findOrFail($sessionId);

    // Verify enrollment
    $enrollment = InteractiveCourseEnrollment::where([
        'course_id' => $session->course_id,
        'student_id' => Auth::id(),
        'status' => 'active'
    ])->firstOrFail();

    $student = Auth::user()->student;
    $attendance = $session->attendances->where('student_id', $student->id)->first();
    $homeworkSubmission = null;

    if ($session->homework) {
        $homeworkSubmission = $session->homework
            ->submissions()
            ->where('student_id', $student->id)
            ->first();
    }

    return view('student.interactive-sessions.show', compact(
        'session',
        'attendance',
        'homeworkSubmission',
        'student'
    ));
}
```

#### 1.2 Create Session Detail View

**File:** Create `resources/views/student/interactive-sessions/show.blade.php`

**Layout Structure:**
```blade
<x-layouts.student-layout>
    {{-- Breadcrumb Navigation --}}
    <nav class="mb-6">
        <a href="{{ route('student.interactive-courses.index') }}">My Courses</a> /
        <a href="{{ route('student.interactive-courses.show', $session->course) }}">
            {{ $session->course->title }}
        </a> /
        <span>Session {{ $session->session_number }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Session Header Component --}}
            <x-sessions.session-header
                :session="$session"
                :attendance="$attendance"
            />

            {{-- LiveKit Meeting Interface (if active) --}}
            @if($session->status === 'in-progress' || $session->canJoin())
                <x-meetings.livekit-interface
                    :meetingId="$session->meeting_id"
                    :participantName="Auth::user()->name"
                />
            @endif

            {{-- Session Content --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Session Content</h2>
                <div class="prose max-w-none">
                    {!! $session->description !!}
                </div>
            </div>

            {{-- Homework Section --}}
            @if($session->homework)
                <x-sessions.homework-display
                    :homework="$session->homework"
                    :submission="$homeworkSubmission"
                />
            @endif

            {{-- Feedback Form (after completion) --}}
            @if($session->status === 'completed')
                <x-interactive.feedback-form :session="$session" />
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <x-interactive.session-info-sidebar
                :session="$session"
                :attendance="$attendance"
            />

            <x-interactive.session-quick-actions :session="$session" />
        </div>
    </div>
</x-layouts.student-layout>
```

#### 1.3 Create Session Info Sidebar Component

**File:** Create `resources/views/components/interactive/session-info-sidebar.blade.php`

```blade
@props(['session', 'attendance'])

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="font-bold text-lg mb-4">Session Information</h3>

    {{-- Course Info --}}
    <div class="mb-4">
        <img src="{{ $session->course->thumbnail }}"
             alt="{{ $session->course->title }}"
             class="w-full h-32 object-cover rounded mb-2">
        <h4 class="font-semibold">{{ $session->course->title }}</h4>
        <p class="text-sm text-gray-600">
            Session {{ $session->session_number }} of {{ $session->course->total_sessions }}
        </p>
    </div>

    {{-- Progress Bar --}}
    @php
        $progress = ($session->session_number / $session->course->total_sessions) * 100;
    @endphp
    <div class="mb-4">
        <div class="flex justify-between text-sm mb-1">
            <span>Course Progress</span>
            <span>{{ round($progress) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full"
                 style="width: {{ $progress }}%"></div>
        </div>
    </div>

    {{-- Attendance Status --}}
    <div class="mb-4">
        <h4 class="font-semibold text-sm mb-2">Attendance</h4>
        @if($attendance)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm
                {{ $attendance->status === 'present' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                <i class="ri-checkbox-circle-line mr-1"></i>
                {{ ucfirst($attendance->status) }}
            </span>
        @else
            <span class="text-gray-500 text-sm">Not marked</span>
        @endif
    </div>

    {{-- Quick Stats --}}
    <div class="border-t pt-4 space-y-2">
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Teacher</span>
            <span class="font-medium">{{ $session->course->teacher->user->name }}</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Duration</span>
            <span class="font-medium">{{ $session->duration }} min</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Date</span>
            <span class="font-medium">{{ $session->scheduled_at->format('M d, Y') }}</span>
        </div>
    </div>
</div>
```

#### 1.4 Create Session Quick Actions Component

**File:** Create `resources/views/components/interactive/session-quick-actions.blade.php`

```blade
@props(['session'])

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="font-bold text-lg mb-4">Quick Actions</h3>

    <div class="space-y-3">
        {{-- Join Meeting (if active) --}}
        @if($session->status === 'in-progress' || $session->canJoin())
            <a href="{{ $session->meeting_url }}"
               class="btn btn-primary w-full flex items-center justify-center">
                <i class="ri-vidicon-line mr-2"></i>
                Join Live Session
            </a>
        @elseif($session->status === 'scheduled')
            <button disabled
                    class="btn btn-secondary w-full opacity-50 cursor-not-allowed">
                <i class="ri-time-line mr-2"></i>
                Starts {{ $session->scheduled_at->diffForHumans() }}
            </button>
        @endif

        {{-- Chat with Teacher --}}
        <a href="{{ route('chatify', ['id' => $session->course->teacher->user_id]) }}"
           class="btn btn-secondary w-full flex items-center justify-center">
            <i class="ri-message-3-line mr-2"></i>
            Chat with Teacher
        </a>

        {{-- View Course Details --}}
        <a href="{{ route('student.interactive-courses.show', $session->course) }}"
           class="btn btn-secondary w-full flex items-center justify-center">
            <i class="ri-book-open-line mr-2"></i>
            View Course
        </a>

        {{-- Submit Homework --}}
        @if($session->homework && !$session->homework->isSubmitted())
            <a href="#homework-section"
               class="btn btn-accent w-full flex items-center justify-center">
                <i class="ri-file-upload-line mr-2"></i>
                Submit Homework
            </a>
        @endif

        {{-- View Recording (if available) --}}
        @if($session->status === 'completed' && $session->recording_url)
            <a href="{{ $session->recording_url }}"
               target="_blank"
               class="btn btn-secondary w-full flex items-center justify-center">
                <i class="ri-play-circle-line mr-2"></i>
                Watch Recording
            </a>
        @endif
    </div>
</div>
```

---

### Phase 2: Sessions List in Course Detail

**Priority:** High
**Effort:** 4-6 hours
**Dependency:** Phase 1 (for session links)

#### 2.1 Update Controller to Load Sessions

**File:** [app/Http/Controllers/StudentProfileController.php](app/Http/Controllers/StudentProfileController.php)

**Update method:** `showInteractiveCourse()`

```php
public function showInteractiveCourse($subdomain, $course)
{
    $student = Auth::user()->student;

    $course = InteractiveCourse::with([
        'teacher.user',
        'sessions' => function($q) use ($student) {
            $q->with([
                'attendances' => function($q2) use ($student) {
                    $q2->where('student_id', $student->id);
                },
                'homework.submissions' => function($q2) use ($student) {
                    $q2->where('student_id', $student->id);
                }
            ])->orderBy('scheduled_at', 'asc');
        },
        'enrollments' => function($q) use ($student) {
            $q->where('student_id', $student->id);
        },
        'progress' => function($q) use ($student) {
            $q->where('student_id', $student->id);
        }
    ])->findOrFail($course);

    // Separate sessions
    $upcomingSessions = $course->sessions
        ->where('scheduled_at', '>=', now())
        ->values();

    $pastSessions = $course->sessions
        ->where('scheduled_at', '<', now())
        ->sortByDesc('scheduled_at')
        ->values();

    return view('student.interactive-course-detail', compact(
        'course',
        'upcomingSessions',
        'pastSessions',
        'student'
    ));
}
```

#### 2.2 Create Session Card Component

**File:** Create `resources/views/components/interactive/session-card.blade.php`

```blade
@props(['session', 'attendance' => null])

@php
    $statusColors = [
        'scheduled' => 'border-blue-300 bg-blue-50',
        'in-progress' => 'border-green-400 bg-green-50',
        'completed' => 'border-gray-300 bg-gray-50',
        'cancelled' => 'border-red-300 bg-red-50'
    ];
    $cardClass = $statusColors[$session->status] ?? 'border-gray-300';
@endphp

<div class="border-2 {{ $cardClass }} rounded-lg p-4 hover:shadow-md transition">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            {{-- Session Number & Title --}}
            <div class="flex items-center mb-2">
                <span class="text-sm font-semibold text-gray-500 mr-2">
                    Session {{ $session->session_number }}
                </span>
                @if($session->status === 'in-progress')
                    <span class="flex items-center text-xs bg-green-500 text-white px-2 py-1 rounded-full animate-pulse">
                        <span class="w-2 h-2 bg-white rounded-full mr-1"></span>
                        LIVE
                    </span>
                @endif
            </div>

            <h4 class="font-bold text-lg mb-2">{{ $session->title }}</h4>

            {{-- Date & Time --}}
            <div class="flex items-center text-sm text-gray-600 mb-2">
                <i class="ri-calendar-line mr-1"></i>
                {{ $session->scheduled_at->format('M d, Y') }} at {{ $session->scheduled_at->format('h:i A') }}
            </div>

            {{-- Duration --}}
            <div class="flex items-center text-sm text-gray-600 mb-3">
                <i class="ri-time-line mr-1"></i>
                {{ $session->duration }} minutes
            </div>

            {{-- Badges --}}
            <div class="flex flex-wrap gap-2 mb-3">
                {{-- Status Badge --}}
                <span class="inline-flex items-center px-2 py-1 rounded text-xs
                    {{ $session->status === 'completed' ? 'bg-gray-200 text-gray-700' : 'bg-blue-200 text-blue-700' }}">
                    {{ ucfirst($session->status) }}
                </span>

                {{-- Attendance Badge --}}
                @if($attendance)
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs
                        {{ $attendance->status === 'present' ? 'bg-green-200 text-green-700' : 'bg-red-200 text-red-700' }}">
                        <i class="ri-checkbox-circle-line mr-1"></i>
                        {{ ucfirst($attendance->status) }}
                    </span>
                @endif

                {{-- Homework Badge --}}
                @if($session->homework)
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-purple-200 text-purple-700">
                        <i class="ri-file-text-line mr-1"></i>
                        Homework
                    </span>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="ml-4 flex flex-col gap-2">
            @if($session->status === 'in-progress')
                <a href="{{ route('student.interactive-sessions.show', $session->id) }}"
                   class="btn btn-sm btn-primary">
                    <i class="ri-vidicon-line mr-1"></i>
                    Join Now
                </a>
            @else
                <a href="{{ route('student.interactive-sessions.show', $session->id) }}"
                   class="btn btn-sm btn-secondary">
                    View Details
                </a>
            @endif
        </div>
    </div>
</div>
```

#### 2.3 Update Course Detail View

**File:** [resources/views/student/interactive-course-detail.blade.php](resources/views/student/interactive-course-detail.blade.php)

**Add after course overview section:**

```blade
{{-- Sessions Section --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-2xl font-bold mb-6">Course Sessions</h2>

    {{-- Use unified-sessions-section component --}}
    <div x-data="{ activeTab: 'upcoming' }">
        {{-- Tabs --}}
        <div class="flex border-b mb-6">
            <button @click="activeTab = 'upcoming'"
                    :class="activeTab === 'upcoming' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                    class="px-4 py-2 border-b-2 font-medium transition">
                Upcoming ({{ $upcomingSessions->count() }})
            </button>
            <button @click="activeTab = 'past'"
                    :class="activeTab === 'past' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                    class="px-4 py-2 border-b-2 font-medium transition">
                Past ({{ $pastSessions->count() }})
            </button>
        </div>

        {{-- Upcoming Sessions Tab --}}
        <div x-show="activeTab === 'upcoming'" class="space-y-4">
            @forelse($upcomingSessions as $session)
                @php
                    $attendance = $session->attendances->where('student_id', $student->id)->first();
                @endphp
                <x-interactive.session-card :session="$session" :attendance="$attendance" />
            @empty
                <p class="text-gray-500 text-center py-8">No upcoming sessions scheduled.</p>
            @endforelse
        </div>

        {{-- Past Sessions Tab --}}
        <div x-show="activeTab === 'past'" class="space-y-4">
            @forelse($pastSessions as $session)
                @php
                    $attendance = $session->attendances->where('student_id', $student->id)->first();
                @endphp
                <x-interactive.session-card :session="$session" :attendance="$attendance" />
            @empty
                <p class="text-gray-500 text-center py-8">No past sessions yet.</p>
            @endforelse
        </div>
    </div>
</div>
```

---

### Phase 3: Session Actions & API Endpoints

**Priority:** High
**Effort:** 3-4 hours
**Dependency:** Phase 1

#### 3.1 Student Feedback Submission

**Add route:**
```php
Route::post('/interactive-sessions/{session}/feedback',
    [StudentProfileController::class, 'addInteractiveSessionFeedback']
)->name('student.interactive-sessions.feedback');
```

**Add controller method:**
```php
public function addInteractiveSessionFeedback(Request $request, $subdomain, $sessionId)
{
    $validated = $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'feedback_text' => 'nullable|string|max:1000'
    ]);

    $session = InteractiveCourseSession::findOrFail($sessionId);

    // Verify enrollment and session completion
    if ($session->status !== 'completed') {
        return back()->with('error', 'Cannot submit feedback for incomplete session');
    }

    $student = Auth::user()->student;

    // Create or update feedback
    $feedback = InteractiveSessionFeedback::updateOrCreate(
        [
            'session_id' => $sessionId,
            'student_id' => $student->id
        ],
        $validated
    );

    return back()->with('success', 'Feedback submitted successfully');
}
```

#### 3.2 Homework Submission

**Add route:**
```php
Route::post('/interactive-sessions/{session}/homework',
    [StudentProfileController::class, 'submitInteractiveCourseHomework']
)->name('student.interactive-sessions.homework');
```

**Add controller method:**
```php
public function submitInteractiveCourseHomework(Request $request, $subdomain, $sessionId)
{
    $validated = $request->validate([
        'homework_id' => 'required|exists:interactive_course_homework,id',
        'answer_text' => 'nullable|string',
        'files.*' => 'nullable|file|max:10240' // 10MB max
    ]);

    $homework = InteractiveCourseHomework::findOrFail($validated['homework_id']);
    $student = Auth::user()->student;

    // Use existing HomeworkService
    app(HomeworkService::class)->submitHomework(
        $homework,
        $student,
        $validated
    );

    return back()->with('success', 'Homework submitted successfully');
}
```

---

### Phase 4: Progress Tracking UI

**Priority:** Medium
**Effort:** 4-5 hours

#### 4.1 Create Progress Service

**File:** Create `app/Services/InteractiveCourseProgressService.php`

```php
<?php

namespace App\Services;

use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseProgress;
use Illuminate\Support\Facades\Cache;

class InteractiveCourseProgressService
{
    public function calculateCourseProgress(int $courseId, int $studentId): array
    {
        $cacheKey = "interactive_progress_{$courseId}_{$studentId}";

        return Cache::remember($cacheKey, 3600, function() use ($courseId, $studentId) {
            $course = InteractiveCourse::with([
                'sessions.attendances' => function($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                },
                'sessions.homework.submissions' => function($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                }
            ])->findOrFail($courseId);

            $totalSessions = $course->sessions->count();
            $completedSessions = $course->sessions
                ->where('status', 'completed')
                ->count();

            $attendedSessions = $course->sessions
                ->filter(function($session) {
                    return $session->attendances->where('status', 'present')->count() > 0;
                })
                ->count();

            $totalHomework = $course->sessions->sum(function($session) {
                return $session->homework ? 1 : 0;
            });

            $submittedHomework = $course->sessions->sum(function($session) {
                return $session->homework && $session->homework->submissions->count() > 0 ? 1 : 0;
            });

            $averageGrade = $this->calculateAverageGrade($course, $studentId);

            return [
                'completion_percentage' => $totalSessions > 0
                    ? round(($completedSessions / $totalSessions) * 100)
                    : 0,
                'sessions_attended' => $attendedSessions,
                'total_sessions' => $totalSessions,
                'attendance_rate' => $totalSessions > 0
                    ? round(($attendedSessions / $totalSessions) * 100)
                    : 0,
                'homework_submitted' => $submittedHomework,
                'total_homework' => $totalHomework,
                'homework_completion_rate' => $totalHomework > 0
                    ? round(($submittedHomework / $totalHomework) * 100)
                    : 0,
                'average_grade' => $averageGrade
            ];
        });
    }

    protected function calculateAverageGrade($course, $studentId)
    {
        $grades = [];

        foreach ($course->sessions as $session) {
            if ($session->homework) {
                $submission = $session->homework->submissions
                    ->where('student_id', $studentId)
                    ->first();

                if ($submission && $submission->grade !== null) {
                    $grades[] = $submission->grade;
                }
            }
        }

        return count($grades) > 0 ? round(array_sum($grades) / count($grades), 1) : null;
    }

    public function updateProgress(int $courseId, int $studentId): void
    {
        Cache::forget("interactive_progress_{$courseId}_{$studentId}");

        $progress = $this->calculateCourseProgress($courseId, $studentId);

        InteractiveCourseProgress::updateOrCreate(
            [
                'course_id' => $courseId,
                'student_id' => $studentId
            ],
            [
                'completion_percentage' => $progress['completion_percentage'],
                'sessions_attended' => $progress['sessions_attended'],
                'homework_submitted' => $progress['homework_submitted'],
                'average_grade' => $progress['average_grade']
            ]
        );
    }
}
```

#### 4.2 Create Progress Component

**File:** Create `resources/views/components/interactive/progress-summary.blade.php`

```blade
@props(['courseId', 'studentId'])

@php
    $progressService = app(\App\Services\InteractiveCourseProgressService::class);
    $progress = $progressService->calculateCourseProgress($courseId, $studentId);
@endphp

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="font-bold text-lg mb-4">Your Progress</h3>

    {{-- Overall Completion --}}
    <div class="mb-6">
        <div class="flex justify-between text-sm mb-2">
            <span class="font-medium">Overall Completion</span>
            <span class="font-bold text-blue-600">{{ $progress['completion_percentage'] }}%</span>
        </div>
        <div class="relative w-24 h-24 mx-auto">
            <svg class="transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                <circle cx="50" cy="50" r="45" fill="none"
                        stroke="{{ $progress['completion_percentage'] >= 80 ? '#10b981' : ($progress['completion_percentage'] >= 50 ? '#f59e0b' : '#ef4444') }}"
                        stroke-width="10"
                        stroke-dasharray="{{ 2 * 3.14159 * 45 }}"
                        stroke-dashoffset="{{ 2 * 3.14159 * 45 * (1 - $progress['completion_percentage'] / 100) }}"
                        stroke-linecap="round"/>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-2xl font-bold">{{ $progress['completion_percentage'] }}%</span>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="space-y-3">
        {{-- Attendance --}}
        <div class="flex items-center justify-between p-3 bg-blue-50 rounded">
            <div class="flex items-center">
                <i class="ri-user-follow-line text-blue-600 text-xl mr-2"></i>
                <span class="text-sm">Attendance</span>
            </div>
            <span class="font-bold text-blue-600">
                {{ $progress['sessions_attended'] }}/{{ $progress['total_sessions'] }}
            </span>
        </div>

        {{-- Homework --}}
        <div class="flex items-center justify-between p-3 bg-purple-50 rounded">
            <div class="flex items-center">
                <i class="ri-file-text-line text-purple-600 text-xl mr-2"></i>
                <span class="text-sm">Homework</span>
            </div>
            <span class="font-bold text-purple-600">
                {{ $progress['homework_submitted'] }}/{{ $progress['total_homework'] }}
            </span>
        </div>

        {{-- Average Grade --}}
        @if($progress['average_grade'] !== null)
            <div class="flex items-center justify-between p-3 bg-green-50 rounded">
                <div class="flex items-center">
                    <i class="ri-bar-chart-line text-green-600 text-xl mr-2"></i>
                    <span class="text-sm">Avg. Grade</span>
                </div>
                <span class="font-bold text-green-600">
                    {{ $progress['average_grade'] }}/100
                </span>
            </div>
        @endif
    </div>
</div>
```

#### 4.3 Add to Course Detail

**In** [resources/views/student/interactive-course-detail.blade.php](resources/views/student/interactive-course-detail.blade.php)

Add to sidebar:
```blade
<x-interactive.progress-summary
    :courseId="$course->id"
    :studentId="$student->id"
/>
```

---

### Phase 5: Teacher Session View

**Priority:** Medium
**Effort:** 6-8 hours

*(Similar structure to student view but with management controls)*

---

### Phase 6: Bug Fixes & Cleanup

**Priority:** High
**Effort:** 2-3 hours

#### 6.1 Remove Hardcoded Data

**Files to audit:**
- [student/interactive-courses.blade.php](resources/views/student/interactive-courses.blade.php)
- [student/interactive-course-detail.blade.php](resources/views/student/interactive-course-detail.blade.php)

**Search for:** Static arrays, hardcoded course data, dummy sessions

**Replace with:** Database queries, proper Eloquent relationships

#### 6.2 Route Cleanup

**Current routes:**
```php
GET /interactive-courses  // Ambiguous - public or student?
```

**Proposed structure:**
```php
// Public
GET /courses/interactive → PublicInteractiveCourseController@index

// Student
GET /my-courses/interactive → StudentProfileController@interactiveCourses
GET /my-courses/interactive/{course} → StudentProfileController@showInteractiveCourse
GET /interactive-sessions/{session} → StudentProfileController@showInteractiveCourseSession
```

#### 6.3 Navigation Updates

**Files:**
- [components/navigation/student-nav.blade.php](resources/views/components/navigation/student-nav.blade.php)
- [components/sidebar/student-sidebar.blade.php](resources/views/components/sidebar/student-sidebar.blade.php)

**Ensure links point to:**
```
/my-courses/interactive (not /interactive-courses)
```

---

## Implementation Checklist

### Phase 1: Session Detail Page ✅
- [ ] Create route: `student.interactive-sessions.show`
- [ ] Add controller method: `showInteractiveCourseSession()`
- [ ] Create view: `student/interactive-sessions/show.blade.php`
- [ ] Create component: `interactive/session-info-sidebar.blade.php`
- [ ] Create component: `interactive/session-quick-actions.blade.php`
- [ ] Integrate LiveKit meeting interface
- [ ] Integrate homework display component
- [ ] Add feedback form
- [ ] Test session access and enrollment verification

### Phase 2: Sessions List ✅
- [ ] Update `showInteractiveCourse()` to load sessions
- [ ] Create component: `interactive/session-card.blade.php`
- [ ] Add sessions section to course detail view
- [ ] Implement upcoming/past tabs
- [ ] Add "View Session" links
- [ ] Test session listing and navigation

### Phase 3: Session Actions ✅
- [ ] Create feedback submission endpoint
- [ ] Create homework submission endpoint
- [ ] Integrate with existing HomeworkService
- [ ] Add feedback form to session view
- [ ] Test form submissions and validations

### Phase 4: Progress Tracking ✅
- [ ] Create `InteractiveCourseProgressService.php`
- [ ] Implement progress calculation methods
- [ ] Create component: `interactive/progress-summary.blade.php`
- [ ] Add progress to course detail sidebar
- [ ] Add caching for performance
- [ ] Test progress calculations

### Phase 5: Teacher View ✅
- [ ] Create teacher session detail page
- [ ] Add attendance marking interface
- [ ] Add homework review section
- [ ] Add session management controls
- [ ] Test teacher workflows

### Phase 6: Bug Fixes ✅
- [x] Remove all hardcoded data (student profile, interactive courses section)
- [x] Clean up routes (renamed authenticated route to avoid conflicts)
- [x] Update navigation links (all route references updated)
- [x] Fix breadcrumbs (session navigation working correctly)
- [x] Fix enrollment visibility logic (removed deprecated status field checks)
- [x] Fix course listing showing no courses (removed restrictive status filter)

### Phase 7: Testing ✅
- [ ] Test complete student flow (enrollment to graduation)
- [ ] Test complete teacher flow (create to grade)
- [ ] Cross-browser testing
- [ ] Mobile responsive testing
- [ ] Performance testing (query optimization)
- [ ] Security testing (authorization checks)

---

## Key Components to Reuse

### From Existing Codebase:

1. **sessions/session-header.blade.php** ✅
   Use for session status, timing, meeting info

2. **meetings/livekit-interface** ✅
   Use for video meetings (already integrated)

3. **sessions/homework-display.blade.php** ✅
   Use for homework sections (may need minor tweaks)

4. **sessions/unified-sessions-section.blade.php** ✅
   Use for tabbed session listing (upcoming/past)

### New Components Needed:

1. **interactive/session-info-sidebar.blade.php** 🆕
   Session and course metadata display

2. **interactive/session-quick-actions.blade.php** 🆕
   Context-aware action buttons

3. **interactive/session-card.blade.php** 🆕
   Session list item with status

4. **interactive/progress-summary.blade.php** 🆕
   Visual progress tracking

---

## Expected Outcomes

After completing this plan:

✅ Students can view all enrolled interactive courses
✅ Students can see sessions list in course detail
✅ Students can view individual session details
✅ Students can join live sessions via LiveKit
✅ Students can submit homework
✅ Students can provide feedback
✅ Students can track their progress
✅ Teachers can manage sessions (via Filament)
✅ Teachers can view session details
✅ Teachers can mark attendance
✅ Teachers can grade homework
✅ UI consistency across all education sections
✅ No hardcoded data
✅ Component reuse >70%
✅ Mobile responsive design

---

## Estimated Timeline

| Phase | Effort | Priority |
|-------|--------|----------|
| Phase 1: Session Detail Page | 6-8 hours | Critical 🚨 |
| Phase 2: Sessions List | 4-6 hours | High |
| Phase 3: Session Actions | 3-4 hours | High |
| Phase 4: Progress Tracking | 4-5 hours | Medium |
| Phase 5: Teacher View | 6-8 hours | Medium |
| Phase 6: Bug Fixes | 2-3 hours | High |
| Phase 7: Testing | 4-6 hours | High |
| **Total** | **29-40 hours** | **~1 week** |

---

## Next Steps

1. **Review this plan** and confirm approach
2. **Start with Phase 1** (Session Detail Page) - highest priority
3. **Test incrementally** after each phase
4. **Iterate based on feedback**

Would you like me to start implementing Phase 1 now?
