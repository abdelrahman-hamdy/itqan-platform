<?php

use App\Filament\Teacher\Resources\QuranSessionResource;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;

use function Pest\Livewire\livewire;

describe('QuranSessionResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('getEloquentQuery', function () {
        it('scopes sessions to current teacher only', function () {
            $this->actingAs($this->teacher);

            // Create session for this teacher
            $ownSession = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            // Create session for another teacher
            $otherTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $otherSession = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $otherTeacher->id,
            ]);

            $query = QuranSessionResource::getEloquentQuery();

            expect($query->pluck('id')->toArray())->toContain($ownSession->id);
            expect($query->pluck('id')->toArray())->not->toContain($otherSession->id);
        });

        it('returns empty results for non-quran-teacher users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($student);

            $query = QuranSessionResource::getEloquentQuery();

            expect($query->count())->toBe(0);
        });
    });

    describe('navigation', function () {
        it('has correct navigation label', function () {
            expect(QuranSessionResource::getNavigationLabel())->toBe('جلساتي');
        });

        it('has correct navigation icon', function () {
            expect(QuranSessionResource::getNavigationIcon())->toBe('heroicon-o-video-camera');
        });

        it('has correct navigation group', function () {
            expect(QuranSessionResource::getNavigationGroup())->toBe('جلساتي');
        });
    });

    describe('model labels', function () {
        it('has correct model label', function () {
            expect(QuranSessionResource::getModelLabel())->toBe('جلسة قرآن');
        });

        it('has correct plural model label', function () {
            expect(QuranSessionResource::getPluralModelLabel())->toBe('جلسات القرآن');
        });
    });
});
