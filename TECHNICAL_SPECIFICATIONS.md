# Itqan Platform - Technical Specifications

## 1. Multi-Panel UI Implementation Specifications

### 1.1 Panel Architecture Overview

#### Panel Provider Configuration
```php
// app/Providers/PanelServiceProvider.php
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('/admin')
            ->domain(config('app.domain')) // Main domain only
            ->authGuard('admin')
            ->colors(['primary' => '#1f2937'])
            ->navigationGroups([
                'النظرة العامة' => 'Dashboard',
                'إدارة الأكاديميات' => 'Academies',
                'إدارة المستخدمين' => 'Users',
                'المحتوى القرآني' => 'Quran',
                'التعليم الأكاديمي' => 'Academic',
                'الإدارة المالية' => 'Financial',
                'السجلات والتدقيق' => 'Auditing',
                'الإعدادات' => 'Settings',
            ])
            ->middleware(['auth:admin', 'verified'])
            ->resources([
                AcademyResource::class,
                GlobalUserResource::class,
                // ... other global resources
            ]);
    }
}

class AcademyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('academy')
            ->path('/panel')
            ->tenant(Academy::class)
            ->tenantRoutePrefix('academy')
            ->authGuard('web')
            ->colors(['primary' => 'var(--academy-primary-color)'])
            ->navigationGroups([
                'لوحة التحكم' => 'Dashboard',
                'إدارة المستخدمين' => 'Users',
                'المحتوى التعليمي' => 'Content',
                'الجلسات والدورات' => 'Sessions',
                'التقارير' => 'Reports',
                'الإعدادات' => 'Settings',
            ])
            ->middleware(['auth', 'tenant'])
            ->resources([
                StudentResource::class,
                TeacherResource::class,
                CourseResource::class,
                // ... academy-scoped resources
            ]);
    }
}

class TeacherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('teacher')
            ->path('/teacher-panel')
            ->tenant(Academy::class)
            ->authGuard('web')
            ->navigationGroups([
                'جدولي' => 'Schedule',
                'جلساتي' => 'Sessions',
                'الواجبات' => 'Assignments',
                'دوراتي' => 'Courses',
                'ملفي' => 'Profile',
            ])
            ->middleware(['auth', 'tenant', 'role:teacher'])
            ->resources([
                MySessionResource::class,
                MyAssignmentResource::class,
                MyStudentResource::class,
            ]);
    }
}
```

#### Routing Structure
```php
// routes/web.php

// Global Super-Admin Routes (Main Domain)
Route::domain(config('app.domain'))
    ->middleware(['web'])
    ->group(function () {
        // Super-Admin Authentication
        Route::prefix('admin')->group(function () {
            Route::get('login', [AdminAuthController::class, 'showLogin']);
            Route::post('login', [AdminAuthController::class, 'login']);
            Route::post('logout', [AdminAuthController::class, 'logout']);
        });
    });

// Tenant-Scoped Routes (Academy Subdomains)
Route::domain('{academy}.'.config('app.domain'))
    ->middleware(['web', 'tenant'])
    ->group(function () {
        
        // Authentication Routes
        Auth::routes(['register' => false]); // Registration via admin only
        
        // Panel Routes (Auto-registered by Filament)
        // - /panel (Academy Admin)
        // - /teacher-panel (Teachers)
        // - /supervisor-panel (Supervisors)
        
        // Student Personal Area
        Route::middleware(['auth', 'role:student'])
            ->prefix('student')
            ->group(function () {
                Route::get('/', StudentDashboard::class)->name('student.dashboard');
                Route::get('/subscriptions', StudentSubscriptions::class);
                Route::get('/assignments', StudentAssignments::class);
                Route::get('/tests', StudentTests::class);
                Route::get('/reports', StudentReports::class);
                Route::get('/payments', StudentPayments::class);
                Route::get('/messages', StudentMessages::class);
                Route::get('/settings', StudentSettings::class);
            });
        
        // Parent Personal Area
        Route::middleware(['auth', 'role:parent'])
            ->prefix('parent')
            ->group(function () {
                Route::get('/', ParentDashboard::class)->name('parent.dashboard');
                Route::get('/children', ParentChildren::class);
                Route::get('/reports', ParentReports::class);
                Route::get('/communication', ParentCommunication::class);
                Route::get('/payments', ParentPayments::class);
                Route::get('/settings', ParentSettings::class);
            });
    });
```

### 1.2 Role-Based UI Components

#### Panel Access Control
```php
// app/Policies/PanelPolicy.php
class PanelPolicy
{
    public function accessAdminPanel(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
    
    public function accessAcademyPanel(User $user): bool
    {
        return $user->hasAnyRole(['academy_admin', 'teacher', 'supervisor']);
    }
    
    public function accessTeacherPanel(User $user): bool
    {
        return $user->hasRole('teacher');
    }
    
    public function accessSupervisorPanel(User $user): bool
    {
        return $user->hasRole('supervisor');
    }
}
```

