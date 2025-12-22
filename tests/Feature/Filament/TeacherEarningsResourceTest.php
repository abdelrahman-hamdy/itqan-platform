<?php

use App\Filament\Teacher\Resources\TeacherEarningsResource;
use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('TeacherEarningsResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('navigation', function () {
        it('has correct navigation label', function () {
            expect(TeacherEarningsResource::getNavigationLabel())->toBe('أرباحي');
        });

        it('has correct navigation icon', function () {
            expect(TeacherEarningsResource::getNavigationIcon())->toBe('heroicon-o-banknotes');
        });
    });

    describe('model labels', function () {
        it('has correct model label', function () {
            expect(TeacherEarningsResource::getModelLabel())->toBe('الأرباح');
        });

        it('has correct plural model label', function () {
            expect(TeacherEarningsResource::getPluralModelLabel())->toBe('أرباحي');
        });
    });
});
