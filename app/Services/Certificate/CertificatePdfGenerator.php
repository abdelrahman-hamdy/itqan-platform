<?php

namespace App\Services\Certificate;

use App\Enums\CertificateTemplateStyle;
use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class CertificatePdfGenerator
{
    /**
     * Generate certificate PDF using FPDI + TCPDF
     */
    public function generatePdf(Certificate $certificate, array $data): Fpdi
    {
        $data['template_style'] = $certificate->template_style;

        // Create FPDI instance with PDF template
        $pdf = $this->createFpdiInstance($certificate->template_style);

        // Overlay text content at specific positions
        $this->addCertificateText($pdf, $data);

        return $pdf;
    }

    /**
     * Generate preview PDF without certificate record
     */
    public function generatePreviewPdf(array $data, CertificateTemplateStyle $templateStyle): Fpdi
    {
        $data['template_style'] = $templateStyle;

        // Create FPDI instance with PDF template
        $pdf = $this->createFpdiInstance($templateStyle);

        // Overlay text content at specific positions
        $this->addCertificateText($pdf, $data);

        return $pdf;
    }

    /**
     * Create FPDI instance with PDF template loaded
     */
    protected function createFpdiInstance(CertificateTemplateStyle $templateStyle): Fpdi
    {
        // Create FPDI with landscape A4, RTL for Arabic
        $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Itqan Platform');
        $pdf->SetAuthor('Itqan Academy');
        $pdf->SetTitle('Certificate');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins to 0 for full-page background
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        // Load the PDF template
        $pdfTemplatePath = public_path('certificates/templates/' . $templateStyle->pdfFileName());
        if (file_exists($pdfTemplatePath)) {
            // Set source file
            $pdf->setSourceFile($pdfTemplatePath);

            // Import first page
            $templateId = $pdf->importPage(1);

            // Add a page with the template
            $pdf->AddPage('L', 'A4');

            // Use the imported page as template (full page)
            $pdf->useTemplate($templateId, 0, 0, 297, 210);
        } else {
            // Fallback: just add a blank page
            $pdf->AddPage('L', 'A4');
        }

        // Enable RTL mode for Arabic
        $pdf->setRTL(true);

        return $pdf;
    }

    /**
     * Add text content to certificate at specific coordinates
     */
    protected function addCertificateText(Fpdi $pdf, array $data): void
    {
        $templateStyle = $data['template_style'];
        $primaryColor = $this->hexToRgb($templateStyle->primaryColor());

        // Use Amiri - elegant Arabic font designed for readability
        // Certificate Title - "شهادة تقدير"
        $pdf->SetTextColor($primaryColor['r'], $primaryColor['g'], $primaryColor['b']);
        $pdf->SetFont('amirib', '', 40);
        $pdf->SetXY(0, 35);
        $pdf->Cell(297, 15, 'شهادة تقدير', 0, 1, 'C');

        // Subtitle - "تُمنح هذه الشهادة إلى"
        $pdf->SetTextColor(85, 85, 85);
        $pdf->SetFont('amiri', '', 16);
        $pdf->SetXY(0, 55);
        $pdf->Cell(297, 10, 'تُمنح هذه الشهادة إلى', 0, 1, 'C');

        // Student Name
        $pdf->SetTextColor(26, 26, 26);
        $pdf->SetFont('amirib', '', 30);
        $pdf->SetXY(0, 70);
        $pdf->Cell(297, 15, $data['student_name'], 0, 1, 'C');

        // Certificate Text (font size 18, with increased line spacing)
        $pdf->SetTextColor(68, 68, 68);
        $pdf->SetFont('amiri', '', 18);
        $pdf->setCellHeightRatio(2.0); // Double the line height
        $pdf->SetXY(40, 95);
        $pdf->MultiCell(217, 0, $data['certificate_text'], 0, 'C');
        $pdf->setCellHeightRatio(1.25); // Reset to default

        // Footer section - Three columns: Teacher, Date, Academy (no lines)
        // Adjusted positions to avoid borders (moved closer to center)
        $footerY = 155;

        // Teacher column (moved left from X=220 to X=195)
        $pdf->SetTextColor(136, 136, 136);
        $pdf->SetFont('amiri', '', 11);
        $pdf->SetXY(195, $footerY + 2);
        $pdf->Cell(70, 6, 'المعلم', 0, 1, 'C');
        $pdf->SetTextColor(51, 51, 51);
        $pdf->SetFont('amirib', '', 13);
        $pdf->SetXY(195, $footerY + 10);
        $pdf->Cell(70, 6, $data['teacher_name'] ?: '—', 0, 1, 'C');

        // Date column (center - unchanged)
        $pdf->SetTextColor(136, 136, 136);
        $pdf->SetFont('amiri', '', 11);
        $pdf->SetXY(108, $footerY + 2);
        $pdf->Cell(80, 6, 'التاريخ', 0, 1, 'C');
        $pdf->SetTextColor(51, 51, 51);
        $pdf->SetFont('amirib', '', 13);
        $pdf->SetXY(108, $footerY + 10);
        $pdf->Cell(80, 6, $data['issued_date_formatted'], 0, 1, 'C');

        // Academy column (moved right from X=7 to X=32)
        $pdf->SetTextColor(136, 136, 136);
        $pdf->SetFont('amiri', '', 11);
        $pdf->SetXY(32, $footerY + 2);
        $pdf->Cell(70, 6, 'الأكاديمية', 0, 1, 'C');
        $pdf->SetTextColor(51, 51, 51);
        $pdf->SetFont('amirib', '', 13);
        $pdf->SetXY(32, $footerY + 10);
        $pdf->Cell(70, 6, $data['academy_name'] ?: '—', 0, 1, 'C');

        // Certificate number at bottom
        $pdf->SetTextColor(153, 153, 153);
        $pdf->SetFont('amiri', '', 10);
        $pdf->setRTL(false); // Certificate number in LTR
        $pdf->SetXY(0, 190);
        $pdf->Cell(297, 6, $data['certificate_number'], 0, 1, 'C');
        $pdf->setRTL(true); // Back to RTL
    }

    /**
     * Apply custom styles to PDF (colors, fonts, etc.)
     */
    public function applyStyles(Fpdi $pdf, array $styles): void
    {
        if (isset($styles['primary_color'])) {
            $rgb = $this->hexToRgb($styles['primary_color']);
            $pdf->SetTextColor($rgb['r'], $rgb['g'], $rgb['b']);
        }

        if (isset($styles['font_family'])) {
            $pdf->SetFont($styles['font_family'], '', $styles['font_size'] ?? 12);
        }
    }

    /**
     * Store certificate PDF to storage
     */
    public function storePdf(Fpdi $pdf, Certificate $certificate): string
    {
        $academy = $certificate->academy;
        $year = now()->year;
        $type = $certificate->certificate_type->value;

        // Build file path
        $fileName = "{$certificate->student_id}_{$certificate->certificate_number}.pdf";
        $directory = "tenants/{$academy->id}/certificates/{$year}/{$type}";
        $filePath = "{$directory}/{$fileName}";

        // Save PDF - TCPDF uses Output() method with 'S' for string
        Storage::put($filePath, $pdf->Output('', 'S'));

        return $filePath;
    }

    /**
     * Get PDF as string (for inline display or download)
     */
    public function getPdfString(Fpdi $pdf): string
    {
        return $pdf->Output('', 'S');
    }

    /**
     * Convert hex color to RGB array
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }
}
