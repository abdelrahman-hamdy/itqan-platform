<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\HasChatIntegration;
use App\Models\Traits\HasNotificationPreferences;
use App\Models\Traits\HasPermissions;
use App\Models\Traits\HasProfiles;
use App\Models\Traits\HasRelationships;
use App\Models\Traits\HasRoles;
use App\Models\Traits\HasTenantContext;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Namu\WireChat\Traits\Chatable;

/**
 * User Model
 *
 * @property int $id
 * @property int|null $academy_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $user_type
 * @property bool $active_status
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $phone_verified_at
 * @property \Carbon\Carbon|null $last_login_at
 * @property string|null $avatar
 * @property \Carbon\Carbon|null $profile_completed_at
 * @property array|null $meeting_preferences
 * @property bool|null $auto_create_meetings
 * @property int|null $meeting_prep_minutes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use Chatable {
        // Resolve conflicts - use our custom implementations
        HasChatIntegration::getCoverUrlAttribute insteadof Chatable;
        HasChatIntegration::getProfileUrlAttribute insteadof Chatable;
        HasChatIntegration::getDisplayNameAttribute insteadof Chatable;
        HasPermissions::canCreateGroups insteadof Chatable;
        HasPermissions::canCreateChats insteadof Chatable;
    }
    use SoftDeletes;

    // Custom traits for organized functionality
    use HasRoles;
    use HasProfiles;
    use HasTenantContext;
    use HasNotificationPreferences;
    use HasPermissions;
    use HasRelationships;
    use HasChatIntegration;

    /**
     * Boot method to add observers
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            // Automatically create profile based on user_type
            // Skip teachers and supervisors as they are handled manually during registration
            if ($user->user_type && $user->academy_id && ! in_array($user->user_type, ['quran_teacher', 'academic_teacher', 'supervisor'])) {
                try {
                    $user->createProfile();
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    // Profile already exists, likely from parallel test execution - ignore
                    if (!app()->environment('testing')) {
                        throw $e;
                    }
                }
            }
        });
    }

    /**
     * Boot method to add global scopes
     *
     * NOTE: TenantScope is intentionally NOT applied to the User model.
     *
     * Reasons:
     * 1. Authentication: Users must be loaded before academy context is available
     *    (the user is needed to determine which academy they belong to)
     * 2. Super Admin Access: Super admins need to access users across all academies
     *    for administrative purposes
     * 3. Parent-Child Relationships: Parents may have children in different academies
     *
     * Tenant isolation for User queries is handled at the application level:
     * - TenantMiddleware sets current academy context via AcademyContextService
     * - Controllers/Services should explicitly filter by academy_id when needed
     * - Use User::where('academy_id', AcademyContextService::getCurrentAcademyId())
     *
     * For other models, use: static::addGlobalScope(new TenantScope);
     *
     * @see \App\Models\Scopes\TenantScope
     * @see \App\Http\Middleware\TenantMiddleware
     * @see \App\Services\AcademyContextService
     */
    protected static function booted(): void
    {
        // No global scope - see docblock above for reasons
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academy_id',
        // 'name' is a generated column (computed from first_name + last_name) - do not include
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'user_type',
        // 'status', // ← REMOVED - using active_status only
        // 'role' field removed - using user_type instead
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'avatar',
        'profile_completed_at',
        'active_status',
        // SECURITY: Removed phone_verification_token, password_reset_token, and remember_token
        // These are sensitive security tokens that should NEVER be mass-assignable
        // They should only be set programmatically via direct attribute assignment

        // Meeting preferences (non-Google)
        'meeting_preferences',
        'auto_create_meetings',
        'meeting_prep_minutes',

        // Teacher preferences
        'teacher_auto_record',
        'teacher_default_duration',
        'teacher_meeting_prep_minutes',
        'teacher_send_reminders',
        'teacher_reminder_times',
        'allow_calendar_conflicts',
        'calendar_visibility',
        'notify_on_student_join',
        'notify_on_session_end',
        'notification_method',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'phone_verification_token',
        'password_reset_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'password' => 'hashed',
            'active_status' => 'boolean',

            // Meeting preferences (non-Google)
            'meeting_preferences' => 'array',
            'auto_create_meetings' => 'boolean',
            'meeting_prep_minutes' => 'integer',

            // Teacher preferences
            'teacher_auto_record' => 'boolean',
            'teacher_default_duration' => 'integer',
            'teacher_meeting_prep_minutes' => 'integer',
            'teacher_send_reminders' => 'boolean',
            'teacher_reminder_times' => 'array',
            'allow_calendar_conflicts' => 'boolean',
            'notify_on_student_join' => 'boolean',
            'notify_on_session_end' => 'boolean',
        ];
    }
}
