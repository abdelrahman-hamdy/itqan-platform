@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'database-schema'])

@section('content')

<h2 id="overview">Database Overview</h2>

<p>
    The Itqan platform uses a <strong>single MySQL 8 database</strong> (<code>itqan_platform</code>) with
    <strong>~90 tables</strong> across 8 logical domains. All tenant-scoped tables carry an
    <code>academy_id</code> foreign key. The database has 111+ migration files.
</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Domain</th>
            <th>Key Tables</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><strong>Users & Profiles</strong></td><td>users, quran_teacher_profiles, academic_teacher_profiles, student_profiles, supervisor_profiles, parent_profiles</td><td>8</td></tr>
        <tr><td><strong>Sessions</strong></td><td>quran_sessions, academic_sessions, interactive_course_sessions, quran_session_attendances, academic_session_attendances, interactive_session_attendances, *_reports</td><td>12</td></tr>
        <tr><td><strong>Subscriptions</strong></td><td>quran_subscriptions, academic_subscriptions, course_subscriptions, quran_circle_enrollments, interactive_course_enrollments</td><td>7</td></tr>
        <tr><td><strong>Courses & Content</strong></td><td>interactive_courses, recorded_courses, course_sections, lessons, quran_circles, quran_individual_circles, quran_circle_schedules</td><td>10</td></tr>
        <tr><td><strong>Homework & Quizzes</strong></td><td>quran_session_homework, academic_homeworks, homework_submissions, quizzes, quiz_questions, quiz_assignments, quiz_attempts</td><td>9</td></tr>
        <tr><td><strong>Payments</strong></td><td>payments, payment_webhook_events, payment_audit_logs, teacher_earnings, saved_payment_methods, exchange_rates</td><td>7</td></tr>
        <tr><td><strong>Communication</strong></td><td>wire_conversations, wire_participants, wire_messages, message_reactions (WireChat)</td><td>5</td></tr>
        <tr><td><strong>Settings & Monitoring</strong></td><td>academies, academy_settings, platform_settings, device_tokens, health_*, pulse_*, telescope_entries</td><td>15+</td></tr>
    </tbody>
</table>
</div>

<h2 id="erd-users">ERD A — Users & Academy Structure</h2>

