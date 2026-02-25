@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'api-architecture'])

@section('content')

<h2 id="overview">API Overview</h2>

<p>
    The platform exposes a <strong>RESTful API at <code>/api/v1/</code></strong> for the Flutter mobile app
    (iOS + Android). Authentication uses Laravel Sanctum tokens. All endpoints return JSON.
</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Category</th><th>Base Path</th><th>Auth</th></tr></thead>
    <tbody>
        <tr><td>Authentication</td><td><code>/api/v1/auth/</code></td><td>None (login/register)</td></tr>
        <tr><td>Student endpoints</td><td><code>/api/v1/student/</code></td><td>Sanctum + student role</td></tr>
        <tr><td>Teacher endpoints</td><td><code>/api/v1/teacher/</code></td><td>Sanctum + teacher role</td></tr>
        <tr><td>Chat endpoints</td><td><code>/api/v1/chat/</code></td><td>Sanctum (any role)</td></tr>
        <tr><td>Profile & settings</td><td><code>/api/v1/profile/</code></td><td>Sanctum (any role)</td></tr>
        <tr><td>Common lookup data</td><td><code>/api/v1/lookup/</code></td><td>Sanctum or none</td></tr>
    </tbody>
</table>
</div>

<h2 id="auth-flow">Authentication Flow</h2>

<div class="help-mermaid">
<pre class="mermaid">
sequenceDiagram
    participant App as Flutter App
    participant API as Laravel API
    participant DB as MySQL

    App->>API: POST /api/v1/auth/login { email, password, device_name }
    API->>DB: Verify credentials
    DB-->>API: User record
    API->>DB: Create Sanctum token for device
    API-->>App: { token: "...", user: {...} }

    Note over App: Subsequent requests
    App->>API: GET /api/v1/student/sessions<br/>Authorization: Bearer {token}
    API->>API: Verify token via Sanctum
    API->>API: Apply tenant global scope
    API-->>App: JSON response
</pre>
</div>

<h2 id="response-format">Standard Response Format</h2>

<pre><code class="language-json">// Success response
{
    "success": true,
    "data": { ... },
    "message": "Operation successful",
    "meta": { "pagination": { "current_page": 1, "total": 50 } }
}

// Error response
{
    "success": false,
    "message": "Validation failed",
    "errors": { "field": ["Error message"] }
}
</code></pre>

<h2 id="key-endpoints">Key Mobile API Endpoints</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Method</th><th>Endpoint</th><th>Purpose</th></tr></thead>
    <tbody>
        <tr><td>GET</td><td><code>/api/v1/student/subscriptions</code></td><td>Student's active subscriptions</td></tr>
        <tr><td>GET</td><td><code>/api/v1/student/subscriptions/counts</code></td><td>Active subscription counts by type</td></tr>
        <tr><td>GET</td><td><code>/api/v1/student/sessions</code></td><td>Upcoming sessions</td></tr>
        <tr><td>GET</td><td><code>/api/v1/student/sessions/{id}</code></td><td>Session detail with meeting token</td></tr>
        <tr><td>POST</td><td><code>/api/v1/student/sessions/{id}/feedback</code></td><td>Submit session feedback/rating</td></tr>
        <tr><td>GET</td><td><code>/api/v1/student/courses/interactive/{id}</code></td><td>Interactive course detail</td></tr>
        <tr><td>GET</td><td><code>/api/v1/student/courses/interactive/{id}/quizzes</code></td><td>Quizzes for course</td></tr>
        <tr><td>GET</td><td><code>/api/v1/student/courses/interactive/{id}/progress</code></td><td>Student progress in course</td></tr>
        <tr><td>GET</td><td><code>/api/v1/student/courses/interactive/{id}/reviews</code></td><td>Course reviews with rating summary</td></tr>
        <tr><td>POST</td><td><code>/api/v1/chat/supervised</code></td><td>Start supervised teacher-student-supervisor chat</td></tr>
        <tr><td>POST</td><td><code>/api/v1/chat/conversations</code></td><td>Start private chat (non teacher-student)</td></tr>
        <tr><td>GET</td><td><code>/api/v1/teacher/sessions</code></td><td>Teacher's upcoming sessions</td></tr>
        <tr><td>PUT</td><td><code>/api/v1/teacher/sessions/{id}/evaluation</code></td><td>Submit session evaluation (academic)</td></tr>
        <tr><td>POST</td><td><code>/api/v1/teacher/sessions/{id}/evaluation</code></td><td>Submit session evaluation (quran)</td></tr>
        <tr><td>PUT</td><td><code>/api/v1/teacher/sessions/{id}/attendance</code></td><td>Update session attendance</td></tr>
    </tbody>
</table>
</div>

<h2 id="conventions">API Field Name Conventions</h2>

<div class="help-warning">
    <i class="ri-alert-line help-callout-icon"></i>
    <div>These field name conventions are established in the mobile app and must be maintained:
    <ul style="margin-top: 0.5rem; margin-bottom: 0;">
        <li>Meeting URL JSON key: <code>meeting_url</code> (DB column is <code>meeting_link</code>)</li>
        <li>Session notes JSON key: <code>session_notes</code></li>
        <li>All participant mappings must include <code>user_type</code> field</li>
        <li>Currency: send ISO code (<code>SAR</code>, <code>EGP</code>) — mobile converts to symbol via CurrencyHelper</li>
        <li>Reviews: use flat fields — <code>reviewer_name</code>, <code>comment</code> (NOT <code>review</code>), <code>reviewer_avatar_url</code></li>
    </ul>
    </div>
</div>

<h2 id="important-gotchas">API-Specific Gotchas</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Gotcha</th><th>Detail</th></tr></thead>
    <tbody>
        <tr>
            <td><code>quiz_attempts.student_id</code></td>
            <td>Points to <code>student_profiles.id</code> — NOT <code>users.id</code></td>
        </tr>
        <tr>
            <td><code>course_subscriptions.student_id</code></td>
            <td>Points to <code>users.id</code> (different from quiz_attempts!)</td>
        </tr>
        <tr>
            <td>Teacher view session</td>
            <td><code>resource.teacher.id</code> stores the STUDENT user ID in teacher-view responses (confusing naming, inherited from a refactor)</td>
        </tr>
        <tr>
            <td>InteractiveCourse materials</td>
            <td>The materials tab always returns empty — there is no materials model for interactive courses</td>
        </tr>
        <tr>
            <td>QuizAssignment.assignable_type</td>
            <td>Full PHP class name: <code>App\Models\InteractiveCourse</code> (not short name)</td>
        </tr>
    </tbody>
</table>
</div>

<h2 id="discovery">API Endpoint Discovery</h2>

<p>Use the built-in audit command to discover all API routes:</p>

<pre><code class="language-bash"># List all API endpoints with methods and middleware
php artisan app:scan-api

# Audit data integrity across the platform
php artisan app:audit-data
</code></pre>

<div class="help-tip">
    <i class="ri-check-line help-callout-icon"></i>
    <div>
        API routes are organized into versioned files in <code>routes/api/</code>.
        The main entry is <code>routes/api.php</code> which includes version-specific route files.
        To add a new API endpoint: create it in the appropriate route file and a corresponding
        controller in <code>app/Http/Controllers/Api/</code>.
    </div>
</div>

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
    }
});
</script>
@endpush
