@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'real-time-system'])

@section('content')

<h2 id="overview">Three Real-time Systems</h2>

<p>The platform has three distinct real-time communication systems, each serving a different purpose:</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>System</th><th>Technology</th><th>Purpose</th><th>Port</th></tr></thead>
    <tbody>
        <tr>
            <td><strong>Event Broadcasting</strong></td>
            <td>Laravel Reverb</td>
            <td>Session updates, attendance changes, notifications to browser</td>
            <td>8085 (internal)</td>
        </tr>
        <tr>
            <td><strong>Video Conferencing</strong></td>
            <td>LiveKit</td>
            <td>WebRTC video meetings for all session types</td>
            <td>Self-hosted server</td>
        </tr>
        <tr>
            <td><strong>Chat Messaging</strong></td>
            <td>WireChat 0.5</td>
            <td>Text chat between users (teacher-supervisor-student)</td>
            <td>Via Reverb</td>
        </tr>
    </tbody>
</table>
</div>

<h2 id="reverb">Laravel Reverb — WebSocket Broadcasting</h2>

<p>
    Reverb is a first-party Laravel WebSocket server that scales via Redis pub/sub.
    The internal server listens on <strong>port 8085</strong>. TLS termination is handled by Nginx
    (browsers connect via <code>wss://</code>).
</p>

<div class="help-mermaid">
<pre class="mermaid">
sequenceDiagram
    participant Browser
    participant Nginx as Nginx (TLS)
    participant Reverb as Reverb :8085
    participant Redis as Redis (scaling)
    participant Laravel as Laravel App

    Browser->>Nginx: WSS connect to itqanway.com:443
    Nginx->>Reverb: Proxy WS to :8085
    Reverb->>Redis: Subscribe to reverb channel
    Browser-->>Reverb: Authenticated (Laravel Echo + Pusher.js)

    Note over Laravel: Session status changes to 'live'
    Laravel->>Redis: Publish broadcast event
    Redis->>Reverb: Push to subscribed channel
    Reverb->>Browser: Real-time event received
    Browser->>Browser: Alpine.js updates UI
</pre>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>Reverb config key facts:</strong>
        Internal address <code>0.0.0.0:8085</code> — never exposed directly.
        Nginx proxies browser <code>wss://</code> connections.
        Redis scaling enabled (channel: <code>reverb</code>), supporting multiple app servers.
        Max message size: 10KB. Ping interval: 60s. Activity timeout: 30s.
    </div>
</div>

<p><strong>Configuration files:</strong></p>
<ul>
    <li><code>config/reverb.php</code> — Server host, port, TLS options, Redis scaling</li>
    <li><code>config/broadcasting.php</code> — Default driver set to <code>reverb</code></li>
    <li><code>routes/channels.php</code> — Private/presence channel authorization</li>
</ul>

<div class="help-warning">
    <i class="ri-alert-line help-callout-icon"></i>
    <div>
        <strong>Subdomain routing caveat:</strong> Broadcasting channels in
        <code>routes/channels.php</code> use <code>{subdomain}</code> dynamic routing.
        In queue/CLI contexts (no HTTP request), you must call
        <code>URL::defaults(['subdomain' => $academy->subdomain])</code>
        before dispatching events, otherwise channel authorization fails.
    </div>
</div>

<h2 id="livekit">LiveKit — Video Conferencing</h2>

<p>
    LiveKit is a self-hosted WebRTC media server. The Laravel app creates rooms, generates tokens,
    and processes webhooks. The Flutter mobile app and browser JS SDK connect directly to LiveKit.
</p>

<div class="help-mermaid">
<pre class="mermaid">
sequenceDiagram
    participant Teacher
    participant Browser
    participant Laravel
    participant LiveKit as LiveKit Server

    Note over Laravel: Session status → LIVE
    Laravel->>LiveKit: Create room (LiveKitRoomManager)
    Laravel->>Laravel: Store meeting_link on session

    Teacher->>Browser: Opens session page
    Browser->>Laravel: GET /sessions/{id}/meeting-token
    Laravel->>LiveKit: Create JWT token (LiveKitTokenGenerator)
    Laravel-->>Browser: Token + Server URL
    Browser->>LiveKit: Connect with token (JS SDK)

    Note over Browser: Student joins
    LiveKit->>Laravel: POST /webhooks/livekit (participant_joined)
    Laravel->>Laravel: Create MeetingAttendanceEvent record
    Laravel->>Reverb: Broadcast AttendanceUpdated event

    Note over Browser: Session ends
    LiveKit->>Laravel: POST /webhooks/livekit (room_ended)
    Laravel->>Laravel: CalculateSessionAttendance job
</pre>
</div>

<p><strong>LiveKit service architecture</strong> (<code>app/Services/LiveKit/</code>):</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Service</th><th>Responsibility</th></tr></thead>
    <tbody>
        <tr><td><code>LiveKitService</code></td><td>Main coordinator — validates server, delegates to sub-services</td></tr>
        <tr><td><code>LiveKitTokenGenerator</code></td><td>JWT token generation per participant with video/audio permissions</td></tr>
        <tr><td><code>LiveKitRoomManager</code></td><td>Create/list/delete rooms, participant listing</td></tr>
        <tr><td><code>LiveKitWebhookHandler</code></td><td>Validates webhook signature, processes participant join/leave/track events</td></tr>
        <tr><td><code>LiveKitRecordingManager</code></td><td>Start/stop/delete recordings via Egress API</td></tr>
        <tr><td><code>RoomPermissionService</code></td><td>Redis-cached room permissions for fast webhook processing</td></tr>
    </tbody>
</table>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>Meeting integration rule:</strong> Meetings are ALWAYS embedded in session pages —
        never on separate routes. The <code>meeting_feature-requirements.mdc</code> rule document
        explicitly forbids standalone meeting pages.
        Config file: <code>config/livekit.php</code>.
        Max participants: 50. Token TTL: 1 hour. Identity prefix: <code>itqan_</code>.
    </div>
</div>

<h2 id="wirechat">WireChat — Supervised Chat</h2>

<p>
    WireChat 0.5.0 provides the chat system. The Itqan platform adds a supervision layer:
    <strong>teachers and students cannot chat directly</strong> — all communication goes through
    a 3-way group chat with a supervisor present.
</p>

<div class="help-mermaid">
<pre class="mermaid">
graph LR
    S[Student] --> GC[Group Chat<br/>via supervisor]
    T[Teacher] --> GC
    SV[Supervisor] --> GC
    GC --> W[WireChat Conversation]

    style GC fill:#fef9c3,stroke:#fbbf24
    style SV fill:#f0fdf4,stroke:#22c55e
</pre>
</div>

<p><strong>Two broadcast events power WireChat:</strong></p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Event</th><th>Queue</th><th>Purpose</th></tr></thead>
    <tbody>
        <tr>
            <td><code>MessageCreated</code></td>
            <td><code>messages</code> (queued, high priority)</td>
            <td>New message notification to room</td>
        </tr>
        <tr>
            <td><code>NotifyParticipant</code></td>
            <td><code>ShouldBroadcastNow</code> (immediate)</td>
            <td>Direct notification to specific participant</td>
        </tr>
    </tbody>
</table>
</div>

<p><strong>Supervised chat API endpoint:</strong></p>
<pre><code>POST /api/v1/chat/supervised
Body: { teacher_id, student_id, entity_type, entity_id }

entity_type values:
  quran_individual | quran_circle | academic_lesson | interactive_course
</code></pre>

<div class="help-warning">
    <i class="ri-alert-line help-callout-icon"></i>
    <div>
        <strong>Horizon must process the <code>messages</code> queue!</strong>
        The Horizon supervisor-default config must include <code>messages</code> in its queue list,
        otherwise real-time chat delivery fails silently.
        Check: <code>config/horizon.php</code> → supervisor-default queues.
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
