---
trigger: always_on
alwaysApply: true
---
# Development Workflow for Itqan Platform

## Core Development Process

### 1. Task-Driven Development
- Use structured task breakdown for complex features
- Follow dependency chains for proper implementation order
- Log progress and findings during implementation
- Update related tasks when implementation changes

### 2. Feature Branch Workflow
```bash
# Create feature branch
git checkout -b feature/session-management
git push -u origin feature/session-management

# Work on tasks in isolation
# Commit frequently with descriptive messages
git commit -m "feat(sessions): implement QuranSession model with tenant scoping

- Add ScopedToAcademy trait for multi-tenancy
- Include proper validation rules
- Add relationships to Teacher and Student models
- Update database migration with foreign keys"

# Push and create PR when feature complete
git push origin feature/session-management
```

### 3. Code Quality Standards

#### Pre-Commit Checklist
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Run tests
php artisan test

# Check for syntax errors
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Commit Message Format
```
type(scope): brief description

- Detailed bullet point of changes
- Another change made
- Reference to task IDs if applicable

Closes #123
```

### 4. Testing Requirements

#### Feature Testing Pattern
```php
// tests/Feature/QuranSessionTest.php
class QuranSessionTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up tenant context
        $this->academy = Academy::factory()->create();
        $this->actingAs($this->academy);
    }
    
    public function test_teacher_can_create_session_with_proper_scoping()
    {
        $teacher = QuranTeacher::factory()->create([
            'academy_id' => $this->academy->id
        ]);
        
        $this->actingAs($teacher->user);
        
        $sessionData = [
            'title' => 'تحفيظ سورة الفاتحة',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'teacher_id' => $teacher->id,
        ];
        
        $response = $this->postJson('/api/sessions', $sessionData);
        
        $response->assertStatus(201);
        
        // Assert proper tenant scoping
        $session = QuranSession::first();
        $this->assertEquals($this->academy->id, $session->academy_id);
        $this->assertEquals($teacher->id, $session->teacher_id);
    }
}
```

### 5. Multi-Tenancy Implementation Workflow

#### Always Follow This Pattern
```php
// 1. Model Creation
class QuranSession extends Model
{
    use ScopedToAcademy; // REQUIRED for all tenant-scoped models
    
    protected $fillable = [
        'title',
        'description', 
        'start_time',
        'end_time',
        'teacher_id',
        'academy_id', // Always include academy_id
    ];
    
    // 2. Relationships with proper scoping
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(QuranTeacher::class)
                    ->where('academy_id', tenant('id'));
    }
    
    // 3. Additional scopes if needed
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

// 4. Migration with proper foreign keys
Schema::create('quran_sessions', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->timestamp('start_time');
    $table->timestamp('end_time');
    
    // Foreign keys with proper constraints
    $table->foreignId('academy_id')->constrained()->onDelete('cascade');
    $table->foreignId('teacher_id')->constrained('quran_teachers')->onDelete('cascade');
    
    $table->timestamps();
    
    // Indexes for performance
    $table->index(['academy_id', 'start_time']);
    $table->index(['teacher_id', 'status']);
});
```

### 6. Service Layer Implementation

```php
// app/Services/SessionManagementService.php
class SessionManagementService
{
    public function createSession(array $data): QuranSession
    {
        return DB::transaction(function () use ($data) {
            // 1. Create session with automatic tenant scoping
            $session = QuranSession::create($data);
            
            // 2. Schedule automated tasks
            $this->scheduleMeetingCreation($session);
            
            // 3. Send notifications
            $this->notifyParticipants($session);
            
            // 4. Log activity
            activity()
                ->performedOn($session)
                ->causedBy(auth()->user())
                ->log('Session created');
            
            return $session;
        });
    }
    
    private function scheduleMeetingCreation(QuranSession $session): void
    {
        // Schedule job to create Google Meet link 15 minutes before
        CreateGoogleMeetSessionJob::dispatch($session)
            ->delay($session->start_time->subMinutes(15));
    }
}
```

### 7. API Development Standards

#### Controller Pattern
```php
// app/Http/Controllers/Api/QuranSessionController.php
class QuranSessionController extends Controller
{
    public function __construct(
        private SessionManagementService $sessionService
    ) {}
    
    public function store(StoreQuranSessionRequest $request): JsonResponse
    {
        try {
            $session = $this->sessionService->createSession(
                $request->validated()
            );
            
            return response()->json([
                'message' => __('sessions.created_successfully'),
                'data' => new QuranSessionResource($session),
            ], 201);
            
        } catch (Exception $e) {
            Log::error('Failed to create session', [
                'request_data' => $request->validated(),
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'academy_id' => tenant('id'),
            ]);
            
            return response()->json([
                'message' => __('sessions.creation_failed'),
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    public function show(QuranSession $session): JsonResponse
    {
        $this->authorize('view', $session);
        
        return response()->json([
            'data' => new QuranSessionResource($session->load([
                'teacher',
                'students',
                'assignments'
            ])),
        ]);
    }
}
```

