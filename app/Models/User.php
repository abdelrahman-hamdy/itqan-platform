<?php

namespace App\Models;

use Illuminate\Database\UniqueConstraintViolationException;
use App\Notifications\VerifyEmailNotification;
use Log;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use App\Constants\DefaultAcademy;
use App\Enums\UserType;
use App\Models\Traits\HasChatIntegration;
use App\Models\Traits\HasNotificationPreferences;
use App\Models\Traits\HasPermissions;
use App\Models\Traits\HasProfiles;
use App\Models\Traits\HasRelationships;
use App\Models\Traits\HasRoles;
use App\Models\Traits\HasTenantContext;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $phone_verified_at
 * @property Carbon|null $last_login_at
 * @property string|null $avatar
 * @property Carbon|null $profile_completed_at
 * @property array|null $meeting_preferences
 * @property bool|null $auto_create_meetings
 * @property int|null $meeting_prep_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class User extends Authenticatable implements FilamentUser, HasTenants, MustVerifyEmail
{
    use Chatable {
        // Resolve conflicts - use our custom implementations
        HasChatIntegration::getCoverUrlAttribute insteadof Chatable;
        HasChatIntegration::getProfileUrlAttribute insteadof Chatable;
        HasChatIntegration::getDisplayNameAttribute insteadof Chatable;
        HasPermissions::canCreateGroups insteadof Chatable;
        HasPermissions::canCreateChats insteadof Chatable;
    }
    use HasApiTokens;
    use HasChatIntegration;
    use HasFactory;
    use HasNotificationPreferences;
    use HasPermissions;
    use HasProfiles;
    use HasRelationships;

    // Custom traits for organized functionality
    use HasRoles;
    use HasTenantContext;
    use Notifiable;
    use SoftDeletes;

    /**
     * Boot method to add observers
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate admin_code for admin users
        static::creating(function ($user) {
            if ($user->user_type === UserType::ADMIN->value && empty($user->admin_code)) {
                $academyId = $user->academy_id ?: 1;
                $prefix = 'ADM-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-';

                // Find the highest existing sequence number for this academy
                $maxCode = static::where('admin_code', 'like', $prefix.'%')
                    ->orderByRaw('CAST(SUBSTRING(admin_code, -4) AS UNSIGNED) DESC')
                    ->value('admin_code');

                if ($maxCode) {
                    $sequence = (int) substr($maxCode, -4) + 1;
                } else {
                    $sequence = 1;
                }

                $user->admin_code = $prefix.str_pad($sequence, 4, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($user) {
            // Automatically create profile based on user_type
            // Skip teachers and supervisors as they are handled manually during registration
            if ($user->user_type && $user->academy_id && ! in_array($user->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::SUPERVISOR->value])) {
                try {
                    $user->createProfile();
                } catch (UniqueConstraintViolationException $e) {
                    // Profile already exists, likely from parallel test execution - ignore
                    if (! app()->environment('testing')) {
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
     * Send the email verification notification.
     *
     * Override the default MustVerifyEmail implementation to use our custom notification
     * which includes academy branding and proper subdomain URL generation.
     */
    public function sendEmailVerificationNotification(): void
    {
        $academy = $this->academy;

        if (! $academy) {
            // Fallback to default academy if user doesn't have one
            $academy = Academy::where('subdomain', DefaultAcademy::subdomain())->first();
        }

        if ($academy) {
            try {
                $this->notify(new VerifyEmailNotification($academy));
                Log::info('Verification email sent', [
                    'user_id' => $this->id,
                    'email' => $this->email,
                    'academy' => $academy->name,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to send verification email', [
                    'user_id' => $this->id,
                    'email' => $this->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        } else {
            Log::warning('No academy found for verification email', [
                'user_id' => $this->id,
                'email' => $this->email,
            ]);
        }
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
        'gender', // Used for teacher profiles via API
        'password',
        'user_type',
        'admin_code',
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

        // Localization preferences
        'preferred_locale',
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

    /**
     * Check if this user (as a teacher) has a supervisor assigned.
     * Returns false if user is not a teacher.
     */
    public function hasSupervisor(): bool
    {
        if (! in_array($this->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return false;
        }

        return SupervisorResponsibility::where('responsable_type', self::class)
            ->where('responsable_id', $this->id)
            ->exists();
    }

    /**
     * Get the primary supervisor's User model for this teacher.
     * Returns null if user is not a teacher or has no supervisor.
     */
    public function getPrimarySupervisor(): ?User
    {
        if (! in_array($this->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return null;
        }

        $supervisorProfile = SupervisorProfile::whereHas('responsibilities', function ($query) {
            $query->where('responsable_type', self::class)
                ->where('responsable_id', $this->id);
        })->with('user')->first();

        return $supervisorProfile?->user;
    }

    /**
     * Get all supervisors assigned to this teacher.
     * Returns null if user is not a teacher.
     */
    public function getSupervisors(): ?Collection
    {
        if (! in_array($this->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return null;
        }

        $supervisorProfiles = SupervisorProfile::whereHas('responsibilities', function ($query) {
            $query->where('responsable_type', self::class)
                ->where('responsable_id', $this->id);
        })->with('user')->get();

        return $supervisorProfiles->map(fn ($profile) => $profile->user)->filter();
    }
}
