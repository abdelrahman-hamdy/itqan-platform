<?php

use App\Models\Academy;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;

describe('QuizController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns quizzes for authenticated student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $teacher->id,
            ]);

            // Assign quiz to student
            QuizAssignment::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'assigned_at' => now(),
            ]);

            $response = $this->actingAs($student)->get(route('student.quizzes.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('requires authentication', function () {
            $response = $this->get(route('student.quizzes.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertRedirect();
        });
    });

    describe('show', function () {
        it('shows quiz details to assigned student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $teacher->id,
            ]);

            QuizAssignment::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'assigned_at' => now(),
            ]);

            $response = $this->actingAs($student)->get(route('student.quizzes.show', [
                'subdomain' => $this->academy->subdomain,
                'quiz' => $quiz->id,
            ]));

            $response->assertStatus(200);
        });
    });
});