#### Navigation Customization
```php
// app/Filament/Resources/BaseResource.php
abstract class BaseResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return match (auth()->user()?->role) {
            'super_admin' => static::getSuperAdminGroup(),
            'academy_admin' => static::getAcademyAdminGroup(),
            'teacher' => static::getTeacherGroup(),
            'supervisor' => static::getSupervisorGroup(),
            default => null,
        };
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()?->can('access', static::class) ?? false;
    }
    
    abstract protected static function getSuperAdminGroup(): string;
    abstract protected static function getAcademyAdminGroup(): string;
    abstract protected static function getTeacherGroup(): string;
    abstract protected static function getSupervisorGroup(): string;
}
```

### 1.3 Student/Parent Area Implementation

#### Livewire Dashboard Components
```php
// app/Livewire/Student/StudentDashboard.php
use Livewire\Component;

class StudentDashboard extends Component
{
    public $upcomingSessions;
    public $recentAssignments;
    public $progressStats;
    public $notifications;
    
    public function mount()
    {
        $this->loadDashboardData();
    }
    
    protected function loadDashboardData()
    {
        $student = auth()->user();
        
        $this->upcomingSessions = $student->sessions()
            ->where('scheduled_at', '>', now())
            ->with(['teacher', 'subject'])
            ->limit(3)
            ->get();
            
        $this->recentAssignments = $student->assignments()
            ->where('due_date', '>', now())
            ->orderBy('due_date')
            ->limit(5)
            ->get();
            
        $this->progressStats = [
            'completed_sessions' => $student->sessions()->completed()->count(),
            'average_score' => $student->quiz_attempts()->avg('score'),
            'attendance_rate' => $student->calculateAttendanceRate(),
        ];
    }
    
    public function render()
    {
        return view('livewire.student.dashboard')
            ->layout('layouts.student');
    }
}
```

#### Responsive Layout Templates
```blade
{{-- resources/views/layouts/student.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'منطقة الطالب' }} - {{ tenant('name') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Mobile Menu Button -->
        <div class="lg:hidden">
            <button x-data x-on:click="$store.sidebar.toggle()" 
                    class="fixed top-4 right-4 z-50 p-2 bg-white rounded-md shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        
        <!-- Sidebar Navigation -->
        <div class="fixed inset-y-0 right-0 z-40 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0" 
             x-show="$store.sidebar.open" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:leave="transition ease-in duration-300">
             
            <!-- Academy Branding -->
            <div class="flex items-center justify-center h-16 bg-primary-600">
                <img src="{{ tenant('logo_url') }}" alt="{{ tenant('name') }}" class="h-8">
                <span class="mr-2 text-white font-semibold">{{ tenant('name') }}</span>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="mt-8">
                <div class="px-4 space-y-2">
                    <a href="{{ route('student.dashboard') }}" 
                       class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 
                              {{ request()->routeIs('student.dashboard') ? 'bg-primary-50 text-primary-700' : '' }}">
                        <svg class="w-5 h-5 ml-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        الصفحة الرئيسية
                    </a>
                    
                    <a href="{{ route('student.subscriptions') }}" 
                       class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5 ml-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm1 2a1 1 0 000 2h6a1 1 0 100-2H7zm6 7a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1zm-3 3a1 1 0 100 2h.01a1 1 0 100-2H10zm-4 1a1 1 0 011-1h.01a1 1 0 110 2H7a1 1 0 01-1-1zm1-4a1 1 0 100 2h.01a1 1 0 100-2H7zm2 1a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm4-4a1 1 0 100 2h.01a1 1 0 100-2H13z" clip-rule="evenodd"></path>
                        </svg>
                        اشتراكاتي
                    </a>
                    
                    <!-- More navigation items -->
                </div>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="lg:mr-64">
            <main class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    
    @livewireScripts
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('sidebar', {
                open: false,
                toggle() {
                    this.open = !this.open;
                }
            });
        });
    </script>
</body>
</html>
```

## 2. Database Schema Specifications

### 2.1 Core User Management Tables

