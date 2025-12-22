<?php

use App\Livewire\ReviewForm;
use App\Models\Academy;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\User;
use Livewire\Livewire;

describe('Review Form Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('component rendering', function () {
        it('renders successfully for teacher review', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->assertStatus(200)
                ->assertSet('reviewType', 'teacher')
                ->assertSet('reviewableType', QuranTeacherProfile::class)
                ->assertSet('reviewableId', $teacher->id);
        });

        it('renders successfully for course review', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'course',
                    'reviewableType' => RecordedCourse::class,
                    'reviewableId' => $course->id,
                ])
                ->assertStatus(200)
                ->assertSet('reviewType', 'course');
        });

        it('initializes with default rating of 0', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->assertSet('rating', 0)
                ->assertSet('comment', '');
        });
    });

    describe('modal functionality', function () {
        it('can open modal', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->call('openModal')
                ->assertSet('showModal', true);
        });

        it('can close modal', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->set('showModal', true)
                ->call('closeModal')
                ->assertSet('showModal', false);
        });
    });

    describe('rating functionality', function () {
        it('can set rating value', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->call('setRating', 5)
                ->assertSet('rating', 5);
        });

        it('accepts rating values from 1 to 5', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            foreach ([1, 2, 3, 4, 5] as $rating) {
                Livewire::actingAs($student)
                    ->test(ReviewForm::class, [
                        'reviewType' => 'teacher',
                        'reviewableType' => QuranTeacherProfile::class,
                        'reviewableId' => $teacher->id,
                    ])
                    ->call('setRating', $rating)
                    ->assertSet('rating', $rating);
            }
        });
    });

    describe('validation', function () {
        it('requires rating to submit review', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->set('rating', 0)
                ->set('comment', 'Great teacher!')
                ->call('submitReview')
                ->assertHasErrors(['rating']);
        });

        it('validates rating is at least 1', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->set('rating', 0)
                ->call('submitReview')
                ->assertHasErrors(['rating' => 'min']);
        });

        it('validates rating is at most 5', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->set('rating', 6)
                ->call('submitReview')
                ->assertHasErrors(['rating' => 'max']);
        });

        it('validates comment does not exceed 1000 characters', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->set('rating', 5)
                ->set('comment', str_repeat('a', 1001))
                ->call('submitReview')
                ->assertHasErrors(['comment' => 'max']);
        });

        it('allows optional comment', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->set('rating', 5)
                ->set('comment', '')
                ->call('submitReview')
                ->assertHasNoErrors(['comment']);
        });
    });

    describe('reviewable types', function () {
        it('handles Quran teacher reviews', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->assertStatus(200);
        });

        it('handles Academic teacher reviews', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = AcademicTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => AcademicTeacherProfile::class,
                    'reviewableId' => $teacher->id,
                ])
                ->assertStatus(200);
        });

        it('handles recorded course reviews', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'course',
                    'reviewableType' => RecordedCourse::class,
                    'reviewableId' => $course->id,
                ])
                ->assertStatus(200);
        });

        it('handles interactive course reviews', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'course',
                    'reviewableType' => InteractiveCourse::class,
                    'reviewableId' => $course->id,
                ])
                ->assertStatus(200);
        });
    });

    describe('authorization', function () {
        it('requires authentication to access', function () {
            $teacher = QuranTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::test(ReviewForm::class, [
                'reviewType' => 'teacher',
                'reviewableType' => QuranTeacherProfile::class,
                'reviewableId' => $teacher->id,
            ])
                ->assertSet('canReview', false);
        });
    });

    describe('edge cases', function () {
        it('handles non-existent reviewable gracefully', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(ReviewForm::class, [
                    'reviewType' => 'teacher',
                    'reviewableType' => QuranTeacherProfile::class,
                    'reviewableId' => 99999,
                ])
                ->assertSet('canReview', false);
        });
    });
});
