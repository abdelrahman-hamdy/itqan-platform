<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\UserType;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Namu\WireChat\Models\Conversation;

class ChatGroup extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    // Group Types
    const TYPE_QURAN_CIRCLE = 'quran_circle';

    const TYPE_INDIVIDUAL_SESSION = 'individual_session';

    const TYPE_ACADEMIC_SESSION = 'academic_session';

    const TYPE_INTERACTIVE_COURSE = 'interactive_course';

    const TYPE_RECORDED_COURSE = 'recorded_course';

    const TYPE_ANNOUNCEMENT = 'announcement';

    const TYPE_CUSTOM = 'custom';

    const TYPE_SUPERVISED_INDIVIDUAL = 'supervised_individual';

    // Member Roles
    const ROLE_ADMIN = 'admin';

    const ROLE_MODERATOR = 'moderator';

    const ROLE_MEMBER = 'member';

    protected $fillable = [
        'academy_id',
        'name',
        'type',
        'owner_id',
        'supervisor_id',
        'conversation_id',
        'quran_circle_id',
        'quran_session_id',
        'academic_session_id',
        'interactive_course_id',
        'recorded_course_id',
        'quran_individual_circle_id',
        'academic_individual_lesson_id',
        'metadata',
        'is_active',
        'archived_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the academy that owns the chat group
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the owner (creator) of the chat group
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the memberships for the chat group
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ChatGroupMember::class, 'group_id');
    }

    /**
     * Get the members of the chat group
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_group_members', 'group_id', 'user_id')
            ->withPivot('role', 'can_send_messages', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get the associated quran circle
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    /**
     * Get the associated quran session
     */
    public function quranSession(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class);
    }

    /**
     * Get the associated academic session
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    /**
     * Get the associated interactive course
     */
    public function interactiveCourse(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourse::class);
    }

    /**
     * Get the associated recorded course
     */
    public function recordedCourse(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class);
    }

    /**
     * Get the supervisor assigned to this chat group
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the WireChat conversation for this group
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the associated quran individual circle
     */
    public function quranIndividualCircle(): BelongsTo
    {
        return $this->belongsTo(QuranIndividualCircle::class);
    }

    /**
     * Get the associated academic individual lesson
     */
    public function academicIndividualLesson(): BelongsTo
    {
        return $this->belongsTo(AcademicIndividualLesson::class);
    }

    /**
     * Check if the group has a supervisor assigned
     */
    public function hasSupervisorAssigned(): bool
    {
        return ! is_null($this->supervisor_id);
    }

    /**
     * Check if this is a supervised chat (has supervisor or is supervised type)
     */
    public function isSupervisedChat(): bool
    {
        return $this->type === self::TYPE_SUPERVISED_INDIVIDUAL
            || ! is_null($this->supervisor_id);
    }

    /**
     * Check if user is a member of the group
     */
    public function hasMember(User $user): bool
    {
        return $this->memberships()->where('user_id', $user->id)->exists();
    }

    /**
     * Get member's role in the group
     */
    public function getMemberRole(User $user): ?string
    {
        $membership = $this->memberships()->where('user_id', $user->id)->first();

        return $membership ? $membership->role : null;
    }

    /**
     * Check if user is admin of the group
     */
    public function isAdmin(User $user): bool
    {
        return $this->getMemberRole($user) === self::ROLE_ADMIN;
    }

    /**
     * Check if user is moderator of the group
     */
    public function isModerator(User $user): bool
    {
        return $this->getMemberRole($user) === self::ROLE_MODERATOR;
    }

    /**
     * Check if user can send messages in the group
     */
    public function canSendMessages(User $user): bool
    {
        $membership = $this->memberships()->where('user_id', $user->id)->first();

        return $membership && $membership->can_send_messages;
    }

    /**
     * Get the avatar style (color and icon) based on entity type.
     *
     * @return array{color: string, icon: string, bgClass: string, textClass: string}
     */
    public function getGroupAvatarStyle(): array
    {
        return match ($this->type) {
            self::TYPE_QURAN_CIRCLE => [
                'color' => '#10B981',
                'icon' => 'ri-group-line',
                'bgClass' => 'bg-emerald-100 dark:bg-emerald-900',
                'textClass' => 'text-emerald-600 dark:text-emerald-400',
            ],
            self::TYPE_INDIVIDUAL_SESSION,
            self::TYPE_SUPERVISED_INDIVIDUAL => [
                'color' => '#F59E0B',
                'icon' => 'ri-user-voice-line',
                'bgClass' => 'bg-amber-100 dark:bg-amber-900',
                'textClass' => 'text-amber-600 dark:text-amber-400',
            ],
            self::TYPE_INTERACTIVE_COURSE => [
                'color' => '#3B82F6',
                'icon' => 'ri-slideshow-line',
                'bgClass' => 'bg-blue-100 dark:bg-blue-900',
                'textClass' => 'text-blue-600 dark:text-blue-400',
            ],
            self::TYPE_ACADEMIC_SESSION => [
                'color' => '#8B5CF6',
                'icon' => 'ri-book-open-line',
                'bgClass' => 'bg-violet-100 dark:bg-violet-900',
                'textClass' => 'text-violet-600 dark:text-violet-400',
            ],
            default => [
                'color' => '#6B7280',
                'icon' => 'ri-chat-3-line',
                'bgClass' => 'bg-gray-100 dark:bg-gray-700',
                'textClass' => 'text-gray-600 dark:text-gray-400',
            ],
        };
    }

    /**
     * Archive this chat group.
     */
    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    /**
     * Restore this chat group from archive.
     */
    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    /**
     * Check if this chat group is archived.
     */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Check if the given user can archive/unarchive this chat group.
     */
    public function canBeArchivedBy(User $user): bool
    {
        // Admins and supervisors can always archive
        if (in_array($user->user_type, [UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return true;
        }

        // Members of the chat can archive
        return $this->hasMember($user);
    }

    /**
     * Scope to exclude archived chat groups.
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope to only include archived chat groups.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
}
