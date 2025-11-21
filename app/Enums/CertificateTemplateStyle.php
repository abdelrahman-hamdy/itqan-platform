<?php

namespace App\Enums;

enum CertificateTemplateStyle: string
{
    case MODERN = 'modern';
    case CLASSIC = 'classic';
    case ELEGANT = 'elegant';

    /**
     * Get Arabic label for the template style
     */
    public function label(): string
    {
        return match($this) {
            self::MODERN => 'عصري',
            self::CLASSIC => 'كلاسيكي',
            self::ELEGANT => 'أنيق',
        };
    }

    /**
     * Get English label for the template style
     */
    public function labelEn(): string
    {
        return match($this) {
            self::MODERN => 'Modern',
            self::CLASSIC => 'Classic',
            self::ELEGANT => 'Elegant',
        };
    }

    /**
     * Get description for the template style
     */
    public function description(): string
    {
        return match($this) {
            self::MODERN => 'تصميم عصري بسيط ونظيف بألوان زرقاء',
            self::CLASSIC => 'تصميم كلاسيكي تقليدي مع حدود رسمية',
            self::ELEGANT => 'تصميم أنيق مع زخارف ذهبية راقية',
        };
    }

    /**
     * Get primary color for the template style
     */
    public function primaryColor(): string
    {
        return match($this) {
            self::MODERN => '#3B82F6', // Blue
            self::CLASSIC => '#1F2937', // Gray-800
            self::ELEGANT => '#D97706', // Amber-600
        };
    }

    /**
     * Get secondary color for the template style
     */
    public function secondaryColor(): string
    {
        return match($this) {
            self::MODERN => '#10B981', // Emerald-500
            self::CLASSIC => '#6B7280', // Gray-500
            self::ELEGANT => '#92400E', // Amber-800
        };
    }

    /**
     * Get icon for the template style
     */
    public function icon(): string
    {
        return match($this) {
            self::MODERN => 'ri-layout-grid-line',
            self::CLASSIC => 'ri-file-text-line',
            self::ELEGANT => 'ri-medal-line',
        };
    }

    /**
     * Get all template style values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all template style options for dropdown
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($case) => [
            $case->value => $case->label()
        ])->toArray();
    }

    /**
     * Get template view path
     */
    public function viewPath(): string
    {
        return "pdf.certificates.{$this->value}";
    }
}
