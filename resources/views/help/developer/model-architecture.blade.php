@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'model-architecture'])

@section('content')

<h2 id="overview">Model Layer Overview</h2>

<p>
    The platform has <strong>105 Eloquent model files</strong> organized around 3 core abstract hierarchies.
    All share a common set of patterns for multi-tenancy, inheritance, and type safety.
</p>

<div class="help-mermaid">
<pre class="mermaid">
classDiagram
    class BaseSession {
        #37 common fields
        +HasAttendanceTracking
        +HasMeetingData
        +HasMeetings
        +HasSessionFeedback
        +HasSessionScheduling
        +HasSessionStatus
    }
    class QuranSession {
        +quran_teacher_id
        +session_type individual|circle
        +CountsTowardsSubscription
    }
    class AcademicSession {
        +academic_teacher_id
        +session_code AS-XX-XXXXXX
        +CountsTowardsSubscription
    }
    class InteractiveCourseSession {
        +course_id
        +session_number
        +HasRecording
        -NO academy_id column
    }
    class BaseSubscription {
        #Common billing fields
        +total_sessions
        +sessions_remaining
        +billing_cycle
    }
    class QuranSubscription {
        +quran_teacher_id
        +subscription_type
        +HandlesSubscriptionRenewal
    }
    class AcademicSubscription {
        +subject_id
        +weekly_schedule JSON
        +HandlesSubscriptionRenewal
    }
    class CourseSubscription {
        +recorded_course_id OR
        +interactive_course_id
        +BillingCycle LIFETIME
    }

    BaseSession <|-- QuranSession
    BaseSession <|-- AcademicSession
    BaseSession <|-- InteractiveCourseSession
    BaseSubscription <|-- QuranSubscription
    BaseSubscription <|-- AcademicSubscription
    BaseSubscription <|-- CourseSubscription
</pre>
</div>

<h2 id="constructor-merge">Constructor Merge Pattern — Critical</h2>

<p>
    Child session/subscription classes use a <strong>constructor merge pattern</strong> to inherit
    the parent's 37 <code>$fillable</code> fields without copy-pasting them, while still defining
    their own child-specific fields.
</p>

<div class="help-danger">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>NEVER define <code>protected $casts = []</code> in child classes.</strong>
        Doing so completely <em>overrides</em> the parent's casts, breaking enum casting for
        <code>SessionStatus</code>, <code>AttendanceStatus</code>, and datetime fields.
        Always override the <code>getCasts()</code> <em>method</em> instead.
    </div>
</div>

<pre><code class="language-php">// ✅ CORRECT PATTERN — used by QuranSession and AcademicSession

class QuranSession extends BaseSession
{
    // Child-specific fillable fields only (parent's 37 fields merged in constructor)
    protected $fillable = [
        'quran_teacher_id',
        'session_type',
        'student_id',
        // ... other quran-specific fields
    ];

    public function __construct(array $attributes = [])
    {
        // Merge parent's $fillable BEFORE calling parent constructor
        $this->fillable = array_merge(parent::$fillable ?? [], $this->fillable);
        parent::__construct($attributes);
    }

    // ✅ Override getCasts() method — NEVER use protected $casts property
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'scheduled_date'  => 'date',
            'some_int_field'  => 'integer',
        ]);
    }
}

// ❌ WRONG — this OVERWRITES parent casts entirely
class BadSession extends BaseSession
{
    protected $casts = [
        'my_field' => 'integer',
        // Parent's SessionStatus, datetime casts are now GONE
    ];
}
</code></pre>

<h2 id="global-scopes">Global Scopes — Multi-Tenancy</h2>