### 8. Frontend Integration with Livewire

#### Component Pattern
```php
// app/Livewire/SessionDetail.php
class SessionDetail extends Component
{
    public QuranSession $session;
    public bool $showMeetingInterface = false;
    
    protected $listeners = ['sessionUpdated' => 'refreshSession'];
    
    public function mount(QuranSession $session)
    {
        $this->authorize('view', $session);
        $this->session = $session;
        $this->checkMeetingAvailability();
    }
    
    public function joinMeeting()
    {
        if ($this->session->canJoin(auth()->user())) {
            $this->showMeetingInterface = true;
            $this->dispatch('meeting-join-requested', [
                'sessionId' => $this->session->id
            ]);
        }
    }
    
    private function checkMeetingAvailability()
    {
        $this->showMeetingInterface = $this->session->is_live && 
                                     $this->session->canJoin(auth()->user());
    }
    
    public function render()
    {
        return view('livewire.session-detail');
    }
}
```

### 9. Error Handling & Logging

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    // Handle multi-tenancy violations
    if ($exception instanceof TenantScopeViolationException) {
        Log::critical('Tenant scope violation detected', [
            'user_id' => auth()->id(),
            'requested_resource' => $request->url(),
            'exception' => $exception->getMessage(),
        ]);
        
        return response()->json([
            'message' => 'غير مصرح لك بالوصول لهذا المورد',
        ], 403);
    }
    
    // Handle API errors with Arabic messages
    if ($request->expectsJson()) {
        return response()->json([
            'message' => $this->getArabicErrorMessage($exception),
            'error_code' => $this->getErrorCode($exception),
        ], $this->getStatusCode($exception));
    }
    
    return parent::render($request, $exception);
}
```

### 10. Performance Optimization

#### Query Optimization Checklist
```php
// ✅ DO: Eager load relationships
$sessions = QuranSession::with(['teacher', 'students', 'academy'])
    ->where('status', 'active')
    ->get();

// ✅ DO: Use specific selects when needed
$sessions = QuranSession::select(['id', 'title', 'start_time', 'teacher_id'])
    ->with('teacher:id,name')
    ->get();

// ✅ DO: Implement caching for expensive queries
Cache::remember("academy_{tenant('id')}_active_sessions", 600, function () {
    return QuranSession::active()
        ->with(['teacher', 'students'])
        ->get();
});

// ❌ DON'T: Load unnecessary data
$sessions = QuranSession::with([
    'teacher.user.profile.avatar', // Too deep
    'students.user.profile.parent', // Expensive
])->get();
```

### 11. Deployment & CI/CD

#### GitHub Actions Workflow
```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: itqan_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
      
      redis:
        image: redis:7.0
        ports:
          - 6379:6379
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
      
      - name: Install dependencies
        run: |
          composer install --no-dev --optimize-autoloader
          npm ci
      
      - name: Run Laravel Pint
        run: ./vendor/bin/pint --test
      
      - name: Run tests
        run: php artisan test --coverage
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: itqan_test
          DB_USERNAME: root
          DB_PASSWORD: password
```

### 12. Documentation Standards

#### Code Documentation
```php
/**
 * Create a new Quran session with automatic meeting scheduling
 * 
 * @param array $data Session data including title, times, and participants
 * @return QuranSession The created session with meeting link
 * 
 * @throws ValidationException When session data is invalid
 * @throws TenantScopeException When teacher doesn't belong to current academy
 * @throws GoogleCalendarException When meeting creation fails
 */
public function createSession(array $data): QuranSession
{
    // Implementation
}
```

#### API Documentation
```php
/**
 * @OA\Post(
 *     path="/api/sessions",
 *     summary="Create new Quran session",
 *     tags={"Sessions"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title","start_time","end_time","teacher_id"},
 *             @OA\Property(property="title", type="string", example="تحفيظ سورة الفاتحة"),
 *             @OA\Property(property="start_time", type="string", format="date-time"),
 *             @OA\Property(property="end_time", type="string", format="date-time"),
 *             @OA\Property(property="teacher_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Session created successfully"),
 *     @OA\Response(response=422, description="Validation error"),
 *     @OA\Response(response=403, description="Unauthorized")
 * )
 */
```