# Itqan Platform - Development Rules and Guidelines

## 🎯 Project Overview

**Itqan Platform** is a comprehensive multi-tenant SaaS educational platform for Islamic academies, built with Laravel 11, Filament 4, and Livewire 3. The platform serves Quran memorization programs, live academic tutoring, and recorded course delivery with complete tenant isolation and Arabic-first design.

### Core Architecture Principles
- **Multi-Tenant SaaS**: Single database with tenant isolation via `tenant_id`
- **Arabic-First Design**: RTL support and Arabic language as primary
- **Multi-Panel UI**: Role-specific interfaces optimized for each user type
- **Real-time Communication**: WebSocket-based chat and notifications
- **Secure Content Delivery**: Signed URLs and tenant-scoped storage

---

## 🛠 Technology Stack Requirements

### Backend Core
```
Laravel 11.x (PHP 8.3+)
├── Database: MySQL 8.0+
├── Cache/Queue: Redis 7.0+
├── File Storage: DigitalOcean Spaces (S3-compatible)
├── WebSockets: Soketi/Pusher Protocol
└── Multi-tenancy: Spatie Laravel Multitenancy
```

### Frontend Stack
```
Blade Templates + Livewire 3
├── CSS Framework: TailwindCSS 3.x with RTL support
├── Admin Panels: Multiple Filament 4.x Panels
├── Real-time: Livewire Components + WebSockets
├── Chat: Enhanced Chatify Package
└── Video Meetings: LiveKit JavaScript SDK
```

### Key Packages
- **filament/filament**: v3.0+ (Multi-panel admin interfaces)
- **livewire/livewire**: v3.0+ (Real-time UI components)
- **spatie/laravel-multitenancy**: v3.2+ (Tenant isolation)
- **spatie/laravel-permission**: v6.0+ (Role-based access control)
- **munafio/chatify**: v1.6+ (Enhanced chat system)
- **livekit-client**: v2.15.5+ (Video meeting integration)
- **google/apiclient**: v2.15+ (Google Calendar integration)

---

## 🏗 Laravel Best Practices & Standards

### 1. Code Structure & Organization

#### Directory Structure
```
app/
├── Actions/           # Single-purpose action classes
├── Enums/            # Application enums
├── Filament/         # Multi-panel Filament resources
│   ├── Academy/      # Academy admin panel
│   ├── Pages/        # Custom Filament pages
│   └── Resources/    # Filament resources
├── Helpers/          # Helper classes
├── Http/
│   ├── Controllers/  # Keep thin, delegate to services
│   ├── Requests/     # Form validation classes
│   └── Resources/    # API resources
├── Models/           # Eloquent models with tenant scoping
├── Services/         # Business logic services
├── Policies/         # Authorization policies
└── Jobs/             # Queue jobs
```

#### Naming Conventions
```php
// ✅ DO: Follow Laravel conventions
class User extends Model {}              // Singular model names
class PostController extends Controller  // Resource controller naming
public function comments(): HasMany {}   // Clear relationship methods

// ❌ DON'T: Use unclear naming
class Users extends Model {}             // Wrong: plural model name
public function getComments() {}         // Wrong: unclear relationship
```

### 2. Multi-Tenancy Implementation

#### Tenant Scoping
```php
// ✅ DO: Always scope queries by tenant
class Post extends Model {
    use BelongsToTenant;
    
    protected static function booted() {
        static::addGlobalScope(new TenantScope);
    }
}

// ✅ DO: Include tenant_id in all relevant models
protected $fillable = ['title', 'content', 'tenant_id'];
```

#### Tenant Isolation
```php
// ✅ DO: Use tenant-aware storage paths
Storage::disk('spaces')->path("tenants/{tenant_id}/uploads/...")
Storage::disk('spaces')->path("tenants/{tenant_id}/chat/...")

// ✅ DO: Verify tenant access in policies
Gate::define('access-tenant', function ($user, $tenant_id) {
    return $user->tenant_id === $tenant_id;
});
```