<div class="help-mermaid">
<pre class="mermaid">
erDiagram
    academies {
        uuid id PK
        string subdomain UK
        string name
        string currency
        string timezone
    }
    users {
        uuid id PK
        uuid academy_id FK
        string email UK
        string user_type
        string first_name
        string last_name
    }
    quran_teacher_profiles {
        uuid id PK
        uuid academy_id FK
        uuid user_id FK
        decimal session_price_individual
        json languages
    }
    academic_teacher_profiles {
        uuid id PK
        uuid academy_id FK
        uuid user_id FK
        json subject_ids
        json grade_level_ids
    }
    student_profiles {
        uuid id PK
        uuid academy_id FK
        uuid user_id FK
        string student_code
        string gender
    }
    academic_grade_levels {
        uuid id PK
        uuid academy_id FK
        string name
        integer sort_order
    }

    academies ||--o{ users : "has many"
    academies ||--o{ quran_teacher_profiles : "has many"
    academies ||--o{ academic_teacher_profiles : "has many"
    academies ||--o{ student_profiles : "has many"
    academies ||--o{ academic_grade_levels : "has many"
    users ||--o| quran_teacher_profiles : "has one"
    users ||--o| academic_teacher_profiles : "has one"
    users ||--o| student_profiles : "has one"
</pre>
</div>

<h2 id="erd-sessions">ERD B — Session Inheritance & Attendance</h2>

<div class="help-mermaid">
<pre class="mermaid">
erDiagram
    quran_sessions {
        uuid id PK
        uuid academy_id FK
        uuid quran_teacher_id FK
        uuid quran_subscription_id FK
        string status
        datetime scheduled_at
        string session_type
    }
    academic_sessions {
        uuid id PK
        uuid academy_id FK
        uuid academic_teacher_id FK
        uuid academic_subscription_id FK
        string session_code
        string status
        datetime scheduled_at
    }
    interactive_course_sessions {
        uuid id PK
        uuid academy_id FK
        uuid course_id FK
        integer session_number
        datetime scheduled_at
        string status
    }
    quran_session_attendances {
        uuid id PK
        uuid session_id FK
        uuid student_id FK
        string attendance_status
    }
    academic_session_attendances {
        uuid id PK
        uuid session_id FK
        uuid student_id FK
        string attendance_status
    }
    interactive_session_attendances {
        uuid id PK
        uuid session_id FK
        uuid student_id FK
        string attendance_status
    }

    quran_sessions ||--o{ quran_session_attendances : "tracks"
    academic_sessions ||--o{ academic_session_attendances : "tracks"
    interactive_course_sessions ||--o{ interactive_session_attendances : "tracks"
</pre>
</div>

<h2 id="erd-subscriptions">ERD C — Subscriptions & Session Reports</h2>

<div class="help-mermaid">
<pre class="mermaid">
erDiagram
    quran_subscriptions {
        uuid id PK
        uuid academy_id FK
        uuid student_id FK
        uuid quran_teacher_id FK
        integer total_sessions
        integer sessions_remaining
        string status
    }
    academic_subscriptions {
        uuid id PK
        uuid academy_id FK
        uuid student_id FK
        uuid academic_teacher_id FK
        json weekly_schedule
        string status
    }
    course_subscriptions {
        uuid id PK
        uuid student_id FK
        uuid recorded_course_id FK
        uuid interactive_course_id FK
        string course_type
        string billing_cycle
    }
    student_session_reports {
        uuid id PK
        uuid session_id FK
        uuid student_id FK
        uuid academy_id FK
        string attendance_status
        datetime evaluated_at
        timestamp deleted_at
    }
    academic_session_reports {
        uuid id PK
        uuid academic_session_id FK
        integer rating
        string teacher_feedback
        timestamp deleted_at
    }

    quran_subscriptions ||--o{ quran_sessions : "counts toward"
    academic_subscriptions ||--o{ academic_sessions : "counts toward"
    quran_sessions ||--o{ student_session_reports : "generates"
    academic_sessions ||--o{ academic_session_reports : "generates"
</pre>
</div>

<h2 id="course-erd">Course & Quiz ERD</h2>

<div class="help-mermaid">
<pre class="mermaid">
erDiagram
    interactive_courses {
        uuid id PK
        uuid academy_id FK
        uuid assigned_teacher_id FK
        uuid subject_id FK
        string status
        integer max_students
    }
    recorded_courses {
        uuid id PK
        uuid academy_id FK
        string title
        string status
    }
    course_subscriptions {
        uuid id PK
        uuid student_id FK
        uuid recorded_course_id FK
        uuid interactive_course_id FK
        string course_type
        string billing_cycle
    }
    quizzes {
        uuid id PK
        uuid academy_id FK
        string title
        integer time_limit_minutes
    }
    quiz_assignments {
        uuid id PK
        uuid quiz_id FK
        string assignable_type
        uuid assignable_id
        datetime due_date
    }
    quiz_attempts {
        uuid id PK
        uuid quiz_assignment_id FK
        bigint student_id FK
        integer score
        datetime submitted_at
    }

    interactive_courses ||--o{ course_subscriptions : "subscribed via"
    recorded_courses ||--o{ course_subscriptions : "subscribed via"
    quizzes ||--o{ quiz_assignments : "assigned as"
    quiz_assignments ||--o{ quiz_attempts : "attempted by"
</pre>
</div>

<div class="help-info">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>Design Decision: <code>quiz_attempts.student_id</code></strong><br>
        <code>quiz_attempts.student_id</code> references <code>student_profiles.id</code> (bigint),
        not <code>users.id</code>. This is intentional — quiz attempts track a student's academic profile
        (grade level, learning history), not a raw user account. The <code>course_subscriptions</code> table
        uses <code>users.id</code> because subscriptions are a billing/access concern, not a learning-profile concern.
        Do not refactor — 8+ API/service locations depend on <code>StudentProfile.id</code> being the FK.
    </div>
</div>

<h2 id="payment-erd">Payment ERD</h2>

<div class="help-mermaid">
<pre class="mermaid">
erDiagram
    payments {
        uuid id PK
        uuid user_id FK
        string payable_type
        uuid payable_id
        decimal amount
        string currency
        string gateway
        string status
        string transaction_id
    }
    payment_webhook_events {
        uuid id PK
        uuid payment_id FK
        string gateway
        string event_type
        json payload
        boolean processed
    }
    payment_audit_logs {
        uuid id PK
        uuid payment_id FK
        string action
        json before
        json after
    }
    teacher_earnings {
        uuid id PK
        uuid teacher_id FK
        uuid academy_id FK
        decimal amount
        string currency
        string period
    }
    exchange_rates {
        id id PK
        string from_currency
        string to_currency
        decimal rate
        datetime fetched_at
    }

    payments ||--o{ payment_webhook_events : "receives"
    payments ||--o{ payment_audit_logs : "audited by"
</pre>
</div>

<h2 id="conventions">Naming Conventions</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Convention</th>
            <th>Rule</th>
            <th>Example</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Primary Keys</td><td>UUID v4 (most tables)</td><td><code>id uuid PK</code></td></tr>
        <tr><td>Soft Deletes</td><td>40+ models have <code>deleted_at</code>, including all session report tables</td><td><code>deleted_at timestamp NULL</code></td></tr>
        <tr><td>Tenant Scoping</td><td><code>academy_id</code> on all tenant-scoped tables</td><td><code>academy_id uuid FK → academies.id</code></td></tr>
        <tr><td>Polymorphic</td><td><code>{relation}_type</code> + <code>{relation}_id</code></td><td><code>payable_type, payable_id</code></td></tr>
        <tr><td>JSON Fields</td><td>Stored as JSON, cast in model</td><td><code>subject_ids json, weekly_schedule json</code></td></tr>
        <tr><td>Status Fields</td><td>String column backed by PHP enum</td><td><code>status varchar → SessionStatus enum</code></td></tr>
        <tr><td>Timestamps</td><td>All in UTC, converted on display</td><td><code>scheduled_at, started_at, ended_at</code></td></tr>
        <tr><td>Snapshot Fields</td><td>Subscriptions copy package data at creation</td><td><code>package_name_ar, monthly_price</code></td></tr>
    </tbody>
</table>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
<script>
mermaid.initialize({
    startOnLoad: true,
    theme: 'base',
    themeVariables: {
        darkMode: true,
        fontFamily: 'monospace, Consolas',
        fontSize: '13px',
        background: '#0f172a',
        mainBkg: '#1e293b',
        nodeBorder: '#3b82f6',
        clusterBkg: '#0f172a',
        titleColor: '#e2e8f0',
        edgeLabelBackground: '#1e293b',
        primaryColor: '#1d3461',
        primaryBorderColor: '#3b82f6',
        primaryTextColor: '#e2e8f0',
        secondaryColor: '#1e293b',
        secondaryBorderColor: '#475569',
        secondaryTextColor: '#cbd5e1',
        tertiaryColor: '#334155',
        tertiaryBorderColor: '#475569',
        tertiaryTextColor: '#cbd5e1',
        lineColor: '#64748b',
        textColor: '#e2e8f0',
        nodeTextColor: '#e2e8f0',
        attributeBackgroundColorEven: '#1e293b',
        attributeBackgroundColorOdd: '#0f172a',
    }
});
</script>
@endpush
