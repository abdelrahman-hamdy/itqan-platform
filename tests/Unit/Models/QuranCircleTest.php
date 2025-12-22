<?php

use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('QuranCircle Model', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('factory', function () {
        it('can create a quran circle using factory', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($circle)->toBeInstanceOf(QuranCircle::class)
                ->and($circle->id)->toBeInt();
        });

        it('auto-generates circle code on creation', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($circle->circle_code)->toStartWith('QC-');
        });

        it('creates circle with names', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'name_ar' => 'حلقة الفاتحين',
                'name_en' => 'Al-Fatihin Circle',
            ]);

            expect($circle->name_ar)->toBe('حلقة الفاتحين')
                ->and($circle->name_en)->toBe('Al-Fatihin Circle');
        });
    });

    describe('relationships', function () {
        it('belongs to an academy', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($circle->academy)->toBeInstanceOf(Academy::class)
                ->and($circle->academy->id)->toBe($this->academy->id);
        });

        it('belongs to a teacher', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($circle->teacher)->toBeInstanceOf(User::class)
                ->and($circle->teacher->id)->toBe($this->teacher->id);
        });

        it('has quranTeacher alias for teacher', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($circle->quranTeacher)->toBeInstanceOf(User::class);
        });

        it('has many students', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            $circle->students()->attach($student1->id, ['enrolled_at' => now(), 'status' => 'enrolled']);
            $circle->students()->attach($student2->id, ['enrolled_at' => now(), 'status' => 'enrolled']);

            expect($circle->students)->toHaveCount(2);
        });

        it('has many sessions', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            QuranSession::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'circle_id' => $circle->id,
                'session_type' => 'group',
            ]);

            expect($circle->sessions)->toHaveCount(2);
        });

        it('has one schedule', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            QuranCircleSchedule::create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'weekly_schedule' => [['day' => 'sunday', 'time' => '16:00']],
                'schedule_starts_at' => now(),
            ]);

            expect($circle->schedule)->toBeInstanceOf(QuranCircleSchedule::class);
        });
    });

    describe('scopes', function () {
        it('can filter active circles', function () {
            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
            ]);

            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => false,
                'enrollment_status' => 'closed',
            ]);

            expect(QuranCircle::active()->count())->toBe(1);
        });

        it('can filter ongoing circles', function () {
            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => true,
            ]);

            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => false,
            ]);

            expect(QuranCircle::ongoing()->count())->toBe(1);
        });

        it('can filter by specialization', function () {
            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'specialization' => 'memorization',
            ]);

            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'specialization' => 'recitation',
            ]);

            expect(QuranCircle::bySpecialization('memorization')->count())->toBe(1);
        });

        it('can filter by teacher', function () {
            QuranCircle::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $otherTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $otherTeacher->id,
            ]);

            expect(QuranCircle::byTeacher($this->teacher->id)->count())->toBe(2);
        });

        it('can filter high rated circles', function () {
            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'avg_rating' => 4.5,
            ]);

            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'avg_rating' => 3.0,
            ]);

            expect(QuranCircle::highRated(4.0)->count())->toBe(1);
        });
    });

    describe('attributes and casts', function () {
        it('casts learning_objectives to array', function () {
            $objectives = ['حفظ سورة البقرة', 'إتقان التجويد'];
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'learning_objectives' => $objectives,
            ]);

            expect($circle->learning_objectives)->toBeArray()
                ->and($circle->learning_objectives)->toBe($objectives);
        });

        it('casts status to boolean', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => 1,
            ]);

            expect($circle->status)->toBeBool();
        });

        it('casts schedule_days to array', function () {
            $days = ['sunday', 'tuesday', 'thursday'];
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'schedule_days' => $days,
            ]);

            expect($circle->schedule_days)->toBeArray()
                ->and($circle->schedule_days)->toBe($days);
        });

        it('casts monthly_fee to decimal', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'monthly_fee' => 150.50,
            ]);

            expect($circle->monthly_fee)->toBeString(); // Decimals cast to string
        });

        it('casts recording_enabled to boolean', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'recording_enabled' => true,
            ]);

            expect($circle->recording_enabled)->toBeBool()->toBeTrue();
        });
    });

    describe('accessors', function () {
        it('returns name based on locale', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'name_ar' => 'حلقة الحفاظ',
                'name_en' => 'Memorizers Circle',
            ]);

            app()->setLocale('ar');
            expect($circle->name)->toBe('حلقة الحفاظ');
        });

        it('returns status text in Arabic', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => true,
            ]);

            expect($circle->status_text)->toBe('نشط');
        });

        it('returns enrollment status text', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'enrollment_status' => 'open',
            ]);

            expect($circle->enrollment_status_text)->toBe('مفتوح للتسجيل');
        });

        it('returns circle type text', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'circle_type' => 'memorization',
            ]);

            expect($circle->circle_type_text)->toBe('حلقة حفظ');
        });

        it('returns specialization text', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'specialization' => 'memorization',
            ]);

            expect($circle->specialization_text)->toBe('حفظ القرآن');
        });

        it('returns formatted monthly fee', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'monthly_fee' => 200,
            ]);

            expect($circle->formatted_monthly_fee)->toContain('200')
                ->and($circle->formatted_monthly_fee)->toContain('ريال');
        });

        it('returns available spots', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'max_students' => 10,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle->students()->attach($student->id, ['enrolled_at' => now(), 'status' => 'enrolled']);

            expect($circle->available_spots)->toBe(9);
        });

        it('checks if circle is full', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'max_students' => 1,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle->students()->attach($student->id, ['enrolled_at' => now(), 'status' => 'enrolled']);

            expect($circle->is_full)->toBeTrue();
        });

        it('checks if circle can start', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'min_students_to_start' => 3,
            ]);

            expect($circle->can_start)->toBeFalse();

            // Add 3 students
            for ($i = 0; $i < 3; $i++) {
                $student = User::factory()->student()->forAcademy($this->academy)->create();
                $circle->students()->attach($student->id, ['enrolled_at' => now(), 'status' => 'enrolled']);
            }

            $circle->refresh();
            expect($circle->can_start)->toBeTrue();
        });
    });

    describe('methods', function () {
        it('can enroll a student', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'max_students' => 10,
                'enrollment_status' => 'open',
                'status' => true,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle->enrollStudent($student);

            expect($circle->students)->toHaveCount(1);
        });

        it('throws exception when enrolling to full circle', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'max_students' => 1,
                'enrollment_status' => 'open',
                'status' => true,
            ]);

            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $circle->students()->attach($student1->id, ['enrolled_at' => now(), 'status' => 'enrolled']);

            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            expect(fn() => $circle->enrollStudent($student2))
                ->toThrow(\Exception::class, 'الحلقة مكتملة العدد');
        });

        it('can unenroll a student', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'max_students' => 10,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle->students()->attach($student->id, ['enrolled_at' => now(), 'status' => 'enrolled']);

            $circle->unenrollStudent($student);

            expect($circle->students)->toHaveCount(0);
        });

        it('can check if student can enroll', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'max_students' => 10,
                'enrollment_status' => 'open',
                'status' => true,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            expect($circle->canEnrollStudent($student))->toBeTrue();
        });

        it('can suspend circle', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => true,
            ]);

            $circle->suspend('Maintenance required');
            $circle->refresh();

            // Status should be false (suspended)
            // Note: 'notes' column doesn't exist in schema, so reason is not stored
            expect($circle->status)->toBeFalse();
        });

        it('can resume circle', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => false,
            ]);

            $circle->resume();
            $circle->refresh();

            expect($circle->status)->toBeTrue();
        });

        it('can cancel circle', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'status' => true,
            ]);

            $circle->cancel('Not enough students');
            $circle->refresh();

            // Status should be false (cancelled), enrollment should be closed
            // Note: 'notes' column doesn't exist in schema, so reason is not stored
            expect($circle->status)->toBeFalse()
                ->and($circle->enrollment_status)->toBe('closed');
        });
    });

    describe('constants', function () {
        it('has age group constants', function () {
            expect(QuranCircle::AGE_GROUPS)->toBeArray()
                ->and(QuranCircle::AGE_GROUPS)->toHaveKey('children')
                ->and(QuranCircle::AGE_GROUPS)->toHaveKey('adults');
        });

        it('has gender type constants', function () {
            expect(QuranCircle::GENDER_TYPES)->toBeArray()
                ->and(QuranCircle::GENDER_TYPES)->toHaveKey('male')
                ->and(QuranCircle::GENDER_TYPES)->toHaveKey('female')
                ->and(QuranCircle::GENDER_TYPES)->toHaveKey('mixed');
        });
    });

    describe('circle code generation', function () {
        it('generates unique circle codes', function () {
            $circle1 = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $circle2 = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($circle1->circle_code)->not->toBe($circle2->circle_code);
        });

        it('includes academy id in circle code', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($circle->circle_code)->toContain((string) $this->academy->id);
        });
    });

    describe('soft deletes', function () {
        it('can be soft deleted', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $circle->delete();

            expect($circle->trashed())->toBeTrue()
                ->and(QuranCircle::withTrashed()->find($circle->id))->not->toBeNull();
        });

        it('can be restored', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);
            $circle->delete();

            $circle->restore();

            expect($circle->trashed())->toBeFalse()
                ->and(QuranCircle::find($circle->id))->not->toBeNull();
        });
    });
});