### 3. Database & Migration Standards

#### Migration Best Practices
```php
// ✅ DO: Use descriptive migration names
create_users_table.php
add_tenant_id_to_posts_table.php
create_session_schedules_table.php

// ✅ DO: Add proper foreign key constraints
$table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// ✅ DO: Create strategic indexes for multi-tenant queries
$table->index(['tenant_id', 'status', 'created_at']);
```

#### Eloquent Model Standards
```php
// ✅ DO: Define fillable properties and casts
protected $fillable = ['name', 'email', 'tenant_id'];

protected $casts = [
    'email_verified_at' => 'datetime',
    'settings' => 'array',
    'is_active' => 'boolean',
];

// ✅ DO: Use query scopes for reusable queries
public function scopeActive($query) {
    return $query->where('is_active', true);
}

// ✅ DO: Define clear relationships with return types
public function posts(): HasMany {
    return $this->hasMany(Post::class);
}
```

### 4. Performance & N+1 Prevention

#### Eager Loading
```php
// ✅ DO: Eager load relationships to prevent N+1
$posts = Post::with(['author', 'comments.user'])->get();

// ✅ DO: Use pagination for large datasets
Post::with('author')->paginate(15);

// ❌ DON'T: Lazy load in loops
foreach ($posts as $post) {
    echo $post->author->name; // N+1 problem
}
```

### 5. Form Requests & Validation

#### Custom Form Requests
```php
// ✅ DO: Create custom form requests with Arabic messages
class StorePostRequest extends FormRequest {
    public function authorize() {
        return $this->user()->can('create', Post::class);
    }
    
    public function rules() {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }
    
    public function messages() {
        return [
            'title.required' => 'العنوان مطلوب',
            'content.required' => 'المحتوى مطلوب',
        ];
    }
}
```

---

## 🎛 Multi-Panel UI Architecture

### Panel Structure
```
Power User Panels (Filament):
├── Super-Admin Panel (/admin - Global Domain)
├── Academy Admin Panel (/{academy}/panel)
├── Teacher Panel (/{academy}/teacher-panel)
└── Supervisor Panel (/{academy}/supervisor-panel)

End User Areas (Blade + Livewire):
├── Student Area (/{academy}/student)
└── Parent Area (/{academy}/parent)
```

### Panel Configuration
```php
// ✅ DO: Configure panels with proper tenant scoping
class AcademyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('academy')
            ->path('/panel')
            ->tenant(Academy::class)
            ->authGuard('web')
            ->colors(['primary' => 'var(--academy-primary-color)'])
            ->middleware(['auth', 'tenant'])
            ->resources([
                StudentResource::class,
                TeacherResource::class,
                // ... academy-scoped resources
            ]);
    }
}
```

### Routing Structure
```php
// ✅ DO: Use proper domain-based tenant routing
Route::domain('{academy}.'.config('app.domain'))
    ->middleware(['web', 'tenant'])
    ->group(function () {
        // Panel routes auto-registered by Filament
        // Student/Parent areas with Livewire
        Route::middleware(['auth', 'role:student'])
            ->prefix('student')
            ->group(function () {
                Route::get('/', StudentDashboard::class)->name('student.dashboard');
            });
    });
```

---

## 🎨 UI Design System & TailwindCSS Rules

### Design Philosophy
- **Arabic-First**: All interfaces designed with Arabic and RTL support as primary
- **Consistency Over Creativity**: Maintain consistent patterns across components
- **Mobile-First Responsive**: Every component works perfectly on mobile devices
- **Accessibility**: WCAG 2.1 AA compliance for inclusive design

### Color System
```css
/* Primary Brand Colors */
primary: {
  50: '#f0f9ff',    // Very light blue
  500: '#0ea5e9',   // Main primary color
  600: '#0284c7',   // Hover state
  700: '#0369a1',   // Active state
}

/* Success Colors (Islamic Green) */
success: {
  500: '#22c55e',
  600: '#16a34a',
}
```

