@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'service-layer'])

@section('title', 'Service Layer')

@section('content')

<div class="prose prose-slate max-w-none">

    {{-- =========================================================
         1. Why a Service Layer?
         ========================================================= --}}
    <h2 id="why">Why a Service Layer?</h2>
    <p>
        The Itqan codebase enforces a strict <strong>thin controller / fat service</strong> architecture.
        Controllers are responsible for exactly three things:
    </p>
    <ol>
        <li>Accepting an HTTP request (validated via a <code>FormRequest</code>)</li>
        <li>Delegating work to the appropriate service</li>
        <li>Returning an HTTP response</li>
    </ol>
    <p>
        All business logic — session status transitions, subscription counting, attendance calculations,
        payment gateway calls, notification dispatching — lives in <code>app/Services/</code>.
        This separation makes services independently testable, reusable across controllers and Filament pages,
        and safe to call from queue jobs without HTTP context.
    </p>
    <p>
        Services are resolved through Laravel's service container via constructor injection.
        Most services do not need to be manually registered in <code>AppServiceProvider</code> because
        Laravel's auto-resolution handles them. Register explicitly only when you need to bind
        an interface to a concrete implementation or configure constructor parameters that cannot
        be auto-resolved.
    </p>

    {{-- =========================================================
         2. Service Organization
         ========================================================= --}}
    <h2 id="organization">Service Organization</h2>
    <p>
        Services are grouped into domain-focused subdirectories under <code>app/Services/</code>:
    </p>

    <div class="bg-slate-900 text-slate-100 rounded-lg p-5 font-mono text-sm leading-relaxed my-6 overflow-x-auto">
