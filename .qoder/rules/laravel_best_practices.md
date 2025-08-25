---
trigger: always_on
alwaysApply: true
---
# Laravel Best Practices for Itqan Platform

## Core Laravel Principles

- **Follow Laravel 11 Conventions**: Use streamlined directory structure (no kernel files)
- **Keep Controllers Thin**: Move business logic to Services and specialized classes  
- **Use Form Requests**: Always validate with custom Form Request classes
- **Embrace Eloquent**: Use proper relationships, scopes, and query optimization
- **Queue Heavy Tasks**: Background jobs for emails, file processing, notifications

## Naming Conventions

### Models & Relationships
```php
// ✅ DO: Singular model names, clear relationships
class QuranSession extends Model 
{
    use ScopedToAcademy; // Required for multi-tenancy
    
    public function teacher(): BelongsTo 
    {
        return $this->belongsTo(QuranTeacher::class);
    }
    
    public function students(): BelongsToMany 
    {
        return $this->belongsToMany(Student::class);
    }
}

// ❌ DON'T: Plural model names or missing tenant scoping
class QuranSessions extends Model {} // Wrong naming
```

### Controllers & Routes
```php
// ✅ DO: Resource controllers with clear actions
class QuranSessionController extends Controller
{
    public function show(QuranSession $session)
    {
        $this->authorize('view', $session);
        return view('student.sessions.show', compact('session'));
    }
}

// ✅ DO: Use existing session routes for meetings (CRITICAL)
Route::get('/sessions/{session}', [QuranSessionController::class, 'show'])
    ->name('student.sessions.show');

// ❌ DON'T: Create separate meeting routes
// Route::get('/meetings/{session}/join', ...) // FORBIDDEN
```

## Multi-Tenancy Requirements (CRITICAL)

### Tenant Scoping
```php
// ✅ DO: Always use ScopedToAcademy trait
class Student extends Model
{
    use ScopedToAcademy;
    
    protected $fillable = ['name', 'email', 'tenant_id'];
    
    protected static function booted()
    {
        parent::booted();
        // ScopedToAcademy trait handles global scoping
    }
}

// ❌ DON'T: Query without tenant scoping
Student::all(); // Dangerous - crosses tenant boundaries
```

## Performance & Security

### Query Optimization
```php
// ✅ DO: Eager load relationships
$sessions = QuranSession::with(['teacher', 'students'])
    ->where('status', 'active')
    ->get();

// ✅ DO: Use pagination for large datasets
$sessions = QuranSession::paginate(15);

// ❌ DON'T: Lazy load in loops (N+1 problem)
foreach ($sessions as $session) {
    echo $session->teacher->name; // N+1 query problem
}
```

### Form Validation
```php
// ✅ DO: Custom Form Request classes
class StoreQuranSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', QuranSession::class);
    }
    
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'start_time' => 'required|date|after:now',
            'teacher_id' => 'required|exists:quran_teachers,id',
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الجلسة مطلوب',
            'start_time.required' => 'وقت بداية الجلسة مطلوب',
        ];
    }
}
```

## Service Layer Pattern

```php
// ✅ DO: Business logic in service classes
class SessionManagementService
{
    public function createSession(array $data): QuranSession
    {
        return DB::transaction(function () use ($data) {
            $session = QuranSession::create($data);
            $this->scheduleAutomaticMeeting($session);
            $this->notifyParticipants($session);
            return $session;
        });
    }
    
    private function scheduleAutomaticMeeting(QuranSession $session): void
    {
        CreateScheduledMeetingsCommand::dispatch($session);
    }
}
```

## Arabic Content & Localization

```php
// ✅ DO: Proper Arabic localization
// resources/lang/ar/sessions.php
return [
    'session_created' => 'تم إنشاء الجلسة بنجاح',
    'session_updated' => 'تم تحديث الجلسة بنجاح',
];

// In controllers/views
return response()->json([
    'message' => __('sessions.session_created')
]);
```

## Job & Queue Management

```php
// ✅ DO: Queue heavy operations
class SendSessionReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        private QuranSession $session
    ) {}
    
    public function handle(): void
    {
        // Send notifications to all participants
        foreach ($this->session->students as $student) {
            $student->notify(new SessionReminderNotification($this->session));
        }
    }
}

// Dispatch after response for better UX
dispatch(new SendSessionReminderJob($session))->afterResponse();
```

## Critical Anti-Patterns to Avoid

```php
// ❌ DON'T: Fat controllers with business logic
class SessionController extends Controller
{
    public function store(Request $request)
    {
        // 50+ lines of business logic here - WRONG
    }
}

// ❌ DON'T: Direct DB queries without Eloquent
public function index()
{
    $sessions = DB::select('SELECT * FROM quran_sessions'); // Bypasses tenant scoping
}

// ❌ DON'T: Ignore tenant isolation
QuranSession::withoutGlobalScopes()->get(); // Dangerous for multi-tenancy
```

## Testing Requirements

```php
// ✅ DO: Comprehensive feature tests
class QuranSessionTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_teacher_can_create_session()
    {
        $teacher = QuranTeacher::factory()->create();
        
        $response = $this->actingAs($teacher->user)
            ->post('/sessions', [
                'title' => 'تحفيظ سورة الفاتحة',
                'start_time' => now()->addDay(),
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('quran_sessions', [
            'title' => 'تحفيظ سورة الفاتحة',
            'teacher_id' => $teacher->id,
        ]);
    }
}
```