### Component Patterns
```php
// ✅ DO: Use consistent component patterns
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
  <h3 class="text-lg font-semibold text-gray-900 mb-4">Card Title</h3>
  <p class="text-sm text-gray-600 mb-4">Card description</p>
  <div class="flex items-center justify-end space-x-3 space-x-reverse">
    <button class="btn-secondary">Secondary Action</button>
    <button class="btn-primary">Primary Action</button>
  </div>
</div>
```

### RTL/Arabic Support
```php
// ✅ DO: Use proper directional classes
<div class="flex items-center space-x-3 space-x-reverse"> // RTL-safe spacing
<div class="text-right"> // Arabic text alignment
<div class="mr-4 ml-0 rtl:mr-0 rtl:ml-4"> // RTL-responsive margins

// ✅ DO: Use dir="auto" for mixed content
<p dir="auto" class="text-sm text-gray-700">
  Mixed العربي and English content
</p>
```

### Button Components
```php
// Primary Button
btn-primary: "inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 
              text-white font-medium rounded-md transition-colors duration-200 
              focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"

// Secondary Button  
btn-secondary: "inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 
                text-gray-700 font-medium rounded-md border border-gray-300 
                transition-colors duration-200"
```

---

## 📱 LiveKit Meeting Integration Rules

### 🚨 CRITICAL REQUIREMENTS - NEVER VIOLATE

#### 1. NO SEPARATE MEETING ROUTES
- **ABSOLUTELY FORBIDDEN**: Creating separate meeting routes like `/meetings/{session}/join`
- **REQUIRED**: All meeting functionality MUST be integrated directly into session detail pages
- **CORRECT APPROACH**: Use existing `student.sessions.show` and `teacher.sessions.show` routes

#### 2. SINGLE UNIFIED UI FOR ALL ROLES
- **FORBIDDEN**: Different UI implementations for teachers vs students
- **REQUIRED**: One robust, professional UI that works for all participants
- **OBJECTIVE**: Group video call functionality that actually works

