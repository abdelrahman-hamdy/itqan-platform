<?php

use App\Livewire\QuizzesWidget;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;
use Livewire\Livewire;

describe('Quizzes Widget Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('component rendering', function () {
        it('renders successfully with assignable model and student ID', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(QuizzesWidget::class, [
                    'assignable' => $session,
                    'studentId' => $student->studentProfile->id,
                ])
                ->assertStatus(200)
                ->assertSet('studentId', $student->studentProfile->id);
        });

        it('uses authenticated user student profile when student ID not provided', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(QuizzesWidget::class, ['assignable' => $session])
                ->assertSet('studentId', $student->studentProfile->id);
        });

        it('handles null student ID when user has no student profile', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($admin)
                ->test(QuizzesWidget::class, ['assignable' => $session])
                ->assertSet('studentId', null);
        });
    });

    describe('quizzes property', function () {
        it('returns empty collection when student ID is null', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create(['academy_id' => $this->academy->id]);

            $component = Livewire::actingAs($admin)
                ->test(QuizzesWidget::class, ['assignable' => $session]);

            expect($component->get('quizzes')->isEmpty())->toBeTrue();
        });

        it('displays available quizzes for student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create(['academy_id' => $this->academy->id]);
            $quiz = Quiz::factory()->create(['academy_id' => $this->academy->id, 'title' => 'Test Quiz']);

            QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => get_class($session),
                'assignable_id' => $session->id,
                'student_id' => $student->studentProfile->id,
                'academy_id' => $this->academy->id,
            ]);

            Livewire::actingAs($student)
                ->test(QuizzesWidget::class, [
                    'assignable' => $session,
                    'studentId' => $student->studentProfile->id,
                ])
                ->assertStatus(200);
        });
    });

    describe('mount method', function () {
        it('sets assignable property correctly', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create(['academy_id' => $this->academy->id]);

            $component = Livewire::actingAs($student)
                ->test(QuizzesWidget::class, ['assignable' => $session]);

            expect($component->get('assignable'))->toBeInstanceOf(AcademicSession::class);
        });

        it('accepts different assignable model types', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($student)
                ->test(QuizzesWidget::class, ['assignable' => $subscription])
                ->assertStatus(200);
        });
    });

    describe('edge cases', function () {
        it('handles assignable with no quizzes', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create(['academy_id' => $this->academy->id]);

            $component = Livewire::actingAs($student)
                ->test(QuizzesWidget::class, [
                    'assignable' => $session,
                    'studentId' => $student->studentProfile->id,
                ]);

            expect($component->get('quizzes')->isEmpty())->toBeTrue();
        });

        it('filters quizzes by student ID', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create(['academy_id' => $this->academy->id]);
            $quiz = Quiz::factory()->create(['academy_id' => $this->academy->id]);

            QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => get_class($session),
                'assignable_id' => $session->id,
                'student_id' => $student1->studentProfile->id,
                'academy_id' => $this->academy->id,
            ]);

            Livewire::actingAs($student2)
                ->test(QuizzesWidget::class, [
                    'assignable' => $session,
                    'studentId' => $student2->studentProfile->id,
                ])
                ->assertStatus(200);
        });
    });
});
