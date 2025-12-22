<?php

use App\Filament\Teacher\Resources\QuranIndividualCircleResource;
use App\Models\Academy;
use App\Models\QuranIndividualCircle;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('QuranIndividualCircleResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('getEloquentQuery', function () {
        it('scopes circles to current teacher only', function () {
            $this->actingAs($this->teacher);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Create circle for this teacher
            $ownCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $this->teacherProfile->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
            ]);

            // Create circle for another teacher
            $otherTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $otherTeacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $otherTeacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $otherStudent = User::factory()->student()->forAcademy($this->academy)->create();
            $otherCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $otherTeacherProfile->id,
                'student_profile_id' => $otherStudent->studentProfileUnscoped->id,
            ]);

            $query = QuranIndividualCircleResource::getEloquentQuery();

            expect($query->pluck('id')->toArray())->toContain($ownCircle->id);
            expect($query->pluck('id')->toArray())->not->toContain($otherCircle->id);
        });
    });

    describe('navigation', function () {
        it('has correct navigation icon', function () {
            expect(QuranIndividualCircleResource::getNavigationIcon())->toBe('heroicon-o-user');
        });

        it('has correct navigation label', function () {
            expect(QuranIndividualCircleResource::getNavigationLabel())->toBe('حلقاتي الفردية');
        });
    });
});
