<?php

namespace App\Enums;

enum CertificateType: string
{
    case RECORDED_COURSE = 'recorded_course';
    case INTERACTIVE_COURSE = 'interactive_course';
    case QURAN_SUBSCRIPTION = 'quran_subscription';
    case ACADEMIC_SUBSCRIPTION = 'academic_subscription';

    /**
     * Get Arabic label for the certificate type
     */
    public function label(): string
    {
        return match($this) {
            self::RECORDED_COURSE => 'دورة مسجلة',
            self::INTERACTIVE_COURSE => 'دورة تفاعلية',
            self::QURAN_SUBSCRIPTION => 'حلقة قرآن',
            self::ACADEMIC_SUBSCRIPTION => 'حصص أكاديمية',
        };
    }

    /**
     * Get English label for the certificate type
     */
    public function labelEn(): string
    {
        return match($this) {
            self::RECORDED_COURSE => 'Recorded Course',
            self::INTERACTIVE_COURSE => 'Interactive Course',
            self::QURAN_SUBSCRIPTION => 'Quran Circle',
            self::ACADEMIC_SUBSCRIPTION => 'Academic Lessons',
        };
    }

    /**
     * Get icon for the certificate type
     */
    public function icon(): string
    {
        return match($this) {
            self::RECORDED_COURSE => 'ri-video-line',
            self::INTERACTIVE_COURSE => 'ri-live-line',
            self::QURAN_SUBSCRIPTION => 'ri-book-open-line',
            self::ACADEMIC_SUBSCRIPTION => 'ri-graduation-cap-line',
        };
    }

    /**
     * Get badge color class for the certificate type
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::RECORDED_COURSE => 'bg-blue-100 text-blue-800',
            self::INTERACTIVE_COURSE => 'bg-purple-100 text-purple-800',
            self::QURAN_SUBSCRIPTION => 'bg-green-100 text-green-800',
            self::ACADEMIC_SUBSCRIPTION => 'bg-orange-100 text-orange-800',
        };
    }

    /**
     * Get all certificate type values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all certificate type options for dropdown
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($case) => [
            $case->value => $case->label()
        ])->toArray();
    }

    /**
     * Check if certificate type is for manual issuance
     */
    public function isManualType(): bool
    {
        return in_array($this, [
            self::QURAN_SUBSCRIPTION,
            self::ACADEMIC_SUBSCRIPTION,
        ]);
    }

    /**
     * Check if certificate type is for automatic issuance
     */
    public function isAutoType(): bool
    {
        return in_array($this, [
            self::RECORDED_COURSE,
            self::INTERACTIVE_COURSE,
        ]);
    }
}
