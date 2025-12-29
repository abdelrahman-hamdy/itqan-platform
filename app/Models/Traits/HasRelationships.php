<?php

namespace App\Models\Traits;

use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Models\Payment;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasRelationships
{
    /**
     * Parent-child relationships for family accounts
     * Safely re-enabled with proper conditions
     */
    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id')
            ->where('user_type', 'student');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id')
            ->where('user_type', 'parent');
    }

    /**
     * User sessions for tracking
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * Quran circles that this user (student) is enrolled in
     */
    public function quranCircles(): BelongsToMany
    {
        return $this->belongsToMany(QuranCircle::class, 'quran_circle_students', 'student_id', 'circle_id')
            ->withPivot([
                'enrolled_at',
                'status',
                'attendance_count',
                'missed_sessions',
                'makeup_sessions_used',
                'current_level',
                'progress_notes',
                'parent_rating',
                'student_rating',
                'completion_date',
                'certificate_issued',
            ])
            ->withTimestamps();
    }

    /**
     * Individual Quran circles that this user (student) is enrolled in
     */
    public function quranIndividualCircles(): HasMany
    {
        return $this->hasMany(QuranIndividualCircle::class, 'student_id');
    }

    /**
     * Interactive course enrollments for this user (student)
     */
    public function interactiveCourseEnrollments(): HasMany
    {
        return $this->hasMany(InteractiveCourseEnrollment::class, 'student_id');
    }

    /**
     * Recorded course enrollments for this user (student)
     */
    public function recordedCourseEnrollments(): HasMany
    {
        return $this->hasMany(CourseSubscription::class, 'student_id');
    }

    /**
     * Chat Groups Relationships
     */

    /**
     * Get chat groups the user owns
     */
    public function ownedChatGroups(): HasMany
    {
        return $this->hasMany(ChatGroup::class, 'owner_id');
    }

    /**
     * Get chat groups the user is a member of
     */
    public function chatGroups(): BelongsToMany
    {
        return $this->belongsToMany(ChatGroup::class, 'chat_group_members', 'user_id', 'group_id')
                    ->withPivot(['role', 'can_send_messages', 'is_muted', 'joined_at', 'last_read_at', 'unread_count'])
                    ->withTimestamps();
    }

    /**
     * Get chat group memberships
     */
    public function chatGroupMemberships(): HasMany
    {
        return $this->hasMany(ChatGroupMember::class, 'user_id');
    }

    // ========================================
    // SUBSCRIPTION RELATIONSHIPS
    // ========================================

    /**
     * Get Quran subscriptions for this user (student)
     */
    public function quranSubscriptions(): HasMany
    {
        return $this->hasMany(QuranSubscription::class, 'student_id');
    }

    /**
     * Get Academic subscriptions for this user (student)
     */
    public function academicSubscriptions(): HasMany
    {
        return $this->hasMany(AcademicSubscription::class, 'student_id');
    }

    /**
     * Get Course subscriptions for this user (student)
     * Alias for recordedCourseEnrollments for consistency
     */
    public function courseSubscriptions(): HasMany
    {
        return $this->hasMany(CourseSubscription::class, 'student_id');
    }

    // ========================================
    // SESSION RELATIONSHIPS
    // ========================================

    /**
     * Get Quran sessions for this user (as student)
     */
    public function quranSessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'student_id');
    }

    /**
     * Get Academic sessions for this user (as student)
     */
    public function academicSessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class, 'student_id');
    }

    // ========================================
    // PAYMENT RELATIONSHIPS
    // ========================================

    /**
     * Get payments made by this user (polymorphic)
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Get payments directly associated with this user
     */
    public function userPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id');
    }
}
