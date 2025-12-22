<?php

use App\Models\Academy;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;

describe('ParentQuizController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns quizzes for linked children', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $teacher->id,
            ]);

            QuizAssignment::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'assigned_at' => now(),
            ]);

            $response = $this->actingAs($parent)->get(route('parent.quizzes.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('parent.quizzes.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(403);
        });
    });
});