#### Users Table (Enhanced)
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academy_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'academy_admin', 'teacher', 'supervisor', 'student', 'parent') NOT NULL,
    status ENUM('pending', 'active', 'inactive', 'rejected') DEFAULT 'pending',
    bio TEXT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Teacher-specific fields
    teacher_type ENUM('quran', 'academic') NULL,
    qualification_degree ENUM('bachelor', 'master', 'phd', 'other') NULL,
    qualification_text TEXT NULL,
    university VARCHAR(255) NULL,
    years_experience INT NULL,
    has_ijazah BOOLEAN DEFAULT FALSE,
    student_session_price DECIMAL(8,2) NULL, -- What student pays
    teacher_session_price DECIMAL(8,2) NULL, -- What teacher receives
    
    -- Student-specific fields
    parent_phone VARCHAR(20) NULL, -- For automatic parent account creation
    parent_id BIGINT UNSIGNED NULL,
    
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_academy_role (academy_id, role),
    INDEX idx_status (status),
    INDEX idx_teacher_type (teacher_type)
);
```

#### Grade Levels Table (Academy-Specific)
```sql
CREATE TABLE grade_levels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academy_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    order_index INT NOT NULL DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_academy_grade (academy_id, name),
    INDEX idx_academy_active (academy_id, is_active, order_index)
);
```

#### Subjects Table (Academy-Specific, Academic Teachers Only)
```sql
CREATE TABLE subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academy_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_academy_subject (academy_id, name),
    INDEX idx_academy_active (academy_id, is_active)
);
```

#### Teacher-Subject Relationships (Many-to-Many)
```sql
CREATE TABLE teacher_subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id)
);
```

#### Teacher-Grade Level Relationships (Many-to-Many)
```sql
CREATE TABLE teacher_grade_levels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    grade_level_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_grade (teacher_id, grade_level_id)
);
```

#### Student-Grade Level Relationship
```sql
CREATE TABLE student_grade_levels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    grade_level_id BIGINT UNSIGNED NOT NULL,
    academic_year VARCHAR(10) NOT NULL, -- e.g., "2024-2025"
    is_current BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id) ON DELETE CASCADE,
    INDEX idx_student_current (student_id, is_current),
    INDEX idx_academic_year (academic_year)
);
```

#### Teacher Approval Workflow
```sql
CREATE TABLE teacher_approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT NULL,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_teacher (teacher_id)
);
```

#### WhatsApp Notifications Log
```sql
CREATE TABLE whatsapp_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academy_id BIGINT UNSIGNED NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('parent_account_created', 'session_reminder', 'payment_reminder') NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
    external_id VARCHAR(255) NULL, -- WhatsApp API message ID
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    failed_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    INDEX idx_academy_status (academy_id, status),
    INDEX idx_phone_type (phone_number, type)
);
```

### 2.2 Registration Forms Structure

#### Academy-Specific Registration Routes
```
Domain: {academy}.domain.com

/register - Registration type selection page
├── /register/teacher - Teacher type selection
│   ├── /register/teacher/quran - Quran Teacher Registration
│   └── /register/teacher/academic - Academic Teacher Registration
├── /register/student - Student Registration
└── /register/parent - Parent Registration (manual, if needed)

Admin Routes:
/{academy}/panel/teachers/pending - Teacher approval queue
/{academy}/panel/grade-levels - Grade levels management
/{academy}/panel/subjects - Subjects management (academic only)
```

#### Registration Form Fields Mapping

**Quran Teacher Registration:**
```php
[
    'first_name' => 'required|string|max:255',
    'last_name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'phone' => 'required|string|max:20',
    'password' => 'required|min:8|confirmed',
    'bio' => 'nullable|string|max:1000',
    'has_ijazah' => 'required|boolean',
    'years_experience' => 'required|integer|min:0|max:50',
    'grade_level_ids' => 'required|array|min:1',
    'grade_level_ids.*' => 'exists:grade_levels,id'
]
```

**Academic Teacher Registration:**
```php
[
    'first_name' => 'required|string|max:255',
    'last_name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'phone' => 'required|string|max:20',
    'password' => 'required|min:8|confirmed',
    'bio' => 'nullable|string|max:1000',
    'qualification_degree' => 'required|in:bachelor,master,phd,other',
    'qualification_text' => 'required|string|max:500',
    'university' => 'required|string|max:255',
    'years_experience' => 'required|integer|min:0|max:50',
    'subject_ids' => 'required|array|min:1',
    'subject_ids.*' => 'exists:subjects,id',
    'grade_level_ids' => 'required|array|min:1',
    'grade_level_ids.*' => 'exists:grade_levels,id'
]
```

**Student Registration:**
```php
[
    'first_name' => 'required|string|max:255',
    'last_name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'phone' => 'required|string|max:20',
    'password' => 'required|min:8|confirmed',
    'grade_level_id' => 'required|exists:grade_levels,id',
    'parent_phone' => 'required|string|max:20'
]
```

### 2.3 Automatic Parent Account Creation Process

```php
class StudentRegistrationService
{
    public function registerStudent(array $data): User
    {
        DB::transaction(function () use ($data) {
            // 1. Create student account
            $student = User::create([
                'academy_id' => tenant('id'),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => 'student',
                'status' => 'active',
                'parent_phone' => $data['parent_phone']
            ]);
            
            // 2. Assign grade level
            $student->currentGradeLevel()->attach($data['grade_level_id'], [
                'academic_year' => $this->getCurrentAcademicYear(),
                'is_current' => true
            ]);
            
            // 3. Create parent account automatically
            $parent = $this->createParentAccount($student, $data['parent_phone']);
            
            // 4. Send WhatsApp notification to parent
            $this->sendParentAccountWhatsApp($parent, $data['parent_phone']);
            
            return $student;
        });
    }
    
