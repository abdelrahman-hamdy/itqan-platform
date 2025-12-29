# User Model Trait Architecture

## Visual Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        User Model                            │
│                      (192 lines)                             │
│                                                              │
│  Core Configuration:                                         │
│  • $fillable, $hidden, casts()                              │
│  • boot() - auto-create profiles                            │
│  • booted() - global scopes                                 │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ uses
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                     Laravel Traits                           │
├─────────────────────────────────────────────────────────────┤
│  • HasFactory        - Model factories                       │
│  • Notifiable        - Notification support                  │
│  • HasApiTokens      - Sanctum authentication               │
│  • Chatable          - WireChat integration                 │
│  • SoftDeletes       - Soft delete functionality            │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ extends with
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Custom Traits (7)                         │
└─────────────────────────────────────────────────────────────┘
           │              │              │              │
           ▼              ▼              ▼              ▼
    ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐
    │ HasRoles  │  │HasProfiles│  │HasTenant  │  │HasNotif   │
    │ (167 L)   │  │ (258 L)   │  │Context    │  │Prefs      │
    │           │  │           │  │ (65 L)    │  │ (54 L)    │
    └───────────┘  └───────────┘  └───────────┘  └───────────┘
           │              │              │              │
           ▼              ▼              ▼              ▼
    ┌───────────┐  ┌───────────┐  ┌───────────┐
    │HasPermiss │  │HasRelatio │  │ HasChat   │
    │ions       │  │nships     │  │Integration│
    │ (63 L)    │  │ (118 L)   │  │ (143 L)   │
    └───────────┘  └───────────┘  └───────────┘
```

## Trait Responsibilities Map

### HasRoles (Role Management)
```
┌──────────────────────────────────────┐
│         Role Checking                │
├──────────────────────────────────────┤
│ • isStudent()                        │
│ • isTeacher()                        │
│ • isQuranTeacher()                   │
│ • isAcademicTeacher()                │
│ • isAdmin()                          │
│ • isSuperAdmin()                     │
│ • isParent()                         │
│ • isSupervisor()                     │
│ • isStaff()                          │
│ • isEndUser()                        │
├──────────────────────────────────────┤
│         Role Constants               │
├──────────────────────────────────────┤
│ • ROLE_SUPER_ADMIN                   │
│ • ROLE_ACADEMY_ADMIN                 │
│ • ROLE_QURAN_TEACHER                 │
│ • ROLE_ACADEMIC_TEACHER              │
│ • ROLE_SUPERVISOR                    │
│ • ROLE_STUDENT                       │
│ • ROLE_PARENT                        │
├──────────────────────────────────────┤
│         Scopes & Utilities           │
├──────────────────────────────────────┤
│ • scopeOfType()                      │
│ • scopeDashboardUsers()              │
│ • scopeEndUsers()                    │
│ • canAccessDashboard()               │
│ • getDashboardRoute()                │
│ • getUserTypeLabel()                 │
└──────────────────────────────────────┘
```

### HasProfiles (Profile Management)
```
┌──────────────────────────────────────┐
│       Profile Relationships          │
├──────────────────────────────────────┤
│ • getProfile()                       │
│ • quranTeacherProfile()              │
│ • academicTeacherProfile()           │
│ • studentProfile()                   │
│ • studentProfileUnscoped()           │
│ • parentProfile()                    │
│ • supervisorProfile()                │
│ • subjects()                         │
├──────────────────────────────────────┤
│       Profile Management             │
├──────────────────────────────────────┤
│ • createProfile()                    │
│ • hasCompletedProfile()              │
│ • getNameAttribute()                 │
├──────────────────────────────────────┤
│       Legacy Chatify                 │
├──────────────────────────────────────┤
│ • getChatifyName()                   │
│ • getChatifyAvatar()                 │
│ • getChatifyInfo()                   │
└──────────────────────────────────────┘
```

### HasTenantContext (Multi-tenancy)
```
┌──────────────────────────────────────┐
│      Filament Tenancy                │
├──────────────────────────────────────┤
│ • getTenants()                       │
│ • canAccessTenant()                  │
├──────────────────────────────────────┤
│      Academy Management              │
├──────────────────────────────────────┤
│ • academy() - relationship           │
│ • scopeForAcademy()                  │
└──────────────────────────────────────┘
```

### HasNotificationPreferences (Status)
```
┌──────────────────────────────────────┐
│       Verification Status            │
├──────────────────────────────────────┤
│ • hasVerifiedEmail()                 │
│ • hasVerifiedPhone()                 │
│ • isActive()                         │
├──────────────────────────────────────┤
│       Query Scopes                   │
├──────────────────────────────────────┤
│ • scopeActive()                      │
│ • scopeProfileCompleted()            │
│ • scopeEmailVerified()               │
└──────────────────────────────────────┘
```

### HasPermissions (Authorization)
```
┌──────────────────────────────────────┐
│      Filament Panel Access           │
├──────────────────────────────────────┤
│ • canAccessPanel()                   │
│   - admin panel                      │
│   - academy panel                    │
│   - teacher panel                    │
│   - academic-teacher panel           │
│   - supervisor panel                 │
├──────────────────────────────────────┤
│      Chat Permissions                │
├──────────────────────────────────────┤
│ • canCreateGroups()                  │
│ • canCreateChats()                   │
└──────────────────────────────────────┘
```

### HasRelationships (Eloquent)
```
┌──────────────────────────────────────┐
│      Family Relationships            │
├──────────────────────────────────────┤
│ • children()                         │
│ • parent()                           │
├──────────────────────────────────────┤
│      Course Enrollments              │
├──────────────────────────────────────┤
│ • quranCircles()                     │
│ • quranIndividualCircles()           │
│ • interactiveCourseEnrollments()     │
│ • recordedCourseEnrollments()        │
├──────────────────────────────────────┤
│      Chat Relationships              │
├──────────────────────────────────────┤
│ • ownedChatGroups()                  │
│ • chatGroups()                       │
│ • chatGroupMemberships()             │
├──────────────────────────────────────┤
│      Session Tracking                │
├──────────────────────────────────────┤
│ • sessions()                         │
└──────────────────────────────────────┘
```

### HasChatIntegration (WireChat)
```
┌──────────────────────────────────────┐
│      WireChat Interface              │
├──────────────────────────────────────┤
│ • displayName()                      │
│ • getDisplayNameAttribute()          │
│ • getCoverUrlAttribute()             │
│ • getProfileUrlAttribute()           │
├──────────────────────────────────────┤
│      LiveKit Integration             │
├──────────────────────────────────────┤
│ • getIdentifier()                    │
├──────────────────────────────────────┤
│      Conversation Management         │
├──────────────────────────────────────┤
│ • getOrCreatePrivateConversation()   │
└──────────────────────────────────────┘
```

## Trait Conflict Resolution

The User model implements WireChat's `Chatable` trait which provides default implementations for several methods. Our custom traits override these with context-aware implementations:

```php
use Chatable {
    // Override Chatable defaults with our implementations
    HasChatIntegration::getCoverUrlAttribute insteadof Chatable;
    HasChatIntegration::getProfileUrlAttribute insteadof Chatable;
    HasChatIntegration::getDisplayNameAttribute insteadof Chatable;
    HasPermissions::canCreateGroups insteadof Chatable;
    HasPermissions::canCreateChats insteadof Chatable;
}
```

### Why Override?

1. **getCoverUrlAttribute**: Our version checks profile avatars based on user_type
2. **getProfileUrlAttribute**: Our version generates role-specific profile URLs
3. **getDisplayNameAttribute**: Our version uses profile data for more accurate names
4. **canCreateGroups**: Our version implements academy-specific permissions
5. **canCreateChats**: Our version checks active_status properly

## Method Count Breakdown

```
Original User Model:
├── Total Methods: 61
└── Total Lines: 980

