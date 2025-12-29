<?php

namespace App\Enums;

/**
 * Certificate Template Style Enum
 *
 * Defines available certificate template designs.
 * Each template has unique styling, colors, and layout.
 *
 * @see \App\Models\Certificate
 * @see \App\Services\CertificateService
 */
enum CertificateTemplateStyle: string
{
    case TEMPLATE_1 = 'template_1';
    case TEMPLATE_2 = 'template_2';
    case TEMPLATE_3 = 'template_3';
    case TEMPLATE_4 = 'template_4';
    case TEMPLATE_5 = 'template_5';
    case TEMPLATE_6 = 'template_6';
    case TEMPLATE_7 = 'template_7';
    case TEMPLATE_8 = 'template_8';

    /**
     * Get localized label for the template style
     */
    public function label(): string
    {
        return __('enums.certificate_template_style.' . $this->value);
    }

    /**
     * Get English label for the template style
     */
    public function labelEn(): string
    {
        return match($this) {
            self::TEMPLATE_1 => 'Template 1',
            self::TEMPLATE_2 => 'Template 2',
            self::TEMPLATE_3 => 'Template 3',
            self::TEMPLATE_4 => 'Template 4',
            self::TEMPLATE_5 => 'Template 5',
            self::TEMPLATE_6 => 'Template 6',
            self::TEMPLATE_7 => 'Template 7',
            self::TEMPLATE_8 => 'Template 8',
        };
    }

    /**
     * Get description for the template style
     */
    public function description(): string
    {
        return match($this) {
            self::TEMPLATE_1 => 'تصميم بسيط وأنيق',
            self::TEMPLATE_2 => 'تصميم كلاسيكي رسمي',
            self::TEMPLATE_3 => 'تصميم عصري بألوان زاهية',
            self::TEMPLATE_4 => 'تصميم فاخر بإطار ذهبي',
            self::TEMPLATE_5 => 'تصميم مبتكر بعناصر هندسية',
            self::TEMPLATE_6 => 'تصميم متقدم مع زخارف معقدة',
            self::TEMPLATE_7 => 'تصميم احترافي راقي',
            self::TEMPLATE_8 => 'تصميم مميز وعصري',
        };
    }

    /**
     * Get primary color for the template style
     */
    public function primaryColor(): string
    {
        return match($this) {
            self::TEMPLATE_1 => '#1e40af', // Blue-800
            self::TEMPLATE_2 => '#7c2d12', // Brown-800
            self::TEMPLATE_3 => '#0891b2', // Cyan-600
            self::TEMPLATE_4 => '#d97706', // Amber-600
            self::TEMPLATE_5 => '#6366f1', // Indigo-500
            self::TEMPLATE_6 => '#ec4899', // Pink-500
            self::TEMPLATE_7 => '#059669', // Emerald-600
            self::TEMPLATE_8 => '#7c3aed', // Violet-600
        };
    }

    /**
     * Get secondary color for the template style
     */
    public function secondaryColor(): string
    {
        return match($this) {
            self::TEMPLATE_1 => '#3b82f6', // Blue-500
            self::TEMPLATE_2 => '#a8a29e', // Stone-400
            self::TEMPLATE_3 => '#06b6d4', // Cyan-500
            self::TEMPLATE_4 => '#f59e0b', // Amber-500
            self::TEMPLATE_5 => '#818cf8', // Indigo-400
            self::TEMPLATE_6 => '#f472b6', // Pink-400
            self::TEMPLATE_7 => '#34d399', // Emerald-400
            self::TEMPLATE_8 => '#a78bfa', // Violet-400
        };
    }

    /**
     * Get icon for the template style
     */
    public function icon(): string
    {
        return 'ri-file-text-line'; // Same icon for all templates
    }

    /**
     * Get preview image URL for the template
     */
    public function previewImageUrl(): string
    {
        return asset('certificates/templates/template_images/' . $this->value . '.png');
    }

    /**
     * Get PDF filename for the template (used for FPDI-based generation)
     */
    public function pdfFileName(): string
    {
        return $this->value . '.pdf';
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
        return "pdf.certificates.png-template";
    }
}