    private function createParentAccount(User $student, string $parentPhone): User
    {
        $temporaryPassword = Str::random(8);
        
        $parent = User::create([
            'academy_id' => $student->academy_id,
            'first_name' => $student->first_name . "'s Parent",
            'last_name' => '',
            'email' => 'parent_' . $student->id . '@' . tenant('domain'),
            'phone' => $parentPhone,
            'password' => Hash::make($temporaryPassword),
            'role' => 'parent',
            'status' => 'active'
        ]);
        
        // Link student to parent
        $student->update(['parent_id' => $parent->id]);
        
        // Store temporary password for WhatsApp
        $parent->temp_password = $temporaryPassword;
        
        return $parent;
    }
    
    private function sendParentAccountWhatsApp(User $parent, string $phone): void
    {
        $message = "Welcome to " . tenant('name') . "!\n\n";
        $message .= "A parent account has been created for your child.\n";
        $message .= "Login: " . $parent->email . "\n";
        $message .= "Password: " . $parent->temp_password . "\n";
        $message .= "Please login and change your password.\n\n";
        $message .= "Login URL: " . tenant('url') . "/login";
        
        WhatsAppNotification::create([
            'academy_id' => tenant('id'),
            'phone_number' => $phone,
            'message' => $message,
            'type' => 'parent_account_created',
            'status' => 'pending'
        ]);
        
        // Queue WhatsApp sending job
        SendWhatsAppNotificationJob::dispatch($phone, $message);
    }
}
```

### 2.4 Teacher Approval Workflow

```php
class TeacherApprovalService
{
    public function submitTeacherForApproval(User $teacher): void
    {
        // Create approval record
        TeacherApproval::create([
            'teacher_id' => $teacher->id,
            'status' => 'pending'
        ]);
        
        // Notify academy admin
        $academyAdmins = User::where('academy_id', $teacher->academy_id)
            ->where('role', 'academy_admin')
            ->get();
            
        foreach ($academyAdmins as $admin) {
            $admin->notify(new NewTeacherRegistrationNotification($teacher));
        }
    }
    
    public function approveTeacher(User $teacher, User $admin, array $pricingData): void
    {
        DB::transaction(function () use ($teacher, $admin, $pricingData) {
            // Update teacher status and pricing
            $teacher->update([
                'status' => 'active',
                'student_session_price' => $pricingData['student_price'],
                'teacher_session_price' => $pricingData['teacher_price']
            ]);
            
            // Update approval record
            TeacherApproval::where('teacher_id', $teacher->id)
                ->update([
                    'status' => 'approved',
                    'admin_id' => $admin->id,
                    'approved_at' => now()
                ]);
                
            // Notify teacher
            $teacher->notify(new TeacherApprovedNotification());
        });
    }
}
```

### 1.1 Core Multi-Tenancy Tables

```sql
-- Tenants (Academies) Table
CREATE TABLE tenants (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(100) UNIQUE NOT NULL,
    domain VARCHAR(255) NULL,
    logo_path VARCHAR(500) NULL,
    primary_color VARCHAR(7) DEFAULT '#3B82F6',
    currency VARCHAR(3) DEFAULT 'USD',
    timezone VARCHAR(50) DEFAULT 'UTC',
    locale VARCHAR(5) DEFAULT 'en',
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_subdomain (subdomain),
    INDEX idx_status (status)
);

-- Tenant Settings Table
CREATE TABLE tenant_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    key VARCHAR(100) NOT NULL,
    value JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_setting (tenant_id, key)
);
```

### 1.2 User Management Schema

```sql
-- Users Table
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role_type ENUM('super_admin', 'admin', 'supervisor', 'teacher', 'student', 'parent') NOT NULL,
    status ENUM('active', 'pending', 'suspended', 'inactive') DEFAULT 'pending',
    language VARCHAR(5) DEFAULT 'ar',
    timezone VARCHAR(50) DEFAULT 'Asia/Riyadh',
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_email (tenant_id, email),
    INDEX idx_tenant_role (tenant_id, role_type),
    INDEX idx_status (status)
);

-- User Profiles Table
CREATE TABLE user_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    avatar_path VARCHAR(500) NULL,
    phone VARCHAR(20) NULL,
    bio TEXT NULL,
    date_of_birth DATE NULL,
    gender ENUM('male', 'female') NULL,
    address TEXT NULL,
    certifications JSON NULL,
    subjects JSON NULL,
    experience_years TINYINT UNSIGNED NULL,
    hourly_rate DECIMAL(8,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_profile (user_id)
);

