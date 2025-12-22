<?php

use App\Filament\Teacher\Resources\QuranCircleResource;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('QuranCircleResource', function () {
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

            // Create circle for this teacher
            $ownCircle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $this->teacherProfile->id,
            ]);

            // Create circle for another teacher
            $otherTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $otherTeacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $otherTeacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $otherCircle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $otherTeacherProfile->id,
            ]);

            $query = QuranCircleResource::getEloquentQuery();

            expect($query->pluck('id')->toArray())->toContain($ownCircle->id);
            expect($query->pluck('id')->toArray())->not->toContain($otherCircle->id);
        });
    });

    describe('navigation', function () {
        it('has correct navigation icon', function () {
            expect(QuranCircleResource::getNavigationIcon())->toBe('heroicon-o-user-group');
        });

        it('has correct navigation label', function () {
            expect(QuranCircleResource::getNavigationLabel())->toBe('حلقاتي الجماعية');
        });
    });

    describe('model labels', function () {
        it('has correct model label', function () {
            expect(QuranCircleResource::getModelLabel())->toBe('حلقة');
        });

        it('has correct plural model label', function () {
            expect(QuranCircleResource::getPluralModelLabel())->toBe('حلقاتي الجماعية');
        });
    });
});
