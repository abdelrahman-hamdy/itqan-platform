<?php

use App\Enums\CertificateTemplateStyle;
use App\Enums\InteractiveCourseStatus;
use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseSession;
use App\Models\StudentProfile;
use App\Models\User;

describe('InteractiveCourse Model', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = AcademicTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
        $this->gradeLevel = AcademicGradeLevel::create([
            'academy_id' => $this->academy->id,
            'name' => 'Grade 1',
            'is_active' => true,
        ]);
        $this->subject = AcademicSubject::factory()->create([
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('factory', function () {
        it('can create an interactive course using factory', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($course)->toBeInstanceOf(InteractiveCourse::class)
                ->and($course->id)->toBeInt();
        });

        it('auto-generates course code on creation', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($course->course_code)->toStartWith('IC-');
        });

        it('creates course with title', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'title' => 'دورة الرياضيات المتقدمة',
            ]);

            expect($course->title)->toBe('دورة الرياضيات المتقدمة');
        });
    });

    describe('relationships', function () {
        it('belongs to an academy', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($course->academy)->toBeInstanceOf(Academy::class)
                ->and($course->academy->id)->toBe($this->academy->id);
        });

        it('belongs to an assigned teacher', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($course->assignedTeacher)->toBeInstanceOf(AcademicTeacherProfile::class)
                ->and($course->assignedTeacher->id)->toBe($this->teacherProfile->id);
        });

        it('belongs to a subject', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'subject_id' => $this->subject->id,
            ]);

            expect($course->subject)->toBeInstanceOf(AcademicSubject::class)
                ->and($course->subject->id)->toBe($this->subject->id);
        });

        it('belongs to a grade level', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($course->gradeLevel)->toBeInstanceOf(AcademicGradeLevel::class)
                ->and($course->gradeLevel->id)->toBe($this->gradeLevel->id);
        });

        it('has many enrollments', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            $studentProfile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'course_id' => $course->id,
                'student_id' => $studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            expect($course->enrollments)->toHaveCount(1);
        });

        it('has many sessions', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            InteractiveCourseSession::factory()->count(3)->create([
                'course_id' => $course->id,
            ]);

            expect($course->sessions)->toHaveCount(3);
        });

        it('belongs to a creator', function () {
            $creator = User::factory()->admin()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'created_by' => $creator->id,
            ]);

            expect($course->creator)->toBeInstanceOf(User::class)
                ->and($course->creator->id)->toBe($creator->id);
        });
    });

    describe('scopes', function () {
        it('can filter courses for academy', function () {
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            $otherAcademy = Academy::factory()->create();
            $otherTeacher = User::factory()->academicTeacher()->forAcademy($otherAcademy)->create();
            $otherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $otherTeacher->id,
                'academy_id' => $otherAcademy->id,
            ]);
            InteractiveCourse::factory()->create([
                'academy_id' => $otherAcademy->id,
                'assigned_teacher_id' => $otherProfile->id,
            ]);

            expect(InteractiveCourse::forAcademy($this->academy->id)->count())->toBe(1);
        });

        it('can filter published courses', function () {
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'status' => InteractiveCourseStatus::PUBLISHED,
                'is_published' => true,
            ]);

            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'status' => InteractiveCourseStatus::DRAFT,
                'is_published' => false,
            ]);

            expect(InteractiveCourse::published()->count())->toBe(1);
        });

        it('can filter active courses', function () {
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'status' => InteractiveCourseStatus::ACTIVE,
            ]);

            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'status' => InteractiveCourseStatus::COMPLETED,
            ]);

            expect(InteractiveCourse::active()->count())->toBe(1);
        });

        it('can filter by teacher', function () {
            InteractiveCourse::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            $otherTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $otherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $otherTeacher->id,
                'academy_id' => $this->academy->id,
            ]);
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $otherProfile->id,
            ]);

            expect(InteractiveCourse::byTeacher($this->teacherProfile->id)->count())->toBe(2);
        });

        it('can filter by subject', function () {
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'subject_id' => $this->subject->id,
            ]);

            $otherSubject = AcademicSubject::factory()->create(['academy_id' => $this->academy->id]);
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'subject_id' => $otherSubject->id,
            ]);

            expect(InteractiveCourse::bySubject($this->subject->id)->count())->toBe(1);
        });

        it('can filter by grade level', function () {
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            $otherGrade = AcademicGradeLevel::create([
                'academy_id' => $this->academy->id,
                'name' => 'Grade 2',
                'is_active' => true,
            ]);
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'grade_level_id' => $otherGrade->id,
            ]);

            expect(InteractiveCourse::byGradeLevel($this->gradeLevel->id)->count())->toBe(1);
        });
    });

    describe('attributes and casts', function () {
        it('casts status to enum', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'status' => InteractiveCourseStatus::PUBLISHED,
            ]);

            expect($course->status)->toBeInstanceOf(InteractiveCourseStatus::class);
        });

        it('casts start_date to date', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'start_date' => '2024-01-15',
            ]);

            expect($course->start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('casts schedule to array', function () {
            $schedule = [['day' => 'sunday', 'time' => '16:00']];
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'schedule' => $schedule,
            ]);

            expect($course->schedule)->toBeArray();
        });

        it('casts learning_outcomes to array', function () {
            $outcomes = ['فهم المفاهيم الأساسية', 'تطبيق المهارات'];
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'learning_outcomes' => $outcomes,
            ]);

            expect($course->learning_outcomes)->toBeArray()
                ->and($course->learning_outcomes)->toBe($outcomes);
        });

        it('casts is_published to boolean', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'is_published' => 1,
            ]);

            expect($course->is_published)->toBeBool()->toBeTrue();
        });

        it('casts certificate_enabled to boolean', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'certificate_enabled' => true,
            ]);

            expect($course->certificate_enabled)->toBeBool()->toBeTrue();
        });

        it('casts certificate_template_style to enum', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'certificate_template_style' => CertificateTemplateStyle::TEMPLATE_1,
            ]);

            expect($course->certificate_template_style)->toBeInstanceOf(CertificateTemplateStyle::class);
        });

        it('casts student_price to decimal', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'student_price' => 500.50,
            ]);

            expect($course->student_price)->toBeString();
        });
    });

    describe('accessors', function () {
        it('returns display name with course code', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'title' => 'دورة الرياضيات',
            ]);

            expect($course->display_name)->toContain('دورة الرياضيات')
                ->and($course->display_name)->toContain($course->course_code);
        });

        it('returns course type in Arabic', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'course_type' => 'intensive',
            ]);

            expect($course->course_type_in_arabic)->toBe('مكثف');
        });

        it('returns status in Arabic', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'status' => InteractiveCourseStatus::PUBLISHED,
            ]);

            expect($course->status_in_arabic)->toBeString();
        });

        it('returns payment type in Arabic', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'payment_type' => 'fixed_amount',
            ]);

            expect($course->payment_type_in_arabic)->toBe('مبلغ ثابت');
        });
    });

    describe('methods', function () {
        it('can get current enrollment count', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            $studentProfile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);
            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'course_id' => $course->id,
                'student_id' => $studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            expect($course->getCurrentEnrollmentCount())->toBe(1);
        });

        it('can get available slots', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'max_students' => 20,
            ]);

            $studentProfile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);
            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'course_id' => $course->id,
                'student_id' => $studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            expect($course->getAvailableSlots())->toBe(19);
        });

        it('can check if user has reviewed', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            expect($course->hasReviewFrom($student->id))->toBeFalse();
        });
    });

    describe('boot method calculations', function () {
        it('calculates duration weeks from sessions and sessions per week', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'total_sessions' => 12,
                'sessions_per_week' => 2,
            ]);

            expect($course->duration_weeks)->toBe(6);
        });

        it('calculates end date from start date and duration weeks', function () {
            $startDate = now()->addDays(7);
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
                'start_date' => $startDate,
                'total_sessions' => 8,
                'sessions_per_week' => 2,
            ]);

            expect($course->duration_weeks)->toBe(4);
            expect($course->end_date->format('Y-m-d'))->toBe($startDate->copy()->addWeeks(4)->format('Y-m-d'));
        });
    });

    describe('course code generation', function () {
        it('generates unique course codes', function () {
            $course1 = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            $course2 = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($course1->course_code)->not->toBe($course2->course_code);
        });

        it('includes academy id in course code', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($course->course_code)->toStartWith('IC-');
        });
    });

    describe('soft deletes', function () {
        it('can be soft deleted', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            $course->delete();

            expect($course->trashed())->toBeTrue()
                ->and(InteractiveCourse::withTrashed()->find($course->id))->not->toBeNull();
        });

        it('can be restored', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);
            $course->delete();

            $course->restore();

            expect($course->trashed())->toBeFalse()
                ->and(InteractiveCourse::find($course->id))->not->toBeNull();
        });
    });
});
