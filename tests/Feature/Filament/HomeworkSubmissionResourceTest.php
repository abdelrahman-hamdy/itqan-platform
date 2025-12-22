<?php

use App\Filament\Teacher\Resources\HomeworkSubmissionResource;
use App\Models\Academy;
use App\Models\HomeworkSubmission;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('HomeworkSubmissionResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('navigation', function () {
        it('has correct navigation icon', function () {
            expect(HomeworkSubmissionResource::getNavigationIcon())->toBe('heroicon-o-document-check');
        });
    });

    describe('model labels', function () {
        it('has correct model label', function () {
            expect(HomeworkSubmissionResource::getModelLabel())->toBe('تسليم واجب');
        });

        it('has correct plural model label', function () {
            expect(HomeworkSubmissionResource::getPluralModelLabel())->toBe('تسليمات الواجبات');
        });
    });
});
