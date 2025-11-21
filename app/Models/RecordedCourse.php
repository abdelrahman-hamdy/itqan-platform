<?php

namespace App\Models;

use App\Enums\CertificateTemplateStyle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RecordedCourse extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'subject_id',
        'grade_level_id',
        'title',
        'title_en',
        'description',
        'description_en',
        'certificate_template_text',
        'certificate_template_style',
        'course_code',
        'thumbnail_url',
        'duration_hours',
        'price',
        'discount_price',
        'is_published',
        'enrollment_deadline',
        'prerequisites',
        'learning_outcomes',
        'course_materials',
        'materials',
        'total_sections',
        'total_duration_minutes',
        'avg_rating',
        'total_reviews',
        'total_enrollments',
        'difficulty_level',
        'category',
        'tags',
        'meta_description',
        'published_at',
        'created_by',
        'updated_by',
        'notes',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'avg_rating' => 'decimal:1',
        'prerequisites' => 'array',
        'learning_outcomes' => 'array',
        'course_materials' => 'array',
        'materials' => 'array',
        'tags' => 'array',
        'enrollment_deadline' => 'datetime',
        'published_at' => 'datetime',
        'certificate_template_style' => CertificateTemplateStyle::class,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
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

    public function enrolledStudents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_subscriptions', 'recorded_course_id', 'student_id')
            ->wherePivot('status', 'active')
            ->withPivot(['enrolled_at', 'progress_percentage', 'status'])
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class, 'course_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    public function scopeByDifficultyLevel($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getIsFreeAttribute(): bool
    {
        return $this->price == 0;
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->is_free) {
            return 'مجاني';
        }

        // Get currency from academy or use default SAR
        $currency = $this->academy?->currency ?? 'SAR';

        // Format price with Arabic numerals
        $formattedPrice = number_format($this->price, 0);
        $formattedDiscountPrice = $this->discount_price ? number_format($this->discount_price, 0) : null;

        if ($this->discount_price && $this->discount_price < $this->price) {
            return $formattedDiscountPrice.' '.$currency.' <span class="line-through text-gray-500 text-sm">'.$formattedPrice.' '.$currency.'</span>';
        }

        return $formattedPrice.' '.$currency;
    }

    public function getTotalLessonsAttribute(): int
    {
        return $this->lessons()->where('is_published', true)->count();
    }

    public function getProgressPercentageAttribute(): float
    {
        $totalLessons = $this->total_lessons;
        if ($totalLessons == 0) {
            return 0;
        }

        $completedLessons = $this->lessons()
            ->whereHas('progress', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('is_completed', true);
            })
            ->count();

        return ($completedLessons / $totalLessons) * 100;
    }

    public function getDurationFormattedAttribute(): string
    {
        $hours = floor($this->total_duration_minutes / 60);
        $minutes = $this->total_duration_minutes % 60;

        if ($hours > 0) {
            return $hours.' ساعة'.($minutes > 0 ? ' و '.$minutes.' دقيقة' : '');
        }

        return $minutes.' دقيقة';
    }

    public function getEnrollmentStatusAttribute(): string
    {
        if ($this->enrollment_deadline && now() > $this->enrollment_deadline) {
            return 'انتهى التسجيل';
        }

        if (! $this->is_published) {
            return 'غير منشور';
        }

        return 'متاح للتسجيل';
    }

    // Methods
    public function updateStats(): void
    {
        $this->update([
            'total_sections' => $this->sections()->count(),
            'total_duration_minutes' => $this->lessons()->sum('duration_minutes') ?? 0,
            'total_enrollments' => $this->enrollments()->where('status', 'active')->count(),
            'avg_rating' => $this->reviews()->approved()->avg('rating') ?? 0,
            'total_reviews' => $this->reviews()->approved()->count(),
        ]);
    }

    public function canEnroll(): bool
    {
        return $this->is_published
            && (! $this->enrollment_deadline || now() <= $this->enrollment_deadline);
    }

    public function isEnrolledBy(User $user): bool
    {
        return $this->enrollments()
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnails')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('materials')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'text/plain',
                'text/csv',
                'image/jpeg',
                'image/png',
                'image/webp',
            ]);

        $this->addMediaCollection('videos')
            ->singleFile()
            ->acceptsMimeTypes([
                'video/mp4',
                'video/mpeg',
                'video/quicktime',
                'video/x-msvideo',
                'video/webm',
                'video/mov',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Register conversions if needed
    }

    public function getMediaConversionUrls(string $collection = 'default'): array
    {
        return $this->getMedia($collection)
            ->map(fn ($media) => $media->getUrl())
            ->toArray();
    }

    protected static function booted(): void
    {
        static::creating(function (RecordedCourse $course) {
            // Auto-generate course code if not provided
            if (empty($course->course_code)) {
                $course->course_code = 'RC-'.strtoupper(substr($course->title ?? 'COURSE', 0, 3)).'-'.time();
            }

            // Set published_at when course is published
            if ($course->is_published && ! $course->published_at) {
                $course->published_at = now();
            }
        });

        static::created(function (RecordedCourse $course) {
            // Create a default section for this course
            $course->sections()->create([
                'title' => 'دروس الكورس',
                'title_en' => 'Course Lessons',
                'description' => 'دروس الكورس الرئيسية',
                'description_en' => 'Main course lessons',
                'is_published' => true,
                'order' => 1,
                'created_by' => auth()->id() ?? 1,
            ]);
        });

        static::updating(function (RecordedCourse $course) {
            // Set published_at when course is published for the first time
            if ($course->is_published && ! $course->published_at && $course->getOriginal('is_published') == false) {
                $course->published_at = now();
            }
        });
    }

    public function getDefaultSectionId(): int
    {
        $defaultSection = $this->sections()->first();

        if (! $defaultSection) {
            $defaultSection = $this->sections()->create([
                'title' => 'دروس الكورس',
                'title_en' => 'Course Lessons',
                'description' => 'دروس الكورس الرئيسية',
                'description_en' => 'Main course lessons',
                'is_published' => true,
                'order' => 1,
                'created_by' => auth()->id() ?? 1,
            ]);
        }

        return $defaultSection->id;
    }
}
