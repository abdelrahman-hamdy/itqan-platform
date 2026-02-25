@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'session-system'])

@section('title', 'Session System Architecture')

@section('content')

<div class="prose prose-slate max-w-none">

    {{-- =========================================================
         1. Session Architecture Overview
         ========================================================= --}}
    <h2 id="overview">Session Architecture Overview</h2>
    <p>
        The session system is built on a polymorphic inheritance model with a single abstract base class —
        <code>BaseSession</code> — that holds 37 common fields shared by every session type.
        Three concrete child classes extend it:
    </p>
    <ul>
        <li><strong>QuranSession</strong> — Quran memorization and recitation sessions (individual 1-on-1 or group circles)</li>
        <li><strong>AcademicSession</strong> — Academic tutoring sessions (private lessons)</li>
        <li><strong>InteractiveCourseSession</strong> — Live sessions belonging to an interactive course</li>
    </ul>
    <p>
        All three share attendance, meeting (LiveKit), feedback, cancellation, rescheduling, and tracking fields through
        <code>BaseSession</code>. Child classes add only type-specific fields (e.g. Quran page tracking, academic homework
        content, course FK). Trait composition (<code>CountsTowardsSubscription</code>, <code>HandlesSubscriptionRenewal</code>)
        avoids duplication of subscription-counting logic.
    </p>
    <p>
        The <strong>Constructor Merge Pattern</strong> is used by QuranSession and AcademicSession to merge their own
        <code>$fillable</code> arrays and <code>getCasts()</code> definitions with those of <code>BaseSession</code>
        at runtime — avoiding copy-paste of the ~37 fillable fields and 14 casts.
    </p>

    <div class="help-warning">
        <strong>Never define <code>protected $casts = []</code> in a child session class.</strong>
        Doing so completely overrides the parent's casts and breaks enum/datetime casting on BaseSession fields.
        Always override <code>getCasts(): array</code> and merge with <code>parent::getCasts()</code>.
    </div>

    {{-- =========================================================
         2. BaseSession Common Fields
         ========================================================= --}}
    <h2 id="base-fields">BaseSession Common Fields</h2>
    <p>
        These 37 fields exist on every session table (<code>quran_sessions</code>, <code>academic_sessions</code>,
        <code>interactive_course_sessions</code>):
    </p>

    <div class="help-table-wrapper">
        <table class="help-table">
            <thead>
                <tr>
                    <th>Field Group</th>
                    <th>Fields</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Timing</strong></td>
                    <td>
                        <code>scheduled_at</code>,
                        <code>started_at</code>,
                        <code>ended_at</code>,
                        <code>duration_minutes</code>,
                        <code>actual_duration_minutes</code>
                    </td>
                    <td>
                        Planned start time, actual start/end times, planned vs real duration.
                        <em>InteractiveCourseSession is the exception — it uses <code>scheduled_date</code> + <code>scheduled_time</code> separately.</em>
                    </td>
                </tr>
                <tr>
                    <td><strong>Meeting (LiveKit)</strong></td>
                    <td>
                        <code>meeting_link</code>,
                        <code>meeting_id</code>,
                        <code>meeting_room_name</code>,
                        <code>meeting_started_at</code>,
                        <code>meeting_ended_at</code>
                    </td>
                    <td>
                        LiveKit room identifiers and timestamps. <code>meeting_link</code> is the URL shared with participants;
                        exposed in API as <code>meeting_url</code>.
                    </td>
                </tr>
                <tr>
                    <td><strong>Attendance</strong></td>
                    <td>
                        <code>participants_count</code>,
                        <code>auto_attendance_tracked</code>
                    </td>
                    <td>
                        Count of actual participants; boolean flag set once LiveKit attendance auto-tracking completes.
                    </td>
                </tr>
                <tr>
                    <td><strong>Feedback</strong></td>
                    <td>
                        <code>teacher_feedback</code>,
                        <code>session_notes</code>,
                        <code>supervisor_notes</code>
                    </td>
                    <td>
                        Teacher's written notes, general session notes (exposed as <code>session_notes</code> in API),
                        and internal supervisor-only notes.
                    </td>
                </tr>
                <tr>
                    <td><strong>Status</strong></td>
                    <td><code>status</code></td>
                    <td>
                        Cast to <code>SessionStatus</code> enum.
                        Values: <code>scheduled</code>, <code>live</code>, <code>completed</code>,
                        <code>cancelled</code>, <code>paused</code>.
                    </td>
                </tr>
                <tr>
                    <td><strong>Cancellation</strong></td>
                    <td>
                        <code>cancellation_reason</code>,
                        <code>cancelled_at</code>,
                        <code>cancelled_by</code>
                    </td>
                    <td>
                        Who cancelled, when, and why. <code>cancelled_by</code> stores the User ID of the actor.
                    </td>
                </tr>
                <tr>
                    <td><strong>Rescheduling</strong></td>
                    <td>
                        <code>reschedule_reason</code>,
                        <code>rescheduled_from</code>,
                        <code>rescheduled_to</code>
                    </td>
                    <td>
                        Original and new scheduled times, plus the reason for rescheduling.
                    </td>
                </tr>
                <tr>
                    <td><strong>Tracking</strong></td>
                    <td>
                        <code>subscription_counted</code>,
                        <code>session_code</code>
                    </td>
                    <td>
                        <code>subscription_counted</code> prevents double-counting a session against a subscription quota.
                        <code>session_code</code> is a human-readable code auto-generated per child class (e.g. <code>AS-10-000042</code>).
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- =========================================================
         3. Session Status Lifecycle
         ========================================================= --}}
    <h2 id="status-lifecycle">Session Status Lifecycle</h2>
    <p>
        Status transitions are managed automatically by <code>UpdateSessionStatusesCommand</code> (runs every minute)
        and by manual actions from Filament admin. The flow:
    </p>

    <div class="mermaid-wrapper my-6">
        <div class="mermaid">