<pre>app/Services/
├── Session Management
│   ├── AcademicSessionMeetingService.php   — Academic meeting lifecycle (26KB)
│   ├── SessionMeetingService.php           — General session meeting operations (24KB)
│   └── SessionTransitionService.php        — Status transition orchestration
│
├── Subscription &amp; Payment
│   ├── SubscriptionService.php             — Activate, renew, cancel (910 lines)
│   ├── PaymentService.php                  — Gateway routing and webhook handling
│   └── Payment/Gateways/
│       ├── PaymobGateway.php               — Paymob Egypt (1264 lines)
│       └── EasyKashGateway.php             — EasyKash SAR
│
├── LiveKit &amp; Video
│   ├── LiveKit/LiveKitTokenGenerator.php   — JWT token generation per role
│   ├── LiveKit/LiveKitRoomManager.php      — Create / close LiveKit rooms
│   ├── LiveKit/LiveKitWebhookHandler.php   — Validate + process LiveKit events
│   └── LiveKit/LiveKitRecordingManager.php — Recording start/stop/retrieval
│
├── Attendance
│   ├── AttendanceCalculationService.php    — Final status computation
│   ├── AttendanceEventService.php          — Raw event storage from webhooks
│   └── MeetingAttendanceService.php        — Per-meeting attendance aggregation
│
├── Notifications
│   ├── NotificationService.php             — Central dispatch entry point
│   └── Notification/
│       ├── NotificationDispatcher.php      — Routes to correct channel
│       └── *Builder.php                    — Builder classes per notification type
│
├── Calendar
│   └── Calendar/
│       ├── CalendarFilterService.php       — Role-based event filtering (27KB)
│       ├── EventFetchingService.php        — Fetches raw events from DB
│       └── SessionStrategyFactory.php      — Selects strategy per session type
│
├── Chat
│   ├── ChatPermissionService.php           — Matrix-based chat authorization
│   └── SupervisedChatGroupService.php      — Creates teacher-student-supervisor groups
│
└── Unified
    ├── Unified/UnifiedSessionFetchingService.php   — Cross-type session queries
    └── Unified/UnifiedStatisticsService.php        — Dashboard aggregation</pre>
    </div>

    {{-- =========================================================
         3. Key Services Reference Table
         ========================================================= --}}
    <h2 id="reference">Key Services Reference</h2>

    <div class="help-table-wrapper overflow-x-auto">
        <table class="help-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Location</th>
                    <th>Key Methods</th>
                    <th>When to Use</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>UnifiedAttendanceService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>updateAttendance()</code>, <code>calculateFinal()</code></td>
                    <td>Any attendance tracking operation — do not call individual attendance models directly</td>
                </tr>
                <tr>
                    <td><code>CalendarService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>getEvents()</code>, <code>filterByRole()</code></td>
                    <td>All calendar views; it delegates to CalendarFilterService + strategy classes</td>
                </tr>
                <tr>
                    <td><code>SessionStatusService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>updateStatuses()</code>, <code>batchUpdate()</code></td>
                    <td>Session status transitions; called by <code>UpdateSessionStatusesCommand</code></td>
                </tr>
                <tr>
                    <td><code>LiveKitService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>createToken()</code>, <code>createRoom()</code>, <code>handleWebhook()</code></td>
                    <td>All LiveKit interactions — never call the LiveKit SDK directly from controllers</td>
                </tr>
                <tr>
                    <td><code>PaymentService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>initiatePayment()</code>, <code>handleWebhook()</code></td>
                    <td>Payment processing; routes to correct gateway based on currency/academy config</td>
                </tr>
                <tr>
                    <td><code>SubscriptionService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>activate()</code>, <code>renew()</code>, <code>cancel()</code></td>
                    <td>Full subscription lifecycle management</td>
                </tr>
                <tr>
                    <td><code>ChatPermissionService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>canChat()</code>, <code>resolveConversation()</code></td>
                    <td>Determining if two users may chat; resolving the correct WireChat conversation</td>
                </tr>
                <tr>
                    <td><code>HomeworkService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>assign()</code>, <code>submit()</code>, <code>grade()</code></td>
                    <td>All homework workflow steps for both Quran and Academic session types</td>
                </tr>
                <tr>
                    <td><code>NotificationService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>send()</code>, <code>dispatch()</code></td>
                    <td>The only entry point for sending any notification — never instantiate notification classes directly</td>
                </tr>
                <tr>
                    <td><code>CertificateService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>generate()</code>, <code>send()</code></td>
                    <td>PDF certificate generation and delivery on course completion</td>
                </tr>
                <tr>
                    <td><code>StudentDashboardService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>getData()</code></td>
                    <td>Aggregating all data for the student dashboard in one optimized query set</td>
                </tr>
                <tr>
                    <td><code>EarningsCalculationService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>calculate()</code>, <code>byPeriod()</code></td>
                    <td>Teacher earnings computation based on completed sessions and commission rules</td>
                </tr>
                <tr>
                    <td><code>ReviewService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>create()</code>, <code>approve()</code></td>
                    <td>Course and teacher review submission and moderation</td>
                </tr>
                <tr>
                    <td><code>ExchangeRateService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>convert()</code>, <code>refresh()</code></td>
                    <td>Currency conversion between SAR and EGP; rates cached in Redis</td>
                </tr>
                <tr>
                    <td><code>SearchService</code></td>
                    <td><code>app/Services/</code></td>
                    <td><code>search()</code></td>
                    <td>Unified search across teachers, courses, and circles in a single call</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- =========================================================
         4. How to Add a New Service
         ========================================================= --}}
    <h2 id="adding-services">How to Add a New Service</h2>

    <p>Follow these steps when adding a new service:</p>

    <ol>
        <li>
            <strong>Create the file</strong> in <code>app/Services/YourService.php</code>
            (or a subdirectory for domain grouping, e.g. <code>app/Services/Payment/YourGateway.php</code>).
        </li>
        <li>
            <strong>Inject dependencies via the constructor</strong> using readonly property promotion.
            This makes dependencies explicit and testable.
        </li>
        <li>
            <strong>Register in <code>AppServiceProvider</code></strong> only if you need to bind an interface
            to a concrete class, or if constructor arguments cannot be auto-resolved (e.g. config strings).
            Otherwise Laravel resolves the class automatically.
        </li>
        <li>
            <strong>Keep each method focused.</strong>
            A service method should do one thing. If a method grows beyond ~40 lines, extract a helper method or split the service.
        </li>
        <li>
            <strong>Use DTOs</strong> in <code>app/DTOs/</code> for complex input/output data structures
            instead of passing long parameter lists.
        </li>
        <li>
            <strong>Never access <code>Request</code> inside a service.</strong>
            Pass only plain PHP values from the controller.
            This keeps services usable from queue jobs, CLI commands, and tests without HTTP context.
        </li>
    </ol>

    <p><strong>Service class template:</strong></p>

    <pre><code class="language-php">&lt;?php

namespace App\Services;

use App\DTOs\YourInputDTO;
use App\Models\SomeModel;

class YourService
{
    public function __construct(
        private readonly AnotherService $anotherService,
        private readonly SomeModel $model,  // inject via container if needed
    ) {}

