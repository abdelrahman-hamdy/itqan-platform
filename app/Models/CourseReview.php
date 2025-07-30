<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'course_id',
        'student_id',
        'rating',
        'title',
        'comment',
        'is_helpful',
        'helpful_votes',
        'is_verified_purchase',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_helpful' => 'boolean',
        'helpful_votes' => 'integer',
        'is_verified_purchase' => 'boolean',
    ];

    protected $attributes = [
        'rating' => 5,
        'is_helpful' => false,
        'helpful_votes' => 0,
        'is_verified_purchase' => false,
        'status' => 'approved',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class, 'course_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeVerifiedPurchases($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    // Accessors
    public function getRatingStarsAttribute(): string
    {
        return str_repeat('⭐', $this->rating);
    }

    public function getStatusInArabicAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'في الانتظار',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
            default => 'غير محدد',
        };
    }
} 