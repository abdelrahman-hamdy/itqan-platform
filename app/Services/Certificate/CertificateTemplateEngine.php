<?php

namespace App\Services\Certificate;

use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Models\Academy;

class CertificateTemplateEngine
{
    /**
     * Get default template text from academy settings
     */
    public function getDefaultTemplateText(Academy $academy, CertificateType $type): string
    {
        $settings = $academy->settings ?? $academy->getOrCreateSettings();

        $key = match ($type) {
            CertificateType::RECORDED_COURSE => 'certificates.templates.recorded_course',
            CertificateType::INTERACTIVE_COURSE => 'certificates.templates.interactive_course',
            CertificateType::QURAN_SUBSCRIPTION => 'certificates.templates.quran_default',
            CertificateType::ACADEMIC_SUBSCRIPTION => 'certificates.templates.academic_default',
        };

        $default = match ($type) {
            CertificateType::RECORDED_COURSE => 'هذا يشهد بأن {student_name} قد أتم بنجاح دورة {course_name} بتاريخ {completion_date}.',
            CertificateType::INTERACTIVE_COURSE => 'هذا يشهد بأن {student_name} قد أتم بنجاح الدورة التفاعلية {course_name} تحت إشراف المعلم {teacher_name}.',
            CertificateType::QURAN_SUBSCRIPTION => 'هذا يشهد بأن {student_name} قد أتم {achievement} تحت إشراف المعلم {teacher_name} في أكاديمية {academy_name}.',
            CertificateType::ACADEMIC_SUBSCRIPTION => 'هذا يشهد بأن {student_name} قد أتم {achievement} تحت إشراف المعلم {teacher_name} في أكاديمية {academy_name}.',
        };

        return $settings->getSetting($key, $default);
    }

    /**
     * Replace placeholders in template text
     */
    public function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }

        return $text;
    }

    /**
     * Get template for recorded course certificate
     */
    public function getRecordedCourseTemplate(Academy $academy, array $data): string
    {
        $templateText = $data['template_text'] ?? $this->getDefaultTemplateText($academy, CertificateType::RECORDED_COURSE);

        return $this->replacePlaceholders($templateText, [
            'student_name' => $data['student_name'],
            'course_name' => $data['course_name'],
            'completion_date' => $data['completion_date'],
            'academy_name' => $academy->name,
        ]);
    }

    /**
     * Get template for interactive course certificate
     */
    public function getInteractiveCourseTemplate(Academy $academy, array $data): string
    {
        $templateText = $this->getDefaultTemplateText($academy, CertificateType::INTERACTIVE_COURSE);

        return $this->replacePlaceholders($templateText, [
            'student_name' => $data['student_name'],
            'course_name' => $data['course_name'],
            'completion_date' => $data['completion_date'] ?? now()->format('Y-m-d'),
            'teacher_name' => $data['teacher_name'] ?? '',
            'academy_name' => $academy->name,
        ]);
    }

    /**
     * Get template for subscription certificate (Quran or Academic)
     */
    public function getSubscriptionTemplate(Academy $academy, CertificateType $type, array $data): string
    {
        // For manual certificates, use achievement text directly
        if (isset($data['achievement_text'])) {
            return $data['achievement_text'];
        }

        $templateText = $this->getDefaultTemplateText($academy, $type);

        return $this->replacePlaceholders($templateText, [
            'student_name' => $data['student_name'],
            'achievement' => $data['achievement'] ?? '',
            'teacher_name' => $data['teacher_name'] ?? '',
            'academy_name' => $academy->name,
        ]);
    }

    /**
     * Get available template styles
     */
    public function getAvailableTemplates(): array
    {
        return CertificateTemplateStyle::cases();
    }

    /**
     * Get template style from course or academy settings
     */
    public function getTemplateStyle($entity, Academy $academy): CertificateTemplateStyle
    {
        // Try to get from entity (course)
        if (isset($entity->certificate_template_style)) {
            return is_string($entity->certificate_template_style)
                ? CertificateTemplateStyle::from($entity->certificate_template_style)
                : $entity->certificate_template_style;
        }

        // Fall back to academy default
        $settings = $academy->settings ?? $academy->getOrCreateSettings();
        $defaultStyle = $settings->getSetting('certificates.default_template_style', 'template_1');

        return CertificateTemplateStyle::from($defaultStyle);
    }

    /**
     * Validate template data has required placeholders
     */
    public function validateTemplateData(string $template, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (! str_contains($template, "{{$field}}")) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get template preview with sample data
     */
    public function getTemplatePreview(CertificateType $type, Academy $academy): string
    {
        $template = $this->getDefaultTemplateText($academy, $type);

        $sampleData = [
            'student_name' => 'أحمد محمد',
            'course_name' => 'دورة تعليمية نموذجية',
            'completion_date' => now()->format('Y-m-d'),
            'teacher_name' => 'عبد الله إبراهيم',
            'academy_name' => $academy->name,
            'achievement' => 'حفظ جزء عم',
        ];

        return $this->replacePlaceholders($template, $sampleData);
    }
}
