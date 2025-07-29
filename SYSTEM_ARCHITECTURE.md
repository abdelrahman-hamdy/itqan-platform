# Itqan Platform - System Architecture Documentation

## 1. High-Level Architecture Overview

The Itqan Platform is built as a **single-database multi-tenant SaaS application** using Laravel 11, designed to serve multiple Islamic academies while maintaining complete data isolation and customizable branding per tenant.

### Architecture Principles
- **Single Database Multi-Tenancy**: All tenants share the same database with `tenant_id` column for isolation
- **Domain-Based Tenant Resolution**: Each academy gets a subdomain (e.g., `academy1.itqan.com`)
- **Default Tenant**: Root domain serves the default "itqan-academy" tenant
- **Multi-Panel UI Architecture**: Different interfaces optimized for each user role
- **Role-Based Access Control**: Comprehensive permission system using Spatie Permission
- **Event-Driven Communication**: Real-time features using WebSockets and queued jobs

## 2. Technology Stack

### Backend Core
```
Laravel 11.x (PHP 8.3+)
├── Framework: Laravel Framework
├── Database: MySQL 8.0+
├── Cache/Queue: Redis 7.0+
├── File Storage: DigitalOcean Spaces (S3-compatible)
└── WebSockets: Soketi/Pusher Protocol
```

### Frontend Stack
```
Blade Templates + Livewire 3
├── CSS Framework: TailwindCSS 3.x
├── Admin Panel: Multiple Filament 4.x Panels
├── Real-time: Livewire Components
├── Chat: Enhanced Chatify Package
└── Mobile: Responsive Design (Future: Flutter App)
```

### Multi-Panel UI Architecture

#### Role-Based Interface Strategy
```
الفلسفة: كل دور يحصل على واجهة مصممة خصيصاً لاحتياجاته

Power Users (Filament Panels):
├── Super-Admin Panel (/admin - Global Domain)
├── Academy Admin Panel (/{academy}/panel)
├── Teacher Panel (/{academy}/teacher-panel)
└── Supervisor Panel (/{academy}/supervisor-panel)

End Users (Blade + Livewire):
├── Student Area (/{academy}/student)
└── Parent Area (/{academy}/parent)
```

#### Panel Routing Structure
```php
// Global Super-Admin (Main Domain)
Route::domain(config('app.domain'))->group(function () {
    Filament::panel('admin')->path('/admin');
});

// Tenant-Scoped Panels
Route::domain('{academy}.'.config('app.domain'))
    ->middleware(['tenant'])
    ->group(function () {
        // Filament Panels for Power Users
        Filament::panel('academy')->path('/panel');
        Filament::panel('teacher')->path('/teacher-panel');
        Filament::panel('supervisor')->path('/supervisor-panel');
        
        // Simple Areas for End Users
        Route::livewire('/student', StudentDashboard::class);
        Route::livewire('/parent', ParentDashboard::class);
    });
```

### Third-Party Integrations
```
Payment Gateways:
├── Paymob (Egypt/MENA Region)
└── Tap Payments (GCC Region)

Communication:
├── Google Calendar API (Meet Links)
├── FCM (Push Notifications)
├── Email Service (Configurable Provider)
└── WhatsApp Business API (Future)

Media & Storage:
├── DigitalOcean Spaces
├── Laravel Media Library
└── Signed URL Generation
```

## 3. Database Schema Overview

### Core Multi-Tenancy Tables
```sql
-- Tenants (Academies)
tenants: id, name, subdomain, settings, created_at
tenant_settings: tenant_id, key, value

-- All other tables include tenant_id for isolation
users: id, tenant_id, name, email, role_type, ...
```

### User Management Schema
```sql
users: id, tenant_id, name, email, password, role_type, status
user_profiles: user_id, avatar, bio, certifications, subjects
teacher_availability: teacher_id, day_of_week, start_time, end_time
supervisor_assignments: supervisor_id, entity_type, entity_id
```

### Academic Content Schema
```sql
subjects: id, tenant_id, name, category, grade_levels
packages: id, tenant_id, name, type, sessions_count, price
subscriptions: id, student_id, package_id, sessions_remaining, expires_at
subscription_addons: id, subscription_id, extra_sessions, price, paid

courses: id, tenant_id, type, name, description, price, is_active
course_lessons: id, course_id, title, scheduled_at, meet_link, recording_url
course_enrollments: id, course_id, student_id, enrolled_at, completed_at
```

### Communication Schema
```sql
chat_messages: id, tenant_id, conversation_id, sender_id, message, attachments
chat_conversations: id, tenant_id, type, participants, created_at
chat_groups: id, tenant_id, name, type, entity_id (circle/course)
supervisor_mirrors: id, tenant_id, conversation_id, supervisor_id
```

### Assessment Schema
```sql
quizzes: id, tenant_id, teacher_id, title, max_attempts, pass_threshold
quiz_questions: id, quiz_id, question, points, question_order
quiz_choices: id, question_id, choice, is_correct, choice_order
quiz_attempts: id, quiz_id, student_id, score, passed, attempt_number

homework: id, tenant_id, teacher_id, session_id, instructions, due_date
homework_submissions: id, homework_id, student_id, content, submitted_at, grade
```

