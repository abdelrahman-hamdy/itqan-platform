<?php

use App\Enums\CertificateTemplateStyle;
use App\Livewire\IssueCertificateModal;
use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Livewire\Livewire;

describe('Issue Certificate Modal Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('component rendering', function () {
        it('renders successfully for quran subscription', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'quran',
                    'subscriptionId' => $subscription->id,
                ])
                ->assertStatus(200)
                ->assertSet('subscriptionType', 'quran')
                ->assertSet('subscriptionId', $subscription->id);
        });

        it('renders successfully for academic subscription', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'academic',
                    'subscriptionId' => $subscription->id,
                ])
                ->assertStatus(200)
                ->assertSet('subscriptionType', 'academic');
        });

        it('renders successfully for group circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'group_quran',
                    'circleId' => $circle->id,
                ])
                ->assertStatus(200)
                ->assertSet('subscriptionType', 'group_quran')
                ->assertSet('circleId', $circle->id);
        });
    });

    describe('modal functionality', function () {
        it('can open modal via event', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class)
                ->call('openModal', 'quran', $subscription->id)
                ->assertSet('showModal', true)
                ->assertSet('subscriptionType', 'quran')
                ->assertSet('subscriptionId', $subscription->id);
        });

        it('can close modal', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class)
                ->set('showModal', true)
                ->call('closeModal')
                ->assertSet('showModal', false)
                ->assertSet('previewMode', false);
        });

        it('resets form fields when closing modal', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class)
                ->set('showModal', true)
                ->set('achievementText', 'Test achievement')
                ->set('templateStyle', 'template_2')
                ->call('closeModal')
                ->assertSet('achievementText', '')
                ->assertSet('templateStyle', 'template_1');
        });
    });

    describe('form fields', function () {
        it('initializes with default template style', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class)
                ->assertSet('templateStyle', 'template_1')
                ->assertSet('achievementText', '');
        });

        it('can set achievement text', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class)
                ->call('setExampleText', 'Test achievement text')
                ->assertSet('achievementText', 'Test achievement text')
                ->assertDispatched('achievement-text-updated');
        });

        it('can change template style', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class)
                ->set('templateStyle', 'template_2')
                ->assertSet('templateStyle', 'template_2');
        });
    });

    describe('validation', function () {
        it('requires achievement text', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'quran',
                    'subscriptionId' => $subscription->id,
                ])
                ->set('achievementText', '')
                ->call('togglePreview')
                ->assertHasErrors(['achievementText' => 'required']);
        });

        it('validates achievement text minimum length', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'quran',
                    'subscriptionId' => $subscription->id,
                ])
                ->set('achievementText', 'Short')
                ->call('togglePreview')
                ->assertHasErrors(['achievementText' => 'min']);
        });

        it('validates achievement text maximum length', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'quran',
                    'subscriptionId' => $subscription->id,
                ])
                ->set('achievementText', str_repeat('a', 1001))
                ->call('togglePreview')
                ->assertHasErrors(['achievementText' => 'max']);
        });

        it('requires selected students for group certificates', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create(['academy_id' => $this->academy->id]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'group_quran',
                    'circleId' => $circle->id,
                ])
                ->set('achievementText', 'Test achievement text for group')
                ->set('selectedStudents', [])
                ->call('togglePreview')
                ->assertHasErrors(['selectedStudents']);
        });
    });

    describe('group certificate functionality', function () {
        it('loads circle students for group quran certificates', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle->students()->attach($student->studentProfile->id);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'group_quran',
                    'circleId' => $circle->id,
                ])
                ->assertStatus(200);
        });

        it('can select individual students', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class)
                ->call('openModal', 'group_quran', null, $circle->id)
                ->set('selectedStudents', [(string)$student->id])
                ->assertSet('selectedStudents', [(string)$student->id]);
        });

        it('can select all students', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class)
                ->set('students', [
                    ['id' => 1, 'subscription_id' => '1', 'name' => 'Student 1'],
                    ['id' => 2, 'subscription_id' => '2', 'name' => 'Student 2'],
                ])
                ->call('selectAllStudents')
                ->assertSet('selectAll', true)
                ->assertSet('selectedStudents', ['1', '2']);
        });

        it('can toggle select all', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class)
                ->set('students', [
                    ['id' => 1, 'subscription_id' => '1', 'name' => 'Student 1'],
                    ['id' => 2, 'subscription_id' => '2', 'name' => 'Student 2'],
                ])
                ->set('selectAll', true)
                ->call('toggleSelectAll')
                ->assertSet('selectedStudents', ['1', '2']);
        });
    });

    describe('preview mode', function () {
        it('can toggle preview mode', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'quran',
                    'subscriptionId' => $subscription->id,
                ])
                ->set('achievementText', 'Valid achievement text here')
                ->call('togglePreview')
                ->assertSet('previewMode', true)
                ->call('togglePreview')
                ->assertSet('previewMode', false);
        });
    });

    describe('template styles', function () {
        it('provides template style options', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $component = Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class);

            $templateStyles = $component->get('templateStyles');
            expect($templateStyles)->toBeArray();
            expect($templateStyles)->toHaveKey('template_1');
        });
    });

    describe('authorization', function () {
        it('allows super admin to issue certificates', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($superAdmin)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'quran',
                    'subscriptionId' => $subscription->id,
                ])
                ->assertStatus(200);
        });

        it('allows admin to issue certificates', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($admin)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'quran',
                    'subscriptionId' => $subscription->id,
                ])
                ->assertStatus(200);
        });

        it('allows quran teacher to issue certificates', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'quran',
                    'subscriptionId' => $subscription->id,
                ])
                ->assertStatus(200);
        });
    });

    describe('property accessors', function () {
        it('correctly identifies group subscriptions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create(['academy_id' => $this->academy->id]);

            $component = Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'group_quran',
                    'circleId' => $circle->id,
                ]);

            expect($component->get('isGroup'))->toBeTrue();
        });

        it('correctly identifies non-group subscriptions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->studentProfile->id,
            ]);

            $component = Livewire::actingAs($teacher)
                ->test(IssueCertificateModal::class, [
                    'subscriptionType' => 'quran',
                    'subscriptionId' => $subscription->id,
                ]);

            expect($component->get('isGroup'))->toBeFalse();
        });
    });
});
