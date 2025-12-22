<?php

use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource;
use App\Models\Academy;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicTeacherProfile;
use App\Models\User;

describe('AcademicIndividualLessonResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = AcademicTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('getEloquentQuery', function () {
        it('scopes lessons to current teacher only', function () {
            $this->actingAs($this->teacher);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Create lesson for this teacher
            $ownLesson = AcademicIndividualLesson::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $this->teacherProfile->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
            ]);

            // Create lesson for another teacher
            $otherTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $otherTeacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $otherTeacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $otherStudent = User::factory()->student()->forAcademy($this->academy)->create();
            $otherLesson = AcademicIndividualLesson::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $otherTeacherProfile->id,
                'student_profile_id' => $otherStudent->studentProfileUnscoped->id,
            ]);

            $query = AcademicIndividualLessonResource::getEloquentQuery();

            expect($query->pluck('id')->toArray())->toContain($ownLesson->id);
            expect($query->pluck('id')->toArray())->not->toContain($otherLesson->id);
        });
    });

    describe('navigation', function () {
        it('has correct navigation icon', function () {
            expect(AcademicIndividualLessonResource::getNavigationIcon())->toBe('heroicon-o-user');
        });
    });
});
