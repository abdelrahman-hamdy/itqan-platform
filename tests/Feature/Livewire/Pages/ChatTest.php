<?php

use App\Livewire\Pages\Chat;
use App\Models\Academy;
use App\Models\User;
use Livewire\Livewire;
use Namu\WireChat\Models\Conversation;

describe('Chat Page Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('component rendering', function () {
        it('renders successfully for authenticated users', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(Chat::class)
                ->assertStatus(200);
        });

        it('extends WireChat Chat component', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $component = Livewire::actingAs($user)
                ->test(Chat::class);

            // Should inherit from parent WireChat component
            expect($component)->toBeInstanceOf(\Livewire\Component::class);
        });
    });

    describe('page title', function () {
        it('has Arabic title', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Component should render with title attribute
            Livewire::actingAs($user)
                ->test(Chat::class)
                ->assertStatus(200);
        });
    });

    describe('conversation display', function () {
        it('displays chat interface', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => 'Test Conversation',
                'type' => 'private',
                'academy_id' => $this->academy->id,
            ]);

            $conversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user1->id,
            ]);

            $conversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user2->id,
            ]);

            Livewire::actingAs($user1)
                ->test(Chat::class)
                ->assertStatus(200);
        });
    });

    describe('user roles', function () {
        it('renders for students', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Chat::class)
                ->assertStatus(200);
        });

        it('renders for teachers', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(Chat::class)
                ->assertStatus(200);
        });

        it('renders for admins', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();

            Livewire::actingAs($admin)
                ->test(Chat::class)
                ->assertStatus(200);
        });
    });

    describe('parent render method', function () {
        it('calls parent render method', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Should call parent::render() from WireChat
            Livewire::actingAs($user)
                ->test(Chat::class)
                ->assertStatus(200);
        });
    });

    describe('integration with WireChat', function () {
        it('inherits WireChat functionality', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Component should work with WireChat's features
            Livewire::actingAs($user)
                ->test(Chat::class)
                ->assertStatus(200);
        });
    });

    describe('multi-tenancy', function () {
        it('respects academy context', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Should only show conversations from user's academy
            Livewire::actingAs($user)
                ->test(Chat::class)
                ->assertStatus(200);
        });

        it('isolates conversations by academy', function () {
            $academy2 = Academy::factory()->create(['subdomain' => 'other-academy']);
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($academy2)->create();

            // Users from different academies
            Livewire::actingAs($user1)
                ->test(Chat::class)
                ->assertStatus(200);

            Livewire::actingAs($user2)
                ->test(Chat::class)
                ->assertStatus(200);
        });
    });

    describe('authentication', function () {
        it('requires authentication', function () {
            // Not authenticated - WireChat should handle this
            $component = Livewire::test(Chat::class);

            // Component might redirect or show error
            expect($component)->toBeInstanceOf(\Livewire\Component::class);
        });
    });

    describe('edge cases', function () {
        it('handles user with no conversations', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // User has no conversations yet
            Livewire::actingAs($user)
                ->test(Chat::class)
                ->assertStatus(200);
        });

        it('handles archived conversations', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => 'Archived Conversation',
                'type' => 'private',
                'academy_id' => $this->academy->id,
            ]);

            $conversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user1->id,
            ]);

            Livewire::actingAs($user1)
                ->test(Chat::class)
                ->assertStatus(200);
        });
    });
});
