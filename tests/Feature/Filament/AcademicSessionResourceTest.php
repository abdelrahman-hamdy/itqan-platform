<?php

use App\Filament\AcademicTeacher\Resources\AcademicSessionResource;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\User;

describe('AcademicSessionResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = AcademicTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('getEloquentQuery', function () {
        it('scopes sessions to current teacher only', function () {
            $this->actingAs($this->teacher);

            // Create session for this teacher
            $ownSession = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacher->id,
            ]);

            // Create session for another teacher
            $otherTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $otherSession = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $otherTeacher->id,
            ]);

            $query = AcademicSessionResource::getEloquentQuery();

            expect($query->pluck('id')->toArray())->toContain($ownSession->id);
            expect($query->pluck('id')->toArray())->not->toContain($otherSession->id);
        });
    });

    describe('navigation', function () {
        it('has correct model label', function () {
            expect(AcademicSessionResource::getModelLabel())->toBe('جلسة');
        });

        it('has correct plural model label', function () {
            expect(AcademicSessionResource::getPluralModelLabel())->toBe('جلساتي');
        });
    });
});
