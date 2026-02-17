<?php

namespace App\Models;

use App\Services\NotificationService;
use App\Enums\NotificationType;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Constants\DefaultAcademy;
use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Certificate extends Model
{
    use HasFactory, HasUuids, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'student_id',
        'teacher_id',
        'certificateable_type',
        'certificateable_id',
        'certificate_number',
        'certificate_type',
        'template_style',
        'certificate_text',
        'issued_at',
        'issued_by',
        'file_path',
        'is_manual',
        'metadata',
    ];

    protected $casts = [
        'certificate_type' => CertificateType::class,
        'template_style' => CertificateTemplateStyle::class,
        'issued_at' => 'datetime',
        'is_manual' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = [
        'download_url',
        'view_url',
    ];

    /**
     * Get the academy that owns the certificate
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the student who received the certificate
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the teacher associated with the certificate (nullable)
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the user who issued the certificate (nullable)
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the parent certificateable model (polymorphic)
     */
    public function certificateable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias for certificateable (handles common typo without 'e')
     */
    public function certificatable(): MorphTo
    {
        return $this->certificateable();
    }

    /**
     * Get the download URL for the certificate
     */
    public function getDownloadUrlAttribute(): string
    {
        $subdomain = $this->academy?->subdomain ?? DefaultAcademy::subdomain();

        return route('student.certificate.download', ['subdomain' => $subdomain, 'certificate' => $this->id]);
    }

    /**
     * Get the view URL for the certificate
     */
    public function getViewUrlAttribute(): string
    {
        $subdomain = $this->academy?->subdomain ?? DefaultAcademy::subdomain();

        return route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $this->id]);
    }

    /**
     * Get the full storage path for the certificate
     */
    public function getFullPathAttribute(): string
    {
        return Storage::path($this->file_path);
    }

    /**
     * Check if certificate file exists
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Get certificate file contents
     */
    public function getFileContents()
    {
        return Storage::get($this->file_path);
    }

    /**
     * Delete certificate file from storage
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::delete($this->file_path);
        }

        return false;
    }

    /**
     * Scope to filter by academy
     */
    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * Scope to filter by student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to filter by certificate type
     */
    public function scopeOfType($query, CertificateType|string $type)
    {
        if ($type instanceof CertificateType) {
            $type = $type->value;
        }

        return $query->where('certificate_type', $type);
    }

    /**
     * Scope to filter by template style
     */
    public function scopeWithStyle($query, CertificateTemplateStyle|string $style)
    {
        if ($style instanceof CertificateTemplateStyle) {
            $style = $style->value;
        }

        return $query->where('template_style', $style);
    }

    /**
     * Scope to get manual certificates only
     */
    public function scopeManual($query)
    {
        return $query->where('is_manual', true);
    }

    /**
     * Scope to get automatic certificates only
     */
    public function scopeAutomatic($query)
    {
        return $query->where('is_manual', false);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Send notification when certificate is issued
        static::created(function ($certificate) {
            $certificate->notifyCertificateIssued();
        });

        // Clean up file when certificate is deleted
        static::deleting(function ($certificate) {
            $certificate->deleteFile();
        });
    }

    /**
     * Send notification to student when certificate is issued
     */
    public function notifyCertificateIssued(): void
    {
        try {
            $student = $this->student;
            if (! $student) {
                return;
            }

            $notificationService = app(NotificationService::class);

            // Determine certificate context for notification
            $certificateContext = 'الشهادة';
            $actionUrl = $this->view_url;

            if ($this->certificateable) {
                if ($this->certificateable instanceof QuranCircle) {
                    $certificateContext = 'حلقة '.$this->certificateable->name;
                } elseif ($this->certificateable instanceof InteractiveCourse) {
                    $certificateContext = 'دورة '.$this->certificateable->title;
                } elseif ($this->certificateable instanceof RecordedCourse) {
                    $certificateContext = 'دورة '.$this->certificateable->title;
                }
            }

            // Get teacher name
            $teacherName = $this->teacher?->full_name ?? 'المعلم';

            $notificationService->send(
                $student,
                NotificationType::CERTIFICATE_EARNED,
                [
                    'teacher_name' => $teacherName,
                    'certificate_type' => $this->certificate_type->value ?? 'certificate',
                    'certificate_context' => $certificateContext,
                    'issued_at' => $this->issued_at?->format('Y-m-d'),
                ],
                $actionUrl,
                [
                    'certificate_id' => $this->id,
                    'certificateable_type' => $this->certificateable_type,
                    'certificateable_id' => $this->certificateable_id,
                ],
                true,  // Mark as important
                'heroicon-o-trophy',  // Custom certificate icon (trophy)
                'orange'  // Custom orange color
            );

            // Also notify parent if exists
            if ($student->studentProfile && $student->studentProfile->parent) {
                $notificationService->send(
                    $student->studentProfile->parent->user,
                    NotificationType::CERTIFICATE_EARNED,
                    [
                        'teacher_name' => $teacherName,
                        'certificate_type' => $this->certificate_type->value ?? 'certificate',
                        'certificate_context' => $certificateContext,
                        'student_name' => $student->full_name,
                        'issued_at' => $this->issued_at?->format('Y-m-d'),
                    ],
                    $actionUrl,
                    [
                        'certificate_id' => $this->id,
                        'certificateable_type' => $this->certificateable_type,
                        'certificateable_id' => $this->certificateable_id,
                    ],
                    true,  // Mark as important
                    'heroicon-o-trophy',  // Custom certificate icon (trophy)
                    'orange'  // Custom orange color
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send certificate notification', [
                'certificate_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
