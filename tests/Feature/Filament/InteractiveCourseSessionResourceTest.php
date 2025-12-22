<?php

use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource;
use App\Models\Academy;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\User;

describe('InteractiveCourseSessionResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = AcademicTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('getEloquentQuery', function () {
        it('scopes sessions to current teacher courses only', function () {
            $this->actingAs($this->teacher);

            // Create course for this teacher
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $this->teacherProfile->id,
            ]);

            // Create session for this course
            $ownSession = InteractiveCourseSession::factory()->create([
                'interactive_course_id' => $course->id,
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
            $otherSession = InteractiveCourseSession::factory()->create([
                'interactive_course_id' => $otherCourse->id,
            ]);

            $query = InteractiveCourseSessionResource::getEloquentQuery();

            expect($query->pluck('id')->toArray())->toContain($ownSession->id);
            expect($query->pluck('id')->toArray())->not->toContain($otherSession->id);
        });
    });

    describe('navigation', function () {
        it('has correct navigation icon', function () {
            expect(InteractiveCourseSessionResource::getNavigationIcon())->toBe('heroicon-o-video-camera');
        });
    });
});