<div class="help-mermaid">
<pre class="mermaid">
sequenceDiagram
    participant Dev as Developer Code
    participant Model as QuranSession::query()
    participant Trait as ScopedToAcademy trait
    participant Scope as AcademyScopedGlobalScope
    participant DB as MySQL

    Dev->>Model: QuranSession::where('status', 'scheduled')->get()
    Model->>Trait: Boot global scope on boot()
    Trait->>Scope: apply() called
    Scope->>DB: SELECT * FROM quran_sessions WHERE academy_id = 5 AND status = 'scheduled'
    DB-->>Dev: Results (only current tenant's data)
</pre>
</div>

<p>There are 3 tenant-scoping traits:</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr><th>Trait</th><th>Used By</th><th>How It Scopes</th></tr>
    </thead>
    <tbody>
        <tr>
            <td><code>ScopedToAcademy</code></td>
            <td>Most tenant models</td>
            <td>Filters by <code>academy_id</code> column directly</td>
        </tr>
        <tr>
            <td><code>ScopedToAcademyForWeb</code></td>
            <td>Session models (web portal)</td>
            <td>Same as above + prevents blank result set on missing context</td>
        </tr>
        <tr>
            <td><code>ScopedToAcademyViaRelationship</code></td>
            <td><code>StudentProfile</code></td>
            <td>Scopes through <code>user.academy_id</code> (no direct column)</td>
        </tr>
    </tbody>
</table>
</div>

<h2 id="traits">Model Traits — Complete List</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr><th>Trait</th><th>Purpose</th></tr>
    </thead>
    <tbody>
        <tr><td><code>CountsTowardsSubscription</code></td><td>Template Method Pattern: locks subscription row, increments counter, sets <code>subscription_counted</code> flag. Prevents double-counting with <code>lockForUpdate()</code>.</td></tr>
        <tr><td><code>HandlesSubscriptionRenewal</code></td><td>Auto-renewal logic — charge stored card, 3-attempt retry, grace period, suspend.</td></tr>
        <tr><td><code>PreventsDuplicatePendingSubscriptions</code></td><td>Throws exception if student already has a pending subscription of the same type.</td></tr>
        <tr><td><code>HasAttendanceTracking</code></td><td>Attendance record management, duration calculation from join/leave times.</td></tr>
        <tr><td><code>HasMeetingData</code></td><td>Meeting link generation, LiveKit room name, token helpers.</td></tr>
        <tr><td><code>HasMeetings</code></td><td>Polymorphic relationship to session meeting records.</td></tr>
        <tr><td><code>HasSessionFeedback</code></td><td>Teacher feedback and session notes functionality.</td></tr>
        <tr><td><code>HasSessionScheduling</code></td><td>Scheduling, rescheduling, timing validation (can join window, etc.).</td></tr>
        <tr><td><code>HasSessionStatus</code></td><td>Status transition management and validation (scheduled → live → completed).</td></tr>
        <tr><td><code>HasRecording</code></td><td>Session recording functionality (start/stop/fetch via LiveKit Egress API).</td></tr>
        <tr><td><code>HasChatIntegration</code></td><td>WireChat profile linking for users.</td></tr>
        <tr><td><code>HasNotificationPreferences</code></td><td>Per-user notification opt-in/out management.</td></tr>
        <tr><td><code>HasPermissions</code></td><td>Fine-grained permission checking for chat and resource access.</td></tr>
        <tr><td><code>HasProfiles</code></td><td>Polymorphic profile relationships (teacher / student / parent / supervisor).</td></tr>
        <tr><td><code>HasRelationships</code></td><td>Generic relationship loading helpers.</td></tr>
        <tr><td><code>HasReviews</code></td><td>Review and star-rating functionality for teachers and courses.</td></tr>
        <tr><td><code>HasRoles</code></td><td>Role-based access control helpers (isAdmin, isTeacher, etc.).</td></tr>
        <tr><td><code>HasTenantContext</code></td><td>Academy context awareness — exposes current tenant on model.</td></tr>
        <tr><td><code>ScopedToAcademy</code></td><td>Global scope for <code>academy_id</code> filtering.</td></tr>
        <tr><td><code>ScopedToAcademyForWeb</code></td><td>Web-specific academy scoping (prevents blank result on missing context).</td></tr>
        <tr><td><code>ScopedToAcademyViaRelationship</code></td><td>Academy scoping via <code>user → academy</code> relationship (StudentProfile).</td></tr>
    </tbody>
</table>
</div>

<h2 id="policies">Authorization Policies</h2>

<p>20 policy classes in <code>app/Policies/</code>. All policies check both role and tenant context.</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr><th>Policy</th><th>Governs</th></tr>
    </thead>
    <tbody>
        <tr><td><code>AcademicIndividualLessonPolicy</code></td><td>CRUD on academic lesson records</td></tr>
        <tr><td><code>AcademyPolicy</code></td><td>Academy settings and configuration</td></tr>
        <tr><td><code>CertificatePolicy</code></td><td>View, verify, download certificates</td></tr>
        <tr><td><code>HomeworkPolicy</code></td><td>Homework assignment and submission</td></tr>
        <tr><td><code>InteractiveCoursePolicy</code></td><td>Course enrollment and management</td></tr>
        <tr><td><code>InteractiveCourseSessionPolicy</code></td><td>Session join and recording</td></tr>
        <tr><td><code>LessonPolicy</code></td><td>Lesson view and completion tracking</td></tr>
        <tr><td><code>MeetingAttendancePolicy</code></td><td>Attendance tracking and override</td></tr>
        <tr><td><code>ParentPolicy</code></td><td>Parent-child linking</td></tr>
        <tr><td><code>ParentProfilePolicy</code></td><td>Parent profile CRUD</td></tr>
        <tr><td><code>PaymentPolicy</code></td><td>Payment view, refund, retry</td></tr>
        <tr><td><code>QuizAssignmentPolicy</code></td><td>Quiz attempt and submission</td></tr>
        <tr><td><code>QuizAttemptPolicy</code></td><td>Attempt view and retake</td></tr>
        <tr><td><code>QuranCirclePolicy</code></td><td>Circle enrollment and management</td></tr>
        <tr><td><code>QuranIndividualCirclePolicy</code></td><td>Individual circle management</td></tr>
        <tr><td><code>RecordingPolicy</code></td><td>Recording view and download</td></tr>
        <tr><td><code>SessionPolicy</code></td><td>Session join, view, feedback (polymorphic)</td></tr>
        <tr><td><code>StudentProfilePolicy</code></td><td>Student profile view and update</td></tr>
        <tr><td><code>SubscriptionPolicy</code></td><td>Purchase, renew, cancel subscriptions</td></tr>
        <tr><td><code>TeacherProfilePolicy</code></td><td>Teacher activation and suspension</td></tr>
    </tbody>
</table>
</div>

<h2 id="enums">Key Enums (55+)</h2>

<pre><code class="language-php">// Session & Attendance
SessionStatus::SCHEDULED | LIVE | COMPLETED | CANCELLED | PAUSED
AttendanceStatus::PRESENT | ABSENT | LATE | LEFT  // DB stores 'leaved' for LEFT!
SessionSubscriptionStatus::PENDING | ACTIVE | COMPLETED | CANCELLED | EXPIRED

// Payments
PaymentStatus::PENDING | COMPLETED | FAILED | REFUNDED
SubscriptionPaymentStatus::UNPAID | PAID | PARTIAL | REFUNDED | PROCESSING
BillingCycle::MONTHLY | QUARTERLY | YEARLY | LIFETIME
PaymentMethod::CARD | BANK_TRANSFER | WALLET | CASH

// Users
UserType::SUPER_ADMIN | ADMIN | SUPERVISOR | TEACHER | STUDENT | PARENT

// Courses
CourseType::RECORDED | INTERACTIVE
EnrollmentType::FULL_PRICE | DISCOUNTED | SPONSORED
EnrollmentStatus::ACTIVE | COMPLETED | SUSPENDED | DROPPED
</code></pre>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
<script>
mermaid.initialize({
    startOnLoad: true,
    theme: 'base',
    themeVariables: {
        fontFamily: 'monospace, Consolas',
        fontSize: '13px',
        primaryColor: '#dbeafe',
        primaryBorderColor: '#3b82f6',
        primaryTextColor: '#1e3a8a',
        lineColor: '#6b7280',
        secondaryColor: '#f0fdf4',
        tertiaryColor: '#fef9c3',
    }
});
</script>
@endpush