    /**
     * Do something meaningful.
     *
     * @param  int  $id
     * @return array
     */
    public function doSomething(int $id): array
    {
        // Business logic here — no Auth::user(), no Request, no response()
        return $this-&gt;anotherService-&gt;process($id);
    }
}</code></pre>

    <p><strong>Thin controller calling the service:</strong></p>

    <pre><code class="language-php">class YourController extends Controller
{
    public function __construct(
        private readonly YourService $yourService,
    ) {}

    public function store(YourFormRequest $request): JsonResponse
    {
        $result = $this-&gt;yourService-&gt;doSomething(
            id: $request->validated('id'),
        );

        return response()-&gt;json($result, 201);
    }
}</code></pre>

    {{-- =========================================================
         5. Calendar Strategy Pattern
         ========================================================= --}}
    <h2 id="calendar-strategy">Calendar Strategy Pattern</h2>
    <p>
        <code>CalendarService</code> uses the <strong>Strategy pattern</strong> to handle the structural
        differences between session types without <code>if/else</code> chains.
    </p>

    <p><strong>The chain of responsibilities:</strong></p>

    <ol>
        <li>
            <strong><code>CalendarService</code></strong> — Entry point.
            Accepts user/role context and a date range. Delegates filtering to <code>CalendarFilterService</code>.
        </li>
        <li>
            <strong><code>CalendarFilterService</code></strong> — Applies role-based visibility rules
            (a teacher only sees their own sessions, a student only their subscribed sessions, a supervisor sees all).
            Delegates data fetching to <code>EventFetchingService</code>.
        </li>
        <li>
            <strong><code>EventFetchingService</code></strong> — Runs the actual DB queries.
            For each session type, it calls <code>SessionStrategyFactory::make(type)</code> to get the
            appropriate strategy instance.
        </li>
        <li>
            <strong><code>SessionStrategyFactory</code></strong> — Returns the correct strategy:
            <ul>
                <li><code>QuranSessionStrategy</code> — handles <code>quran_sessions</code>, circle vs individual, Quran teacher FK</li>
                <li><code>AcademicSessionStrategy</code> — handles <code>academic_sessions</code>, academic teacher profile FK</li>
                <li><code>InteractiveCourseSessionStrategy</code> — handles <code>interactive_course_sessions</code>, virtual academy_id, split date/time fields</li>
            </ul>
        </li>
        <li>
            <strong><code>EventFormattingService</code></strong> — Transforms raw Eloquent models into a normalized
            calendar event format suitable for the FullCalendar.js frontend.
        </li>
    </ol>

    <p>
        To add support for a new session type, create a new class implementing <code>SessionStrategyInterface</code>
        (which requires <code>getEvents()</code> and <code>formatEvent()</code>) and register it in
        <code>SessionStrategyFactory</code>. No other calendar code needs to change.
    </p>

    {{-- =========================================================
         6. Service Layer Rules
         ========================================================= --}}
    <h2 id="rules">Service Layer Rules</h2>

    <div class="help-danger">
        <strong>Rules that must never be broken:</strong>
        <ul class="mt-2 space-y-2">
            <li>
                <strong>NEVER put business logic in controllers.</strong>
                Controllers must stay thin: validate → delegate → respond.
                Any logic that would be duplicated across two controllers belongs in a service.
            </li>
            <li>
                <strong>NEVER put business logic in models.</strong>
                Models are data containers with relationship definitions, casts, scopes, and accessors.
                Complex operations (status transitions, payment calls, notification dispatch) go in services.
            </li>
            <li>
                <strong>NEVER access <code>Auth::user()</code> inside a service.</strong>
                Services must be context-agnostic. Pass the authenticated user as a parameter from the controller
                so the service can be called from queue jobs (where Auth context is unavailable).
            </li>
            <li>
                <strong>NEVER throw HTTP exceptions (<code>abort()</code>, <code>HttpException</code>) from a service.</strong>
                Throw domain-specific exceptions (e.g. <code>SubscriptionExpiredException</code>).
                Let the controller or <code>bootstrap/app.php</code> exception handler convert them to HTTP responses.
                This keeps services testable and reusable outside HTTP contexts.
            </li>
        </ul>
    </div>

    <div class="help-note">
        <strong>Filament pages follow the same rules.</strong>
        Filament resources and pages are essentially controllers in a different skin.
        They must delegate to the same service layer — never implement business logic directly
        in Filament action closures or form submit handlers.
    </div>

</div>

@endsection