### Billing Schema
```sql
invoices: id, tenant_id, user_id, total, currency, status, paid_at
invoice_items: id, invoice_id, description, amount, item_type, item_id
payments: id, tenant_id, invoice_id, gateway, gateway_transaction_id, amount
payment_methods: id, user_id, gateway, token, is_default
```

## 4. Multi-Tenancy Implementation

### Tenant Resolution Flow
```php
1. Request arrives at application
2. TenantFinder extracts subdomain/domain
3. If subdomain exists → Resolve tenant
4. If root domain → Default to "itqan-academy" tenant
5. Set tenant context for all subsequent queries
6. Apply tenant-scoped middleware to all models
```

### Data Isolation Strategy
```php
// Global Scope on all tenant models
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('tenant_id', tenant('id'));
    }
}

// Model trait for automatic tenant assignment
trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::creating(function ($model) {
            $model->tenant_id = tenant('id');
        });
    }
}
```

### Storage Isolation
```php
// Tenant-specific storage paths
Storage::disk('spaces')->path("tenants/{tenant_id}/uploads/...")
Storage::disk('spaces')->path("tenants/{tenant_id}/chat/...")
Storage::disk('spaces')->path("tenants/{tenant_id}/courses/...")
```

## 5. Real-Time Architecture

### WebSocket Channels Structure
```javascript
// Private channels per tenant
`tenant.{tenant_id}.chat.{conversation_id}`
`tenant.{tenant_id}.session.{session_id}`
`tenant.{tenant_id}.notifications.{user_id}`

// Supervisor mirror channels (read-only)
`tenant.{tenant_id}.supervisor.{conversation_id}`

// Global system channels
`system.maintenance`
`system.announcements`
```

### Event Broadcasting Flow
```php
1. User action triggers Laravel Event
2. Event queued for broadcasting
3. Pusher/Soketi receives event
4. WebSocket connection authenticated via tenant context
5. Event broadcasted to appropriate channels
6. Frontend JavaScript listeners update UI
```

## 6. Security Architecture

### Authentication & Authorization
```php
// Multi-layered security approach
1. Laravel Sanctum for API authentication
2. Spatie Permission for role-based access
3. Filament Shield for admin panel security
4. Custom policies for cross-tenant restrictions
```

### Data Protection Measures
```php
// Tenant isolation checks
Gate::define('access-tenant', function ($user, $tenant_id) {
    return $user->tenant_id === $tenant_id;
});

// Encrypted sensitive data
protected $casts = [
    'payment_details' => 'encrypted:array',
    'personal_info' => 'encrypted:json',
];
```

### File Security
```php
// Signed URLs for protected content
Route::get('/secure/{path}', function ($path) {
    abort_unless(
        URL::hasValidSignature(request()),
        401
    );
    return Storage::disk('spaces')->response($path);
})->middleware(['signed'])->name('secure.file');
```

## 7. Performance Optimization

### Caching Strategy
```php
// Multi-level caching
1. Redis: Session data, user permissions, tenant settings
2. Application: Query result caching, view caching
3. CDN: Static assets, public course materials
4. Database: Query optimization, proper indexing
```

### Queue Management
```php
// Queue segregation by priority
'high'    => Session reminders, payment processing
'default' => Notifications, email sending
'low'     => Report generation, analytics
'batch'   => Bulk operations, imports
```

### Database Optimization
```sql
-- Strategic indexes for multi-tenant queries
CREATE INDEX idx_tenant_user_role ON users(tenant_id, role_type);
CREATE INDEX idx_tenant_active_sessions ON sessions(tenant_id, status, scheduled_at);
CREATE INDEX idx_conversation_participants ON chat_conversations(tenant_id, participants);
```

## 8. Scalability Considerations

### Horizontal Scaling Points
```
1. Application Servers: Load balancer + multiple Laravel instances
2. Database: Read replicas for reporting queries
3. File Storage: CDN integration for global content delivery
4. Queue Workers: Separate machines for background processing
5. WebSocket Servers: Multiple Soketi instances with Redis adapter
```

### Performance Monitoring
```php
// Key metrics to monitor
- Response time per tenant
- Database query performance
- Queue processing times
- WebSocket connection counts
- File storage usage per tenant
- Payment processing success rates
```

## 9. Deployment Architecture

### Production Environment
```yaml
Load Balancer (nginx)
├── Web Servers (2+ Laravel instances)
├── Queue Workers (dedicated servers)
├── WebSocket Server (Soketi)
├── Database (MySQL Primary + Read Replicas)
├── Cache (Redis Cluster)
└── File Storage (DigitalOcean Spaces + CDN)
```

### CI/CD Pipeline
```yaml
GitHub Actions:
1. Code quality checks (PHPStan, Pint)
2. Automated testing (PHPUnit, Pest)
3. Build assets (npm run build)
4. Deploy to staging
5. Run integration tests
6. Deploy to production (zero-downtime)
```

## 10. Monitoring & Observability

### Application Monitoring
```php
// Integrated monitoring tools
- Laravel Horizon (Queue monitoring)
- Laravel Telescope (Development debugging)
- Spatie Health (System health checks)
- Sentry (Error tracking)
- Custom dashboard (Filament widgets)
```

### Business Intelligence
```php
// Analytics tracking
- User engagement metrics
- Session completion rates
- Revenue per tenant
- Teacher utilization rates
- Student progress analytics
```

This architecture ensures scalability, security, and maintainability while providing the flexibility needed for a multi-tenant educational platform. 