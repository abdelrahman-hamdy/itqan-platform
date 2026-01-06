<?php

/*
|--------------------------------------------------------------------------
| Development Routes
|--------------------------------------------------------------------------
| Certificate template previews and other development-only utilities.
| These routes are only available in local environment.
*/

use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    /*
    |--------------------------------------------------------------------------
    | Certificate Template Preview (Development Only)
    |--------------------------------------------------------------------------
    */

    // HTML preview (browser) - for layout testing
    Route::get('/dev/certificate-preview', function () {
        $data = [
            'student_name' => 'اسم الطالب التجريبي',
            'certificate_text' => 'لقد أتم الطالب حفظ الجزء الأول من القرآن الكريم بإتقان وتجويد ممتاز. نسأل الله أن يبارك فيه ويجعله من حفظة كتابه.',
            'certificate_number' => 'CERT-2024-001',
            'issued_date_formatted' => now()->format('Y/m/d'),
            'teacher_name' => 'أ. محمد المعلم',
            'academy_name' => 'أكاديمية إتقان',
            'academy_logo' => null,
            'template_style' => \App\Enums\CertificateTemplateStyle::TEMPLATE_1,
        ];

        return view('pdf.certificates.png-template', $data);
    })->name('dev.certificate-preview');

    // PDF preview (download/stream) - using TCPDF for Arabic support
    Route::get('/dev/certificate-pdf-preview', function () {
        $data = [
            'student_name' => 'اسم الطالب التجريبي',
            'certificate_text' => 'لقد أتم الطالب حفظ الجزء الأول من القرآن الكريم بإتقان وتجويد ممتاز. نسأل الله أن يبارك فيه ويجعله من حفظة كتابه.',
            'certificate_number' => 'CERT-2024-001',
            'issued_date_formatted' => now()->format('Y/m/d'),
            'teacher_name' => 'أ. محمد المعلم',
            'academy_name' => 'أكاديمية إتقان',
            'academy_logo' => null,
            'template_style' => \App\Enums\CertificateTemplateStyle::TEMPLATE_1,
        ];

        $certificateService = app(\App\Services\CertificateService::class);
        $pdf = $certificateService->previewCertificate($data, $data['template_style']);

        return response($pdf->Output('', 'S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="certificate-preview.pdf"');
    })->name('dev.certificate-pdf-preview');
}
