<?php

use App\Filament\AcademicTeacher\Resources\InteractiveCourseResource;
use App\Models\Academy;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\User;

describe('InteractiveCourseResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = AcademicTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('getEloquentQuery', function () {
        it('scopes courses to current teacher only', function () {
            $this->actingAs($this->teacher);

            // Create course for this teacher
            $ownCourse = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $this->teacherProfile->id,
            ]);

            // Create course for another teacher
            $otherTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $otherTeacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $otherTeacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $otherCourse = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $otherTeacherProfile->id,
            ]);

            $query = InteractiveCourseResource::getEloquentQuery();

            expect($query->pluck('id')->toArray())->toContain($ownCourse->id);
            expect($query->pluck('id')->toArray())->not->toContain($otherCourse->id);
        });
    });

    describe('navigation', function () {
        it('has correct navigation icon', function () {
            expect(InteractiveCourseResource::getNavigationIcon())->toBe('heroicon-o-computer-desktop');
        });
    });
});