stateDiagram-v2
    [*] --> scheduled : Session created

    scheduled --> live : Auto at scheduled_at\n(UpdateSessionStatusesCommand\nevery minute)
    scheduled --> cancelled : Manual cancellation\n(admin / teacher)

    live --> completed : Auto after duration_minutes\nor teacher ends session
    live --> paused : Teacher pauses
    live --> cancelled : Manual cancellation

    paused --> live : Teacher resumes

    completed --> [*]
    cancelled --> [*]
        </div>
    </div>

    <div class="help-note">
        Status updates broadcast a <code>SessionStatusUpdated</code> event via Laravel Reverb so connected
        Livewire components and the mobile app reflect changes in real time without polling.
    </div>

    {{-- =========================================================
         4. Child Session Differences
         ========================================================= --}}
    <h2 id="child-differences">Child Session Differences</h2>

    <div class="help-table-wrapper overflow-x-auto">
        <table class="help-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>QuranSession</th>
                    <th>AcademicSession</th>
                    <th>InteractiveCourseSession</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>DB Table</strong></td>
                    <td><code>quran_sessions</code></td>
                    <td><code>academic_sessions</code></td>
                    <td><code>interactive_course_sessions</code></td>
                </tr>
                <tr>
                    <td><strong>Teacher FK</strong></td>
                    <td><code>quran_teacher_id → users.id</code></td>
                    <td><code>academic_teacher_id → academic_teacher_profiles.id</code></td>
                    <td>Via <code>course.assigned_teacher_id</code></td>
                </tr>
                <tr>
                    <td><strong>Session Types</strong></td>
                    <td><code>individual</code> | <code>circle</code></td>
                    <td><code>individual</code> only</td>
                    <td>N/A (course-based)</td>
                </tr>
                <tr>
                    <td><strong>Auto Code Format</strong></td>
                    <td><code>QS-{academyId}-{seq}</code></td>
                    <td><code>AS-{academyId}-{seq}</code></td>
                    <td><code>ICS-{academyId}-{seq}</code></td>
                </tr>
                <tr>
                    <td><strong><code>academy_id</code> column</strong></td>
                    <td>YES (real column)</td>
                    <td>YES (real column)</td>
                    <td><strong>NO</strong> — virtual accessor only</td>
                </tr>
                <tr>
                    <td><strong>Scheduling fields</strong></td>
                    <td><code>scheduled_at</code> (datetime)</td>
                    <td><code>scheduled_at</code> (datetime)</td>
                    <td><code>scheduled_date</code> (date) + <code>scheduled_time</code> (time) <strong>separately</strong></td>
                </tr>
                <tr>
                    <td><strong>CountsTowardsSubscription</strong></td>
                    <td>YES</td>
                    <td>YES</td>
                    <td>NO</td>
                </tr>
                <tr>
                    <td><strong>Parent Model FK</strong></td>
                    <td><code>individual_circle_id</code> or <code>quran_circle_id</code></td>
                    <td><code>academic_individual_lesson_id</code></td>
                    <td><code>interactive_course_id</code></td>
                </tr>
                <tr>
                    <td><strong>Student FK</strong></td>
                    <td><code>student_id → users.id</code></td>
                    <td><code>student_id → users.id</code></td>
                    <td>Via <code>course.enrollments</code></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- =========================================================
         5. InteractiveCourseSession Special Cases
         ========================================================= --}}
    <h2 id="interactive-course-special">Critical: InteractiveCourseSession Special Cases</h2>

    <div class="help-danger">
        <strong>Two critical differences from other session types:</strong>
        <ol class="mt-2 space-y-1">
            <li>
                <strong>Uses <code>scheduled_date</code> (date) + <code>scheduled_time</code> (time) separately,</strong>
                not a single <code>scheduled_at</code> datetime. Always combine them when you need a full datetime:
                <code>Carbon::parse("{$session->scheduled_date} {$session->scheduled_time}")</code>
            </li>
            <li>
                <strong>Does NOT use the CountsTowardsSubscription trait.</strong>
                Course sessions do not count against a session-based subscription quota —
                access is controlled by CourseSubscription enrollment status.
            </li>
        </ol>
    </div>

    {{-- =========================================================
         6. CountsTowardsSubscription Trait
         ========================================================= --}}
    <h2 id="counts-trait">CountsTowardsSubscription Trait</h2>
    <p>
        Located at <code>app/Models/Traits/CountsTowardsSubscription.php</code>, this trait implements the
        <strong>Template Method Pattern</strong> to avoid duplicating ~110 lines of subscription-counting logic
        across QuranSession and AcademicSession.
    </p>

    <h3>Trait Methods</h3>
    <ul>
        <li>
            <strong><code>countsTowardsSubscription()</code></strong> —
            Abstract-style method that child classes implement to return <code>true</code> or <code>false</code>
            depending on whether this specific session instance should be counted (e.g. status must be completed).
        </li>
        <li>
            <strong><code>updateSubscriptionUsage()</code></strong> —
            The main entry point. Runs inside a <code>DB::transaction()</code> with <code>lockForUpdate()</code>
            on the subscription row to prevent race conditions if two processes run simultaneously.
        </li>
        <li>
            <strong><code>isSubscriptionCounted()</code></strong> —
            Returns the value of the <code>subscription_counted</code> boolean flag. Once set to <code>true</code>,
            the session will never be double-counted even if the job re-runs.
        </li>
    </ul>

    <h3>Race Condition Prevention</h3>
    <pre><code class="language-php">DB::transaction(function () use ($session) {
    // Lock the subscription row for the duration of this transaction
    $subscription = $session->subscription()->lockForUpdate()->first();

    // Double-check the flag inside the lock
    if (!$session->subscription_counted && $subscription) {
        $subscription->increment('sessions_used');
        $session->update(['subscription_counted' => true]);
    }
});</code></pre>

    <div class="help-note">
        Child classes must implement <code>getSubscriptionForCounting()</code> to return the correct subscription
        model instance (e.g. <code>QuranSubscription</code> or <code>AcademicSubscription</code>).
        The trait handles all locking and flag management.
    </div>

    {{-- =========================================================
         7. Attendance Tracking Flow
         ========================================================= --}}
    <h2 id="attendance-flow">Attendance Tracking Flow</h2>

    <div class="mermaid-wrapper my-6">
        <div class="mermaid">