-- Teacher Availability Table
CREATE TABLE teacher_availability (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id CHAR(36) NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_day (teacher_id, day_of_week),
    UNIQUE KEY unique_teacher_slot (teacher_id, day_of_week, start_time, end_time)
);
```

### 1.3 Academic Content Schema

```sql
-- Subjects Table
CREATE TABLE subjects (
    id CHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NULL,
    grade_levels JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_category (tenant_id, category),
    INDEX idx_active (is_active)
);

-- Packages Table
CREATE TABLE packages (
    id CHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('quran_individual', 'quran_circle', 'academic_private', 'academic_course') NOT NULL,
    description TEXT NULL,
    sessions_count INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    duration_days INT UNSIGNED DEFAULT 30,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_type (tenant_id, type),
    INDEX idx_active (is_active)
);

-- Subscriptions Table
CREATE TABLE subscriptions (
    id CHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    student_id CHAR(36) NOT NULL,
    teacher_id CHAR(36) NULL,
    package_id CHAR(36) NOT NULL,
    sessions_total INT UNSIGNED NOT NULL,
    sessions_remaining INT UNSIGNED NOT NULL,
    sessions_completed INT UNSIGNED DEFAULT 0,
    status ENUM('active', 'expired', 'cancelled', 'suspended') DEFAULT 'active',
    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE RESTRICT,
    INDEX idx_tenant_student (tenant_id, student_id),
    INDEX idx_status_expires (status, expires_at)
);