#### 3. USE LIVEKIT JAVASCRIPT SDK DIRECTLY
- **REQUIRED**: Use LiveKit JavaScript SDK (https://github.com/livekit/client-sdk-js)
- **FORBIDDEN**: iframe solutions, LiveKit Meet embedding, or external meeting UIs
- **DOCUMENTATION**: https://docs.livekit.io/reference/client-sdk-js/

### Implementation Guidelines
```javascript
// ✅ REQUIRED: Direct LiveKit SDK usage
import { Room, RoomEvent, ConnectionState } from 'livekit-client';

// ✅ CORRECT: Room connection within session page
const room = new Room();
await room.connect(serverUrl, token);

// ✅ DO: Integrate into session detail pages
class SessionMeeting {
    constructor(sessionId) {
        this.sessionId = sessionId;
        this.room = new Room();
        this.setupEventListeners();
    }
    
    async joinMeeting() {
        const token = await this.getParticipantToken();
        await this.room.connect(serverUrl, token);
        this.setupVideoGrid();
    }
}
```

### Session Detail Page Structure
```blade
@extends('components.layouts.teacher') {{-- or student --}}

@section('content')
<div class="container">
    <!-- Session Info -->
    <div class="session-header">
        <!-- Session details -->
    </div>
    
    <!-- Meeting Interface -->
    <div class="meeting-container">
        <!-- Video Grid -->
        <div class="video-grid">
            <!-- Participant videos -->
        </div>
        
        <!-- Meeting Controls -->
        <div class="meeting-controls">
            <!-- Mute, camera, screen share, etc. -->
        </div>
        
        <!-- Chat Panel -->
        <div class="chat-panel">
            <!-- Real-time chat -->
        </div>
    </div>
</div>
@endsection
```

---

## 🔗 Google Calendar Integration Guidelines

### Service Account Configuration
```php
// ✅ DO: Create dedicated Google service class
class GoogleCalendarService {
    protected Google_Client $client;
    protected Google_Service_Calendar $service;
    
    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google-calendar/credentials.json'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->service = new Google_Service_Calendar($this->client);
    }
}
```

### Meet Link Generation
```php
// ✅ DO: Use proper conferenceData structure for Meet links
public function createEventWithMeetLink(array $eventData): Google_Service_Calendar_Event {
    $event = new Google_Service_Calendar_Event([
        'summary' => $eventData['title'],
        'start' => ['dateTime' => $eventData['start_time']],
        'end' => ['dateTime' => $eventData['end_time']],
        'conferenceData' => [
            'createRequest' => [
                'requestId' => Str::uuid()->toString(),
                'conferenceSolutionKey' => [
                    'type' => 'hangoutsMeet'
                ]
            ]
        ]
    ]);
    
    return $this->service->events->insert(
        $calendarId, 
        $event, 
        ['conferenceDataVersion' => 1] // ✅ REQUIRED parameter
    );
}
```

### Error Handling
```php
// ✅ DO: Handle token expiration gracefully
public function refreshTokenIfNeeded(User $user): void {
    $token = decrypt($user->google_calendar_token);
    $this->client->setAccessToken($token);
    
    if ($this->client->isAccessTokenExpired()) {
        $refreshToken = $this->client->getRefreshToken();
        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        
        $newToken = $this->client->getAccessToken();
        $user->update([
            'google_calendar_token' => encrypt($newToken)
        ]);
    }
}
```

---

## 🧪 Testing Standards

### Feature Testing
```php
// ✅ DO: Write comprehensive feature tests
class PostCreationTest extends TestCase {
    use RefreshDatabase;
    
    public function test_authenticated_user_can_create_post() {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post('/posts', [
            'title' => 'Test Post',
            'content' => 'Test content',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'user_id' => $user->id,
        ]);
    }
}
```

### Livewire Testing
```php
// ✅ DO: Test Livewire components properly
Livewire::test(Counter::class)
    ->assertSet('count', 0)
    ->call('increment')
    ->assertSet('count', 1)
    ->assertSee(1);
```

### Filament Testing
```php
// ✅ DO: Test Filament resources
livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertRedirect();
```

---

## 🔒 Security Best Practices

### Authentication & Authorization
```php
// ✅ DO: Always validate user permissions
public function show(Post $post) {
    $this->authorize('view', $post);
    return view('posts.show', compact('post'));
}

// ✅ DO: Use policies for authorization logic
class PostPolicy {
    public function update(User $user, Post $post) {
        return $user->id === $post->user_id;
    }
}
```

### Data Protection
```php
// ✅ DO: Encrypt sensitive data
protected $casts = [
    'payment_details' => 'encrypted:array',
    'google_calendar_token' => 'encrypted',
];

// ✅ DO: Use signed URLs for protected content
Route::get('/secure/{path}', function ($path) {
    abort_unless(URL::hasValidSignature(request()), 401);
    return Storage::disk('spaces')->response($path);
})->middleware(['signed'])->name('secure.file');
```

---

## 🌍 Arabic Content & Localization

### Localization Implementation
```php
// ✅ DO: Use proper localization
// In resources/lang/ar/messages.php
return [
    'welcome' => 'مرحباً بك في منصة إتقان',
    'login_required' => 'يجب تسجيل الدخول للمتابعة',
];

// ✅ DO: Use translation helpers
{{ __('messages.welcome') }}
return response()->json(['message' => __('auth.failed')]);
```

### RTL Layout Support
```php
// ✅ DO: Handle RTL properly in views
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

// ✅ DO: Use appropriate CSS classes
<div class="{{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
```

### Arabic Font Stack
```css
/* ✅ DO: Define proper Arabic font stack */
.font-arabic {
  font-family: 'Tajawal', 'Cairo', 'Amiri', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
```

---

## ⚡ Performance Optimization

### Caching Strategy
```php
// ✅ DO: Use multi-level caching
Cache::remember('posts.featured', 3600, function () {
    return Post::where('featured', true)->with('author')->get();
});

// ✅ DO: Cache tenant settings
Cache::remember("tenant.{$tenantId}.settings", 3600, function () use ($tenantId) {
    return TenantSetting::where('tenant_id', $tenantId)->pluck('value', 'key');
});
```

### Queue Management
```php
// ✅ DO: Use queues for heavy operations
dispatch(new SendWelcomeEmailJob($user))->afterResponse();

// ✅ DO: Implement queue priorities
'high'    => Session reminders, payment processing
'default' => Notifications, email sending
'low'     => Report generation, analytics
```

### Database Optimization
```sql
-- ✅ DO: Create strategic indexes for multi-tenant queries
CREATE INDEX idx_tenant_user_role ON users(tenant_id, role_type);
CREATE INDEX idx_tenant_active_sessions ON sessions(tenant_id, status, scheduled_at);
```

---

## 🚫 Anti-Patterns to Avoid

### Code Anti-Patterns
```php
// ❌ DON'T: Fat controllers
class PostController extends Controller {
    public function store(Request $request) {
        // 50+ lines of business logic - WRONG
    }
}

// ❌ DON'T: Direct DB queries in controllers
public function index() {
    $posts = DB::select('SELECT * FROM posts'); // Use Eloquent instead
}

// ❌ DON'T: Ignore Laravel conventions
class post extends Model {} // Wrong casing
public function getUserPosts() {} // Should use relationship
```

### UI Anti-Patterns
```php
// ❌ DON'T: Use arbitrary values unnecessarily
<div class="mt-[23px] px-[15px] w-[234px]">

// ❌ DON'T: Mix utility classes with custom CSS
<div class="flex custom-weird-class" style="margin-top: 13px;">

// ❌ DON'T: Use inconsistent color shades
<div class="bg-blue-300 text-green-700 border-red-400">
```

### Meeting Integration Anti-Patterns
```php
// ❌ DON'T: Create separate meeting routes
Route::get('/meetings/{session}/join', ...); // FORBIDDEN

// ❌ DON'T: Use iframe solutions
<iframe src="https://meet.google.com/..."></iframe> // Use LiveKit SDK

// ❌ DON'T: Forget conferenceDataVersion parameter
$this->service->events->insert($calendarId, $event); // Missing parameter
```

---

## 📋 Development Workflow

### Code Quality Checklist
- [ ] Follows Laravel naming conventions
- [ ] Includes proper tenant scoping
- [ ] Uses Form Requests for validation
- [ ] Implements proper authorization
- [ ] Includes Arabic translations
- [ ] Supports RTL layout
- [ ] Uses consistent UI components
- [ ] Includes comprehensive tests
- [ ] Follows security best practices
- [ ] Optimized for performance

### Before Committing
```bash
# Run code formatting
vendor/bin/pint --dirty

# Run tests
php artisan test

# Build assets
npm run build

# Check for security issues
composer audit
```

### Deployment Checklist
- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Assets built and optimized
- [ ] Queue workers configured
- [ ] Redis cache cleared
- [ ] File permissions set
- [ ] SSL certificates installed
- [ ] Monitoring configured

---

## 🔄 Continuous Improvement

### Rule Updates
- **Add New Rules When**: A pattern is used in 3+ files, common bugs could be prevented, or new technologies are adopted
- **Modify Existing Rules When**: Better examples exist, edge cases are discovered, or implementation details change
- **Monitor**: Code review comments, development questions, performance issues

### Documentation Maintenance
- Keep examples synchronized with actual code
- Update references to external documentation
- Cross-reference related rules
- Document breaking changes and migration paths

---

**Remember**: These rules ensure maintainable, secure, and performant Laravel applications while supporting Arabic content and multi-tenant architecture. Consistency over creativity - every component should feel like part of the same design system.
