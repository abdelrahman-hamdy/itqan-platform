<?php

use App\Filament\Teacher\Resources\QuizResource;
use App\Models\Academy;
use App\Models\Quiz;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('QuizResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('getEloquentQuery', function () {
        it('scopes quizzes to current teacher only', function () {
            $this->actingAs($this->teacher);

            // Create quiz for this teacher
            $ownQuiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $this->teacher->id,
            ]);

            // Create quiz for another teacher
            $otherTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $otherQuiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $otherTeacher->id,
            ]);

            $query = QuizResource::getEloquentQuery();

            expect($query->pluck('id')->toArray())->toContain($ownQuiz->id);
            expect($query->pluck('id')->toArray())->not->toContain($otherQuiz->id);
        });
    });

    describe('navigation', function () {
        it('has correct navigation icon', function () {
            expect(QuizResource::getNavigationIcon())->toBe('heroicon-o-clipboard-document-list');
        });
    });
});
