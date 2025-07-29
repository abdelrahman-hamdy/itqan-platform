<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeachingSession extends Model
{
    use HasFactory;

    protected $table = 'teaching_sessions';

    protected $fillable = [
        'academy_id',
        'course_id',
        'teacher_id',
        'title',
        'description',
        'type',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_minutes',
        'google_event_id',
        'google_meet_url',
        'notes',
        'attendance_taken',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_minutes' => 'integer',
        'attendance_taken' => 'boolean',
    ];

    /**
     * Academy relationship
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Course relationship (optional for individual sessions)
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Teacher relationship
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Students attending this session
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teaching_session_attendances')
                    ->withPivot(['status', 'joined_at', 'left_at', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Session attendances
     */
    public function attendances(): BelongsToMany
    {
        return $this->students();
    }

    /**
     * Scope for upcoming sessions
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled');
    }

    /**
     * Scope for past sessions
     */
    public function scopePast($query)
    {
        return $query->where('scheduled_at', '<', now());
    }

    /**
     * Scope for today's sessions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    /**
     * Scope for sessions by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get session duration in human readable format
     */
    public function getDurationFormattedAttribute()
    {
        $hours = intval($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        
        return $minutes . 'm';
    }

    /**
     * Check if session is live (happening now)
     */
    public function getIsLiveAttribute()
    {
        $now = now();
        return $this->started_at && 
               $this->started_at <= $now && 
               (!$this->ended_at || $this->ended_at >= $now);
    }

    /**
     * Get attendance rate
     */
    public function getAttendanceRateAttribute()
    {
        $totalStudents = $this->students()->count();
        $presentStudents = $this->students()
                               ->wherePivot('status', 'present')
                               ->count();
        
        return $totalStudents > 0 ? ($presentStudents / $totalStudents) * 100 : 0;
    }
}