-- Subscription Add-ons Table
CREATE TABLE subscription_addons (
    id CHAR(36) PRIMARY KEY,
    subscription_id CHAR(36) NOT NULL,
    extra_sessions INT UNSIGNED NOT NULL,
    price DECIMAL(8,2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    paid BOOLEAN DEFAULT FALSE,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    INDEX idx_subscription_paid (subscription_id, paid)
);
```

### 1.4 Session Management Schema

```sql
-- Sessions Table
CREATE TABLE sessions (
    id CHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    subscription_id CHAR(36) NULL,
    course_id CHAR(36) NULL,
    teacher_id CHAR(36) NOT NULL,
    student_id CHAR(36) NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type ENUM('individual', 'circle', 'course', 'trial') NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    scheduled_at TIMESTAMP NOT NULL,
    duration_minutes INT UNSIGNED DEFAULT 60,
    meet_link VARCHAR(500) NULL,
    recording_url VARCHAR(500) NULL,
    attendance_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tenant_scheduled (tenant_id, scheduled_at),
    INDEX idx_teacher_status (teacher_id, status),
    INDEX idx_student_scheduled (student_id, scheduled_at)
);

-- Session Participants Table (for group sessions)
CREATE TABLE session_participants (
    session_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    role ENUM('teacher', 'student', 'supervisor') NOT NULL,
    attended BOOLEAN DEFAULT FALSE,
    joined_at TIMESTAMP NULL,
    left_at TIMESTAMP NULL,
    notes TEXT NULL,
    
    PRIMARY KEY (session_id, user_id),
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_role (user_id, role)
);
```

## 2. API Specifications

### 2.1 Authentication Endpoints

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "student@example.com",
    "password": "password123",
    "tenant_id": "academy1"
}

Response:
{
    "success": true,
    "data": {
        "user": {
            "id": "uuid",
            "name": "Student Name",
            "email": "student@example.com",
            "role": "student"
        },
        "token": "sanctum_token",
        "tenant": {
            "id": "academy1",
            "name": "Academy Name",
            "branding": {...}
        }
    }
}
```

### 2.2 Multi-Tenant Resource Endpoints

```http
GET /api/teachers
Headers: 
    Authorization: Bearer {token}
    X-Tenant-ID: academy1

Response:
{
    "success": true,
    "data": [
        {
            "id": "uuid",
            "name": "Teacher Name",
            "subjects": ["quran", "arabic"],
            "rating": 4.8,
            "hourly_rate": 25.00,
            "availability": [...],
            "certifications": [...]
        }
    ],
    "pagination": {...}
}
```

### 2.3 Session Booking API

```http
POST /api/sessions/book-trial
Headers:
    Authorization: Bearer {token}
    X-Tenant-ID: academy1

{
    "teacher_id": "uuid",
    "preferred_datetime": "2024-01-15T10:00:00Z",
    "subject": "quran_memorization",
    "notes": "Student preferences"
}

Response:
{
    "success": true,
    "data": {
        "session_id": "uuid",
        "status": "pending_approval",
        "scheduled_at": "2024-01-15T10:00:00Z",
        "meet_link": null,
        "message": "Trial session request sent for approval"
    }
}
```

### 2.4 Payment Processing API

```http
POST /api/payments/create-invoice
Headers:
    Authorization: Bearer {token}
    X-Tenant-ID: academy1

{
    "items": [
        {
            "type": "subscription",
            "package_id": "uuid",
            "quantity": 1,
            "amount": 100.00
        },
        {
            "type": "addon",
            "subscription_id": "uuid",
            "extra_sessions": 5,
            "amount": 50.00
        }
    ],
    "payment_method": "paymob",
    "currency": "EGP"
}

Response:
{
    "success": true,
    "data": {
        "invoice_id": "uuid",
        "total_amount": 150.00,
        "currency": "EGP",
        "payment_url": "https://paymob.com/checkout/...",
        "expires_at": "2024-01-15T12:00:00Z"
    }
}
```

### 2.5 WhatsApp Integration Options for Parent Notifications

#### Recommended Free/Low-Cost Solutions

**Option 1: WhatsApp Web API (Unofficial - Free)**
```php
// Using whatsapp-web.js via Node.js service
class WhatsAppService
{
    private $nodeServiceUrl;
    
    public function __construct()
    {
        $this->nodeServiceUrl = config('whatsapp.node_service_url');
    }
    
    public function sendMessage(string $phone, string $message): bool
    {
        $response = Http::post($this->nodeServiceUrl . '/send-message', [
            'phone' => $phone,
            'message' => $message
        ]);
        
        return $response->successful();
    }
}
```

**Option 2: Whapi.Cloud (Affordable - $35/month)**
```php
// Using Whapi.Cloud API
use GuzzleHttp\Client;

class WhapiService
{
    private $apiKey;
    private $client;
    
    public function __construct()
    {
        $this->apiKey = config('whatsapp.whapi_api_key');
        $this->client = new Client();
    }
    
    public function sendParentCredentials(string $phone, User $parent): bool
    {
        try {
            $response = $this->client->post('https://gate.whapi.cloud/messages/text', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'to' => $phone,
                    'body' => $this->formatParentMessage($parent)
                ]
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            Log::error('WhatsApp sending failed: ' . $e->getMessage());
            return false;
        }
    }
    
    private function formatParentMessage(User $parent): string
    {
        return "Welcome to " . tenant('name') . "!\n\n" .
               "A parent account has been created for your child.\n" .
               "Email: " . $parent->email . "\n" .
               "Password: " . $parent->temp_password . "\n\n" .
               "Please login and change your password:\n" .
               tenant('url') . "/login\n\n" .
               "Reply STOP to unsubscribe.";
    }
}
```

**Option 3: Alternative SMS Service (Reliable Fallback)**
```php
// Using Twilio SMS as fallback
use Twilio\Rest\Client;

class SMSFallbackService
{
    private $twilio;
    
    public function __construct()
    {
        $this->twilio = new Client(
            config('twilio.account_sid'),
            config('twilio.auth_token')
        );
    }
    
    public function sendParentCredentials(string $phone, User $parent): bool
    {
        try {
            $this->twilio->messages->create($phone, [
                'from' => config('twilio.from_number'),
                'body' => $this->formatSMSMessage($parent)
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
```

#### Recommended Implementation Strategy

**Multi-Channel Notification Service:**
```php
class ParentNotificationService
{
    private $whatsappService;
    private $smsService;
    private $emailService;
    
    public function __construct(
        WhatsAppService $whatsappService,
        SMSFallbackService $smsService,
        EmailService $emailService
    ) {
        $this->whatsappService = $whatsappService;
        $this->smsService = $smsService;
        $this->emailService = $emailService;
    }
    
    public function sendParentCredentials(User $parent, string $phone): void
    {
        // Try WhatsApp first
        $whatsappSent = $this->whatsappService->sendMessage($phone, 
            $this->formatWhatsAppMessage($parent));
            
        if ($whatsappSent) {
            $this->logNotification($parent, 'whatsapp', 'sent', $phone);
            return;
        }
        
        // Fallback to SMS
        $smsSent = $this->smsService->sendParentCredentials($phone, $parent);
        
        if ($smsSent) {
            $this->logNotification($parent, 'sms', 'sent', $phone);
            return;
        }
        
        // Final fallback to email (if available)
        if ($parent->email) {
            $this->emailService->sendParentCredentials($parent);
            $this->logNotification($parent, 'email', 'sent', $parent->email);
        }
    }
    
    private function logNotification(User $parent, string $type, string $status, string $recipient): void
    {
        WhatsAppNotification::create([
            'academy_id' => $parent->academy_id,
            'phone_number' => $recipient,
            'message' => "Parent credentials for {$parent->first_name}",
            'type' => 'parent_account_created',
            'status' => $status,
            'delivery_method' => $type
        ]);
    }
}
```

#### Free Node.js WhatsApp Web Service Setup

**Create separate Node.js service (Free option):**
```javascript
// whatsapp-service/server.js
const { Client, LocalAuth } = require('whatsapp-web.js');
const express = require('express');
const qrcode = require('qrcode-terminal');

const app = express();
app.use(express.json());

const client = new Client({
    authStrategy: new LocalAuth()
});

client.on('qr', (qr) => {
    console.log('QR RECEIVED', qr);
    qrcode.generate(qr, {small: true});
});

client.on('ready', () => {
    console.log('WhatsApp Client is ready!');
});

// Send message endpoint
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message } = req.body;
        const chatId = phone + '@c.us';
        
        await client.sendMessage(chatId, message);
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

client.initialize();
app.listen(3000, () => {
    console.log('WhatsApp service running on port 3000');
});
```

#### Cost-Effective Recommendations

1. **Free Solution**: Use whatsapp-web.js with Node.js service
   - Pros: Completely free, no monthly fees
   - Cons: Requires QR code scanning, less reliable for production

2. **Low-Cost Solution**: Whapi.Cloud ($35/month)
   - Pros: Professional API, reliable, good documentation
   - Cons: Monthly fee, but still very affordable

3. **Hybrid Approach**: Free + Paid fallback
   - Primary: Free whatsapp-web.js
   - Fallback: SMS service (Twilio - pay per message)
   - Best of both worlds: Free for most cases, reliable when needed

#### Academy-Specific Configuration

```php
// In Academy settings
class AcademySettings
{
    public array $notification_preferences = [
        'primary_method' => 'whatsapp',     // whatsapp, sms, email
        'fallback_method' => 'sms',
        'whatsapp_service' => 'whapi',      // whapi, web, disabled
        'sms_service' => 'twilio',          // twilio, disabled
        'message_template' => 'default'     // default, custom
    ];
    
    public function getNotificationService(): ParentNotificationService
    {
        return app(ParentNotificationService::class)
            ->configure($this->notification_preferences);
    }
}
```

## 3. WebSocket Event Specifications

### 3.1 Chat Events

```javascript
// Message sent event
{
    "event": "message.sent",
    "channel": "tenant.academy1.chat.conversation_uuid",
    "data": {
        "id": "message_uuid",
        "conversation_id": "conversation_uuid",
        "sender": {
            "id": "user_uuid",
            "name": "User Name",
            "role": "student"
        },
        "message": "Hello teacher!",
        "attachments": [],
        "sent_at": "2024-01-15T10:30:00Z"
    }
}

// Typing indicator
{
    "event": "user.typing",
    "channel": "tenant.academy1.chat.conversation_uuid",
    "data": {
        "user_id": "user_uuid",
        "user_name": "User Name",
        "is_typing": true
    }
}
```

### 3.2 Session Events

```javascript
// Session reminder
{
    "event": "session.reminder",
    "channel": "tenant.academy1.notifications.user_uuid",
    "data": {
        "session_id": "session_uuid",
        "title": "Quran Session with Teacher Name",
        "scheduled_at": "2024-01-15T11:00:00Z",
        "meet_link": "https://meet.google.com/...",
        "reminder_type": "10_minutes"
    }
}

// Session status update
{
    "event": "session.status_updated",
    "channel": "tenant.academy1.session.session_uuid",
    "data": {
        "session_id": "session_uuid",
        "status": "in_progress",
        "participants": [
            {
                "user_id": "teacher_uuid",
                "role": "teacher",
                "joined_at": "2024-01-15T11:00:00Z"
            }
        ]
    }
}
```

## 4. File Storage Specifications

### 4.1 Storage Path Structure

```
DigitalOcean Spaces: itqan-platform/
├── tenants/
│   ├── academy1/
│   │   ├── uploads/
│   │   │   ├── avatars/
│   │   │   ├── certifications/
│   │   │   └── homework/
│   │   ├── chat/
│   │   │   └── attachments/
│   │   ├── courses/
│   │   │   ├── videos/
│   │   │   ├── materials/
│   │   │   └── recordings/
│   │   └── reports/
│   └── academy2/
│       └── ...
└── system/
    ├── templates/
    └── backups/
```

### 4.2 File Security Implementation

```php
// Secure file serving
class SecureFileController extends Controller
{
    public function serve(Request $request, string $path)
    {
        // Verify signed URL
        if (!URL::hasValidSignature($request)) {
            abort(401, 'Invalid signature');
        }
        
        // Check tenant isolation
        $tenantId = tenant('id');
        if (!str_starts_with($path, "tenants/{$tenantId}/")) {
            abort(403, 'Access denied');
        }
        
        // Verify user permissions
        $this->authorize('view-file', $path);
        
        return Storage::disk('spaces')->response($path);
    }
}

// Generate signed URLs
function generateSecureUrl(string $path, int $expirationMinutes = 60): string
{
    return URL::temporarySignedRoute(
        'secure.file',
        now()->addMinutes($expirationMinutes),
        ['path' => $path]
    );
}
```

## 5. Queue Job Specifications

### 5.1 Session Management Jobs

```php
class GenerateMeetLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public Session $session
    ) {}
    
    public function handle(): void
    {
        // Generate Meet link 15 minutes before session
        if ($this->session->scheduled_at->diffInMinutes(now()) <= 15) {
            $meetLink = GoogleCalendarService::createMeetEvent(
                $this->session->title,
                $this->session->scheduled_at,
                $this->session->duration_minutes
            );
            
            $this->session->update(['meet_link' => $meetLink]);
            
            // Send reminder notifications
            SendSessionReminder::dispatch($this->session);
        }
    }
}
```

### 5.2 Notification Jobs

```php
class SendSessionReminder implements ShouldQueue
{
    public function __construct(
        public Session $session
    ) {}
    
    public function handle(): void
    {
        $participants = $this->session->participants()->get();
        
        foreach ($participants as $participant) {
            // Send push notification
            FCMService::sendNotification($participant, [
                'title' => 'Session Reminder',
                'body' => "Your session starts in 10 minutes",
                'data' => [
                    'session_id' => $this->session->id,
                    'meet_link' => $this->session->meet_link
                ]
            ]);
            
            // Send email
            Mail::to($participant)->send(
                new SessionReminderMail($this->session)
            );
        }
    }
}
```

## 6. Security Specifications

### 6.1 Multi-Tenant Isolation

```php
// Global scope for tenant isolation
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('current-tenant')) {
            $builder->where($model->getTable() . '.tenant_id', tenant('id'));
        }
    }
    
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}

// Middleware for tenant resolution
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenantFromRequest($request);
        
        if (!$tenant) {
            // Default to itqan-academy for root domain
            $tenant = Tenant::where('id', 'itqan-academy')->first();
        }
        
        app()->instance('current-tenant', $tenant);
        config(['app.tenant' => $tenant]);
        
        return $next($request);
    }
}
```

### 6.2 Role-Based Access Control

```php
// Permission matrix implementation
class ChatPermissionPolicy
{
    private array $roleMatrix = [
        'super_admin' => ['super_admin', 'admin', 'supervisor', 'teacher', 'student', 'parent'],
        'admin' => ['supervisor', 'teacher', 'student', 'parent'],
        'supervisor' => ['teacher', 'student', 'parent'],
        'teacher' => ['supervisor', 'student', 'parent'],
        'student' => ['supervisor', 'teacher'],
        'parent' => ['supervisor', 'teacher']
    ];
    
    public function canStartConversation(User $sender, User $receiver): bool
    {
        // Check if same tenant
        if ($sender->tenant_id !== $receiver->tenant_id) {
            return false;
        }
        
        // Check role matrix
        $allowedRoles = $this->roleMatrix[$sender->role_type] ?? [];
        return in_array($receiver->role_type, $allowedRoles);
    }
}
```

## 7. Performance Optimization Specifications

### 7.1 Database Indexing Strategy

```sql
-- Composite indexes for common queries
CREATE INDEX idx_tenant_user_active ON users(tenant_id, status, role_type);
CREATE INDEX idx_tenant_sessions_scheduled ON sessions(tenant_id, scheduled_at, status);
CREATE INDEX idx_subscription_student_active ON subscriptions(student_id, status, expires_at);
CREATE INDEX idx_messages_conversation_time ON chat_messages(conversation_id, created_at);

-- Partial indexes for specific use cases
CREATE INDEX idx_active_teachers ON users(tenant_id, id) WHERE role_type = 'teacher' AND status = 'active';
CREATE INDEX idx_upcoming_sessions ON sessions(teacher_id, scheduled_at) WHERE status = 'scheduled' AND scheduled_at > NOW();
```

### 7.2 Caching Strategy

```php
// Redis caching for frequently accessed data
class TenantCacheService
{
    public function getTenantSettings(string $tenantId): array
    {
        return Cache::remember(
            "tenant:{$tenantId}:settings",
            3600, // 1 hour
            fn() => TenantSetting::where('tenant_id', $tenantId)->pluck('value', 'key')->toArray()
        );
    }
    
    public function getUserPermissions(string $userId): array
    {
        return Cache::remember(
            "user:{$userId}:permissions",
            1800, // 30 minutes
            fn() => User::find($userId)->getAllPermissions()->pluck('name')->toArray()
        );
    }
}
```

This technical specification provides the detailed implementation guidelines needed to build the Itqan platform according to the requirements outlined in the project overview and development roadmap. 