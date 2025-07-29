<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecordedCourse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'instructor_id',
        'subject_id',
        'grade_level_id',
        'title',
        'title_en',
        'description',
        'description_en',
        'course_code',
        'thumbnail_url',
        'trailer_video_url',
        'level',
        'duration_hours',
        'language',
        'price',
        'discount_price',
        'currency',
        'is_free',
        'is_published',
        'is_featured',
        'enrollment_deadline',
        'completion_certificate',
        'prerequisites',
        'learning_outcomes',
        'course_materials',
        'total_sections',
        'total_lessons',
        'total_duration_minutes',
        'avg_rating',
        'total_reviews',
        'total_enrollments',
        'difficulty_level',
        'category',
        'tags',
        'meta_keywords',
        'meta_description',
        'status',
        'published_at',
        'created_by',
        'updated_by',
        'notes'
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'completion_certificate' => 'boolean',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'avg_rating' => 'decimal:1',
        'prerequisites' => 'array',
        'learning_outcomes' => 'array',
        'course_materials' => 'array',
        'tags' => 'array',
        'enrollment_deadline' => 'datetime',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacher::class, 'instructor_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicGradeLevel::class, 'grade_level_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class)->orderBy('order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(CourseQuiz::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseSubscription::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_subscriptions')
            ->withPivot(['enrolled_at', 'progress_percentage', 'completed_at', 'certificate_issued'])
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        if ($this->is_free) {
            return 'مجاني';
        }
        
        if ($this->discount_price && $this->discount_price < $this->price) {
            return $this->discount_price . ' ' . $this->currency . ' (خصم من ' . $this->price . ')';
        }
        
        return $this->price . ' ' . $this->currency;
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_lessons == 0) {
            return 0;
        }
        
        return ($this->completed_lessons_count / $this->total_lessons) * 100;
    }

    public function getDurationFormattedAttribute(): string
    {
        $hours = floor($this->total_duration_minutes / 60);
        $minutes = $this->total_duration_minutes % 60;
        
        if ($hours > 0) {
            return $hours . ' ساعة' . ($minutes > 0 ? ' و ' . $minutes . ' دقيقة' : '');
        }
        
        return $minutes . ' دقيقة';
    }

    public function getEnrollmentStatusAttribute(): string
    {
        if ($this->enrollment_deadline && now() > $this->enrollment_deadline) {
            return 'انتهى التسجيل';
        }
        
        if (!$this->is_published) {
            return 'غير منشور';
        }
        
        return 'متاح للتسجيل';
    }

    // Methods
    public function updateStats(): void
    {
        $this->update([
            'total_sections' => $this->sections()->count(),
            'total_lessons' => $this->lessons()->count(),
            'total_duration_minutes' => $this->lessons()->sum('duration_minutes'),
            'total_enrollments' => $this->enrollments()->count(),
            'avg_rating' => $this->reviews()->avg('rating') ?? 0,
            'total_reviews' => $this->reviews()->count(),
        ]);
    }

    public function canEnroll(): bool
    {
        return $this->is_published 
            && $this->status === 'published'
            && (!$this->enrollment_deadline || now() <= $this->enrollment_deadline);
    }

    public function isEnrolledBy(User $user): bool
    {
        return $this->enrollments()
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }
} 