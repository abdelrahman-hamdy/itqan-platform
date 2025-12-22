<?php

use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Certificate;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use App\Services\CertificateService;
use App\Services\ParentNotificationService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

describe('CertificateService', function () {
    beforeEach(function () {
        Storage::fake('local');
        Notification::fake();

        $mockParentNotificationService = Mockery::mock(ParentNotificationService::class);
        $mockParentNotificationService->shouldReceive('sendCertificateIssued')->andReturn(true);

        $this->service = new CertificateService($mockParentNotificationService);
        $this->academy = Academy::factory()->create();
    });

    describe('generateCertificateNumber()', function () {
        it('generates a unique certificate number with year and random string', function () {
            $certificateNumber = $this->service->generateCertificateNumber();
            $year = now()->year;

            expect($certificateNumber)->toStartWith("CERT-{$year}-")
                ->and($certificateNumber)->toMatch('/^CERT-\d{4}-[A-Z0-9]{6}$/');
        });

        it('generates unique certificate numbers on multiple calls', function () {
            $number1 = $this->service->generateCertificateNumber();
            $number2 = $this->service->generateCertificateNumber();

            expect($number1)->not->toBe($number2);
        });

        it('regenerates if duplicate exists', function () {
            $existingNumber = $this->service->generateCertificateNumber();

            Certificate::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => User::factory()->student()->forAcademy($this->academy)->create()->id,
                'certificate_number' => $existingNumber,
                'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test certificate',
                'issued_at' => now(),
                'file_path' => 'test.pdf',
            ]);

            $newNumber = $this->service->generateCertificateNumber();
            expect($newNumber)->not->toBe($existingNumber);
        });
    });

    describe('issueCertificateForRecordedCourse()', function () {
        it('issues certificate for completed recorded course', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $recordedCourse = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $subscription = $recordedCourse->subscriptions()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'progress_percentage' => 100,
                'completion_date' => now(),
                'subscription_status' => 'active',
            ]);

            $certificate = $this->service->issueCertificateForRecordedCourse($subscription);

            expect($certificate)->toBeInstanceOf(Certificate::class)
                ->and($certificate->certificate_type)->toBe(CertificateType::RECORDED_COURSE)
                ->and($certificate->student_id)->toBe($student->id)
                ->and($certificate->academy_id)->toBe($this->academy->id)
                ->and($certificate->is_manual)->toBeFalse();

            expect($subscription->fresh()->certificate_issued)->toBeTrue();
        });

        it('returns existing certificate if already issued', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $recordedCourse = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $subscription = $recordedCourse->subscriptions()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'progress_percentage' => 100,
                'completion_date' => now(),
                'certificate_issued' => true,
                'subscription_status' => 'active',
            ]);

            $existingCertificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificateable_type' => get_class($subscription),
                'certificateable_id' => $subscription->id,
                'certificate_number' => 'CERT-2025-TEST01',
                'certificate_type' => CertificateType::RECORDED_COURSE,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test certificate',
                'issued_at' => now(),
                'file_path' => 'test.pdf',
            ]);

            $certificate = $this->service->issueCertificateForRecordedCourse($subscription);

            expect($certificate->id)->toBe($existingCertificate->id);
        });

        it('throws exception if course not 100% complete', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $recordedCourse = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $subscription = $recordedCourse->subscriptions()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'progress_percentage' => 85,
                'subscription_status' => 'active',
            ]);

            expect(fn () => $this->service->issueCertificateForRecordedCourse($subscription))
                ->toThrow(Exception::class, 'Student must complete 100% of the course');
        });

        it('stores PDF file in correct path', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $recordedCourse = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $subscription = $recordedCourse->subscriptions()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'progress_percentage' => 100,
                'completion_date' => now(),
                'subscription_status' => 'active',
            ]);

            $certificate = $this->service->issueCertificateForRecordedCourse($subscription);

            $year = now()->year;
            expect($certificate->file_path)
                ->toContain("tenants/{$this->academy->id}/certificates/{$year}/recorded_course");

            Storage::assertExists($certificate->file_path);
        });
    });

    describe('issueCertificateForInteractiveCourse()', function () {
        it('issues certificate for completed interactive course enrollment', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacherProfile->id,
            ]);

            $enrollment = InteractiveCourseEnrollment::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'course_id' => $course->id,
                'student_id' => $studentProfile->id,
                'enrollment_status' => 'completed',
                'certificate_issued' => false,
            ]);

            $certificate = $this->service->issueCertificateForInteractiveCourse($enrollment);

            expect($certificate)->toBeInstanceOf(Certificate::class)
                ->and($certificate->certificate_type)->toBe(CertificateType::INTERACTIVE_COURSE)
                ->and($certificate->student_id)->toBe($student->id)
                ->and($certificate->teacher_id)->toBe($teacher->id)
                ->and($certificate->is_manual)->toBeFalse();

            expect($enrollment->fresh()->certificate_issued)->toBeTrue();
        });

        it('returns existing certificate if already issued', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $enrollment = InteractiveCourseEnrollment::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'course_id' => $course->id,
                'student_id' => $studentProfile->id,
                'enrollment_status' => 'completed',
                'certificate_issued' => true,
            ]);

            $existingCertificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificateable_type' => get_class($enrollment),
                'certificateable_id' => $enrollment->id,
                'certificate_number' => 'CERT-2025-TEST02',
                'certificate_type' => CertificateType::INTERACTIVE_COURSE,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test certificate',
                'issued_at' => now(),
                'file_path' => 'test.pdf',
            ]);

            $certificate = $this->service->issueCertificateForInteractiveCourse($enrollment);

            expect($certificate->id)->toBe($existingCertificate->id);
        });

        it('throws exception if enrollment not completed', function () {
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $enrollment = InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'course_id' => $course->id,
                'student_id' => $studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            expect(fn () => $this->service->issueCertificateForInteractiveCourse($enrollment))
                ->toThrow(Exception::class, 'Student must complete the course');
        });
    });

    describe('issueManualCertificate()', function () {
        it('issues manual certificate for Quran subscription', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $subscription = QuranSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $achievementText = 'حفظ جزء عم بإتقان';
            $certificate = $this->service->issueManualCertificate(
                $subscription,
                $achievementText,
                CertificateTemplateStyle::TEMPLATE_1,
                null,
                $teacher->id
            );

            expect($certificate)->toBeInstanceOf(Certificate::class)
                ->and($certificate->certificate_type)->toBe(CertificateType::QURAN_SUBSCRIPTION)
                ->and($certificate->is_manual)->toBeTrue()
                ->and($certificate->custom_achievement_text)->toBe($achievementText)
                ->and($certificate->teacher_id)->toBe($teacher->id);

            expect($subscription->fresh()->certificate_issued)->toBeTrue();
        });

        it('issues manual certificate for Academic subscription', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $subscription = AcademicSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'academic_teacher_id' => $teacherProfile->id,
            ]);

            $achievementText = 'إتمام دورة الرياضيات المتقدمة';
            $certificate = $this->service->issueManualCertificate(
                $subscription,
                $achievementText,
                'template_2',
                null,
                $teacher->id
            );

            expect($certificate)->toBeInstanceOf(Certificate::class)
                ->and($certificate->certificate_type)->toBe(CertificateType::ACADEMIC_SUBSCRIPTION)
                ->and($certificate->template_style)->toBe(CertificateTemplateStyle::TEMPLATE_2)
                ->and($certificate->is_manual)->toBeTrue();
        });

        it('throws exception if certificate already issued', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificate_issued' => true,
            ]);

            expect(fn () => $this->service->issueManualCertificate(
                $subscription,
                'Achievement',
                CertificateTemplateStyle::TEMPLATE_1
            ))->toThrow(Exception::class, 'Certificate already issued');
        });

        it('throws exception for invalid subscription type', function () {
            $invalidSubscription = new stdClass();

            expect(fn () => $this->service->issueManualCertificate(
                $invalidSubscription,
                'Achievement',
                CertificateTemplateStyle::TEMPLATE_1
            ))->toThrow(Exception::class, 'Invalid subscription type');
        });
    });

    describe('issueGroupCircleCertificate()', function () {
        it('issues certificate for group circle student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            $achievementText = 'إتمام حفظ جزء تبارك في الحلقة';
            $certificate = $this->service->issueGroupCircleCertificate(
                $circle,
                $student,
                $achievementText,
                CertificateTemplateStyle::TEMPLATE_3
            );

            expect($certificate)->toBeInstanceOf(Certificate::class)
                ->and($certificate->certificateable_type)->toBe(QuranCircle::class)
                ->and($certificate->certificateable_id)->toBe($circle->id)
                ->and($certificate->is_manual)->toBeTrue()
                ->and($certificate->certificate_type)->toBe(CertificateType::QURAN_SUBSCRIPTION);
        });

        it('allows multiple certificates for same circle and student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            $cert1 = $this->service->issueGroupCircleCertificate(
                $circle,
                $student,
                'Achievement 1',
                CertificateTemplateStyle::TEMPLATE_1
            );

            $cert2 = $this->service->issueGroupCircleCertificate(
                $circle,
                $student,
                'Achievement 2',
                CertificateTemplateStyle::TEMPLATE_2
            );

            expect($cert1->id)->not->toBe($cert2->id)
                ->and($cert1->custom_achievement_text)->toBe('Achievement 1')
                ->and($cert2->custom_achievement_text)->toBe('Achievement 2');
        });
    });

    describe('issueInteractiveCourseCertificate()', function () {
        it('issues manual certificate for interactive course student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacherProfile->id,
            ]);

            $achievementText = 'إتمام الدورة بتفوق';
            $certificate = $this->service->issueInteractiveCourseCertificate(
                $course,
                $student,
                $achievementText,
                CertificateTemplateStyle::TEMPLATE_2
            );

            expect($certificate)->toBeInstanceOf(Certificate::class)
                ->and($certificate->certificateable_type)->toBe(InteractiveCourse::class)
                ->and($certificate->certificateable_id)->toBe($course->id)
                ->and($certificate->certificate_type)->toBe(CertificateType::INTERACTIVE_COURSE)
                ->and($certificate->is_manual)->toBeTrue();
        });
    });

    describe('getCertificateData()', function () {
        it('returns properly formatted certificate data', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_number' => 'CERT-2025-TEST99',
                'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test certificate text',
                'issued_at' => now(),
                'file_path' => 'test.pdf',
            ]);

            $data = $this->service->getCertificateData($certificate);

            expect($data)->toBeArray()
                ->and($data)->toHaveKey('certificate')
                ->and($data)->toHaveKey('academy')
                ->and($data)->toHaveKey('student')
                ->and($data)->toHaveKey('teacher')
                ->and($data)->toHaveKey('certificate_number')
                ->and($data)->toHaveKey('certificate_text')
                ->and($data)->toHaveKey('issued_date')
                ->and($data)->toHaveKey('issued_date_formatted')
                ->and($data)->toHaveKey('student_name')
                ->and($data)->toHaveKey('teacher_name')
                ->and($data)->toHaveKey('academy_name')
                ->and($data['certificate_number'])->toBe('CERT-2025-TEST99')
                ->and($data['student_name'])->toBe($student->name);
        });

        it('handles null teacher gracefully', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => null,
                'certificate_number' => 'CERT-2025-NULLT',
                'certificate_type' => CertificateType::RECORDED_COURSE,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test',
                'issued_at' => now(),
                'file_path' => 'test.pdf',
            ]);

            $data = $this->service->getCertificateData($certificate);

            expect($data['teacher_name'])->toBe('');
        });
    });

    describe('generateCertificatePDF()', function () {
        it('generates PDF for certificate', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificate_number' => 'CERT-2025-PDFTS',
                'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test certificate',
                'issued_at' => now(),
                'file_path' => 'test.pdf',
            ]);

            $pdf = $this->service->generateCertificatePDF($certificate);

            expect($pdf)->toBeInstanceOf(\setasign\Fpdi\Tcpdf\Fpdi::class);
        });
    });

    describe('revokeCertificate()', function () {
        it('soft deletes certificate and updates subscription', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->active()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificate_issued' => true,
                'certificate_issued_at' => now(),
            ]);

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificateable_type' => get_class($subscription),
                'certificateable_id' => $subscription->id,
                'certificate_number' => 'CERT-2025-REVOK',
                'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test',
                'issued_at' => now(),
                'file_path' => 'test.pdf',
            ]);

            Storage::put($certificate->file_path, 'fake pdf content');

            $result = $this->service->revokeCertificate($certificate);

            expect($result)->toBeTrue();
            expect($certificate->fresh())->toBeNull();
            expect($subscription->fresh()->certificate_issued)->toBeFalse();
            expect($subscription->fresh()->certificate_issued_at)->toBeNull();
        });

        it('handles certificate without related subscription', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificate_number' => 'CERT-2025-ALONE',
                'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test',
                'issued_at' => now(),
                'file_path' => 'test.pdf',
            ]);

            $result = $this->service->revokeCertificate($certificate);

            expect($result)->toBeTrue();
            expect($certificate->fresh())->toBeNull();
        });
    });

    describe('previewCertificate()', function () {
        it('generates preview PDF without saving', function () {
            $data = [
                'student_name' => 'محمد أحمد',
                'certificate_text' => 'نص الشهادة التجريبية',
                'teacher_name' => 'أحمد علي',
                'academy_name' => 'أكاديمية إتقان',
                'issued_date_formatted' => '١ يناير ٢٠٢٥',
                'certificate_number' => 'PREVIEW-001',
            ];

            $pdf = $this->service->previewCertificate($data, CertificateTemplateStyle::TEMPLATE_1);

            expect($pdf)->toBeInstanceOf(\setasign\Fpdi\Tcpdf\Fpdi::class);
        });

        it('accepts template style as string', function () {
            $data = [
                'student_name' => 'Test Student',
                'certificate_text' => 'Test text',
                'teacher_name' => 'Test Teacher',
                'academy_name' => 'Test Academy',
                'issued_date_formatted' => 'Jan 1, 2025',
                'certificate_number' => 'PREVIEW-002',
            ];

            $pdf = $this->service->previewCertificate($data, 'template_2');

            expect($pdf)->toBeInstanceOf(\setasign\Fpdi\Tcpdf\Fpdi::class);
        });
    });

    describe('downloadCertificate()', function () {
        it('returns download response for existing file', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificate_number' => 'CERT-2025-DLOAD',
                'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test',
                'issued_at' => now(),
                'file_path' => 'test/cert.pdf',
            ]);

            Storage::put($certificate->file_path, 'fake pdf content');

            $response = $this->service->downloadCertificate($certificate);

            expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
        });

        it('regenerates file if missing', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificate_number' => 'CERT-2025-REGEN',
                'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test',
                'issued_at' => now(),
                'file_path' => 'nonexistent.pdf',
            ]);

            expect($certificate->fileExists())->toBeFalse();

            $response = $this->service->downloadCertificate($certificate);

            expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
            expect(Storage::exists($certificate->fresh()->file_path))->toBeTrue();
        });
    });

    describe('streamCertificate()', function () {
        it('streams certificate PDF for viewing', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificate_number' => 'CERT-2025-STREA',
                'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test',
                'issued_at' => now(),
                'file_path' => 'test/stream.pdf',
            ]);

            Storage::put($certificate->file_path, 'fake pdf content');

            $response = $this->service->streamCertificate($certificate);

            expect($response)->toBeInstanceOf(\Illuminate\Http\Response::class);
        });

        it('generates PDF on-the-fly if file missing', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificate_number' => 'CERT-2025-NOFILE',
                'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                'certificate_text' => 'Test',
                'issued_at' => now(),
                'file_path' => 'missing.pdf',
            ]);

            $response = $this->service->streamCertificate($certificate);

            expect($response)->toBeInstanceOf(\Illuminate\Http\Response::class);
            expect($response->headers->get('Content-Type'))->toBe('application/pdf');
        });
    });
});
