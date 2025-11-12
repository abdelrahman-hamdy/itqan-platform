<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChatGroup extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'type',
        'academy_id',
        'owner_id',
        'avatar',
        'settings',
        'is_active',
        'max_members',
        'quran_circle_id',
        'quran_session_id',
        'academic_session_id',
        'interactive_course_id',
        'recorded_course_id',
    ];
    
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'max_members' => 'integer',
    ];
    
    /**
     * Group type constants
     */
    const TYPE_QURAN_CIRCLE = 'quran_circle';
    const TYPE_INDIVIDUAL_SESSION = 'individual_session';
    const TYPE_ACADEMIC_SESSION = 'academic_session';
    const TYPE_INTERACTIVE_COURSE = 'interactive_course';
    const TYPE_RECORDED_COURSE = 'recorded_course';
    const TYPE_ACADEMY_ANNOUNCEMENT = 'academy_announcement';
    
    /**
     * Member role constants
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_MEMBER = 'member';
    const ROLE_OBSERVER = 'observer';
    
    /**
     * Get the academy this group belongs to
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }
    
    /**
     * Get the owner of the group
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    /**
     * Get the members of the group
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_group_members', 'group_id', 'user_id')
                    ->withPivot(['role', 'can_send_messages', 'is_muted', 'joined_at', 'last_read_at', 'unread_count'])
                    ->withTimestamps();
    }
    
    /**
     * Get the group memberships
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ChatGroupMember::class, 'group_id');
    }
    
    /**
     * Get messages in this group
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'group_id');
    }
    
    /**
     * Get the related Quran circle
     */
    public function quranCircle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }
    
    /**
     * Get the related Quran session
     */
    public function quranSession(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class);
    }
    
    /**
     * Get the related academic session
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }
    
    /**
     * Get the related interactive course
     */
    public function interactiveCourse(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourse::class);
    }
    
    /**
     * Get the related recorded course
     */
    public function recordedCourse(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class);
    }
    
    /**
     * Check if a user is a member of this group
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }
    
    /**
     * Check if a user can send messages in this group
     */
    public function canSendMessage(User $user): bool
    {
        $membership = $this->members()
                          ->where('user_id', $user->id)
                          ->first();
        
        if (!$membership) {
            return false;
        }
        
        return $membership->pivot->can_send_messages && !$membership->pivot->is_muted;
    }
    
    /**
     * Add a member to the group
     */
    public function addMember(User $user, string $role = self::ROLE_MEMBER, bool $canSendMessages = true): void
    {
        $this->members()->attach($user->id, [
            'role' => $role,
            'can_send_messages' => $canSendMessages,
            'joined_at' => now(),
        ]);
    }
    
    /**
     * Remove a member from the group
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }
    
    /**
     * Get the group's display name in Arabic
     */
    public function getDisplayName(): string
    {
        $typeLabels = [
            self::TYPE_QURAN_CIRCLE => 'حلقة قرآن',
            self::TYPE_INDIVIDUAL_SESSION => 'جلسة فردية',
            self::TYPE_ACADEMIC_SESSION => 'جلسة أكاديمية',
            self::TYPE_INTERACTIVE_COURSE => 'دورة تفاعلية',
            self::TYPE_RECORDED_COURSE => 'دورة مسجلة',
            self::TYPE_ACADEMY_ANNOUNCEMENT => 'إعلان الأكاديمية',
        ];
        
        $prefix = $typeLabels[$this->type] ?? '';
        
        return $prefix ? $prefix . ': ' . $this->name : $this->name;
    }
    
    /**
     * Automatically create chat groups for educational entities
     */
    public static function createForEntity($entity, string $type): self
    {
        $settings = [];
        $maxMembers = null;
        $name = '';
        $description = '';
        $ownerId = null;
        $academyId = null;
        
        switch ($type) {
            case self::TYPE_QURAN_CIRCLE:
                $name = $entity->name;
                $description = 'مجموعة محادثة لحلقة ' . $entity->name;
                $ownerId = $entity->teacher_id;
                $academyId = $entity->academy_id;
                $maxMembers = $entity->max_students;
                break;
                
            case self::TYPE_INDIVIDUAL_SESSION:
                $name = 'جلسة فردية - ' . $entity->student->name;
                $description = 'محادثة الجلسة الفردية';
                $ownerId = $entity->teacher_id;
                $academyId = $entity->academy_id;
                $maxMembers = 3; // Teacher, Student, Parent (optional)
                break;
                
            case self::TYPE_ACADEMIC_SESSION:
                $name = 'جلسة أكاديمية - ' . $entity->subject->name;
                $description = 'محادثة الجلسة الأكاديمية';
                $ownerId = $entity->teacher_id;
                $academyId = $entity->academy_id;
                $maxMembers = 2; // Teacher and Student
                break;
                
            case self::TYPE_INTERACTIVE_COURSE:
                $name = $entity->title;
                $description = 'مجموعة محادثة للدورة التفاعلية';
                $ownerId = $entity->instructor_id;
                $academyId = $entity->academy_id;
                break;
                
            case self::TYPE_RECORDED_COURSE:
                $name = $entity->title;
                $description = 'منتدى نقاش الدورة المسجلة';
                $ownerId = $entity->instructor_id ?? $entity->academy->admin_id;
                $academyId = $entity->academy_id;
                break;
        }
        
        $group = self::create([
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'academy_id' => $academyId,
            'owner_id' => $ownerId,
            'settings' => $settings,
            'max_members' => $maxMembers,
            $type . '_id' => $entity->id,
        ]);
        
        // Add owner as admin
        if ($ownerId) {
            $group->addMember(User::find($ownerId), self::ROLE_ADMIN);
        }
        
        return $group;
    }
}
