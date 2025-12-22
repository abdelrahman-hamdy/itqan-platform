<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatGroup extends Model
{
    use HasFactory, SoftDeletes;

    // Group Types
    const TYPE_QURAN_CIRCLE = 'quran_circle';
    const TYPE_INDIVIDUAL_SESSION = 'individual_session';
    const TYPE_ACADEMIC_SESSION = 'academic_session';
    const TYPE_INTERACTIVE_COURSE = 'interactive_course';
    const TYPE_RECORDED_COURSE = 'recorded_course';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_CUSTOM = 'custom';

    // Member Roles
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_MEMBER = 'member';

    protected $fillable = [
        'academy_id',
        'name',
        'type',
        'owner_id',
        'quran_circle_id',
        'quran_session_id',
        'academic_session_id',
        'interactive_course_id',
        'recorded_course_id',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
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
    public function members()
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
}