Refactored Distribution:
├── User.php: 2 methods (boot, booted) - 192 lines
├── HasRoles: 16 methods - 167 lines
├── HasProfiles: 14 methods - 258 lines
├── HasTenantContext: 4 methods - 65 lines
├── HasNotificationPreferences: 6 methods - 54 lines
├── HasPermissions: 3 methods - 63 lines
├── HasRelationships: 10 methods - 118 lines
└── HasChatIntegration: 6 methods - 143 lines

Total: 61 methods preserved
Total: 1,060 lines (including traits)
User Model: 192 lines (80% reduction)
```

## Usage Examples

### Role Checking
```php
$user = User::find(1);

if ($user->isTeacher()) {
    // Teacher-specific logic
}

if ($user->isSuperAdmin()) {
    // Super admin access
}

$teachers = User::ofType('quran_teacher')->get();
```

### Profile Access
```php
$user = User::find(1);

// Get profile based on user type
$profile = $user->getProfile();

// Direct profile access
$studentProfile = $user->studentProfile;
$teacherProfile = $user->quranTeacherProfile;

// Check completion
if ($user->hasCompletedProfile()) {
    // Profile is complete
}
```

### Tenant Context
```php
// Get user's academy
$academy = $user->academy;

// Check tenant access
if ($user->canAccessTenant($academy)) {
    // User can access this academy
}

// Get all accessible tenants
$tenants = $user->getTenants($panel);
```

### Permissions
```php
// Panel access
if ($user->canAccessPanel($panel)) {
    // User can access this Filament panel
}

// Chat permissions
if ($user->canCreateChats()) {
    // User can create chat conversations
}
```

## File Structure

```
app/Models/
├── User.php (192 lines) ← Main model
└── Traits/
    ├── HasRoles.php (167 lines)
    ├── HasProfiles.php (258 lines)
    ├── HasTenantContext.php (65 lines)
    ├── HasNotificationPreferences.php (54 lines)
    ├── HasPermissions.php (63 lines)
    ├── HasRelationships.php (118 lines)
    └── HasChatIntegration.php (143 lines)
```

## Benefits Summary

1. **Maintainability**: 80% reduction in User.php size
2. **Organization**: Related methods grouped by responsibility
3. **Reusability**: Traits can be used in other models
4. **Testability**: Each trait can be tested independently
5. **Documentation**: Self-documenting code structure
6. **Backwards Compatible**: No breaking changes

## Next Steps

1. Add PHPDoc blocks to all trait methods
2. Create unit tests for each trait
3. Consider extracting more functionality if needed
4. Document patterns in CLAUDE.md