sequenceDiagram
    participant LK as LiveKit Server
    participant WH as LiveKitWebhookHandler
    participant DB as Database
    participant Q  as Queue (Redis)
    participant J  as CalculateSessionAttendance Job
    participant BC as Laravel Reverb (Broadcast)

    LK->>WH: POST /webhooks/livekit\n(participant_joined / participant_left)
    WH->>WH: Validate HMAC signature
    WH->>DB: Create MeetingAttendanceEvent record\n(user_id, event_type, occurred_at)
    WH->>Q: Dispatch AttendanceEventProcessed

    Note over J: Runs every 5 min (prod)\nor triggered by webhook (dev)
    Q->>J: CalculateSessionAttendance
    J->>DB: Aggregate events → calculate\nauto_duration_minutes, attendance_status
    J->>DB: Update session attendance record
    J->>BC: Broadcast AttendanceUpdated event

    BC-->>LK: (mobile / Livewire receives update)

    Note over DB: Manual override possible\nvia Filament admin:\nsets manually_overridden = true\noverrides auto-calculated status
        </div>
    </div>

    {{-- =========================================================
         8. Attendance Model Fields
         ========================================================= --}}
    <h2 id="attendance-fields">Attendance Model Fields</h2>

    <div class="help-table-wrapper">
        <table class="help-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td rowspan="4"><strong>Auto-tracked</strong></td>
                    <td><code>auto_join_time</code></td>
                    <td>datetime</td>
                    <td>First LiveKit <code>participant_joined</code> event timestamp</td>
                </tr>
                <tr>
                    <td><code>auto_leave_time</code></td>
                    <td>datetime</td>
                    <td>Last LiveKit <code>participant_left</code> event timestamp</td>
                </tr>
                <tr>
                    <td><code>auto_duration_minutes</code></td>
                    <td>integer</td>
                    <td>Sum of all connected intervals (handles reconnects)</td>
                </tr>
                <tr>
                    <td><code>auto_tracked</code></td>
                    <td>boolean</td>
                    <td>True once LiveKit data has been processed for this participant</td>
                </tr>
                <tr>
                    <td rowspan="4"><strong>Manual override</strong></td>
                    <td><code>join_time</code></td>
                    <td>datetime</td>
                    <td>Admin-overridden join time (takes precedence over auto)</td>
                </tr>
                <tr>
                    <td><code>leave_time</code></td>
                    <td>datetime</td>
                    <td>Admin-overridden leave time</td>
                </tr>
                <tr>
                    <td><code>manually_overridden</code></td>
                    <td>boolean</td>
                    <td>When true, auto-tracking updates are ignored for this record</td>
                </tr>
                <tr>
                    <td><code>overridden_by</code> / <code>override_reason</code></td>
                    <td>FK / text</td>
                    <td>User ID of the admin who overrode, and their stated reason</td>
                </tr>
                <tr>
                    <td rowspan="3"><strong>Final result</strong></td>
                    <td><code>attendance_status</code></td>
                    <td>enum</td>
                    <td>
                        <code>present</code>, <code>absent</code>, <code>late</code>, <code>left</code>
                    </td>
                </tr>
                <tr>
                    <td><code>participation_score</code></td>
                    <td>integer 0–100</td>
                    <td>Calculated participation quality metric</td>
                </tr>
                <tr>
                    <td><code>notes</code></td>
                    <td>text</td>
                    <td>Free-text notes from teacher or supervisor</td>
                </tr>
            </tbody>
        </table>
    </div>


</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
    mermaid.initialize({
        startOnLoad: true,
        theme: 'base',
        themeVariables: {
            fontFamily: 'monospace',
            primaryColor: '#dbeafe',
            primaryBorderColor: '#3b82f6',
            primaryTextColor: '#1e3a8a',
            lineColor: '#6b7280'
        }
    });
</script>
@endpush
