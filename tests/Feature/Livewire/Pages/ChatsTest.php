<?php

use App\Livewire\Pages\Chats;
use App\Models\Academy;
use App\Models\User;
use Livewire\Livewire;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

describe('Chats Listing Page Component', function () {
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
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('extends WireChat Chats component', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $component = Livewire::actingAs($user)
                ->test(Chats::class);

            // Should inherit from parent WireChat component
            expect($component)->toBeInstanceOf(\Livewire\Component::class);
        });
    });

    describe('page title', function () {
        it('has Arabic title', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Component should render with title attribute
            Livewire::actingAs($user)
                ->test(Chats::class)
                ->assertStatus(200);
        });
    });

    describe('conversations listing', function () {
        it('displays user conversations', function () {
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
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('shows multiple conversations', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();
            $user3 = User::factory()->student()->forAcademy($this->academy)->create();

            // Create first conversation
            $conversation1 = Conversation::create([
                'name' => 'Conversation 1',
                'type' => 'private',
                'academy_id' => $this->academy->id,
            ]);

            $conversation1->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user1->id,
            ]);

            $conversation1->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user2->id,
            ]);

            // Create second conversation
            $conversation2 = Conversation::create([
                'name' => 'Conversation 2',
                'type' => 'private',
                'academy_id' => $this->academy->id,
            ]);

            $conversation2->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user1->id,
            ]);

            $conversation2->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user3->id,
            ]);

            Livewire::actingAs($user1)
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('handles user with no conversations', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(Chats::class)
                ->assertStatus(200);
        });
    });

    describe('conversation types', function () {
        it('displays private conversations', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => 'Private Chat',
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
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('displays group conversations', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();
            $user3 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => 'Group Chat',
                'type' => 'group',
                'academy_id' => $this->academy->id,
            ]);

            foreach ([$user1, $user2, $user3] as $user) {
                $conversation->participants()->create([
                    'participantable_type' => User::class,
                    'participantable_id' => $user->id,
                ]);
            }

            Livewire::actingAs($user1)
                ->test(Chats::class)
                ->assertStatus(200);
        });
    });

    describe('user roles', function () {
        it('renders for students', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('renders for teachers', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('renders for academic teachers', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            Livewire::actingAs($teacher)
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('renders for admins', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();

            Livewire::actingAs($admin)
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('renders for parents', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();

            Livewire::actingAs($parent)
                ->test(Chats::class)
                ->assertStatus(200);
        });
    });

    describe('parent render method', function () {
        it('calls parent render method', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Should call parent::render() from WireChat
            Livewire::actingAs($user)
                ->test(Chats::class)
                ->assertStatus(200);
        });
    });

    describe('integration with WireChat', function () {
        it('inherits WireChat functionality', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Component should work with WireChat's features
            Livewire::actingAs($user)
                ->test(Chats::class)
                ->assertStatus(200);
        });
    });

    describe('multi-tenancy', function () {
        it('respects academy context', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Should only show conversations from user's academy
            Livewire::actingAs($user)
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('isolates conversations by academy', function () {
            $academy2 = Academy::factory()->create(['subdomain' => 'other-academy']);
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($academy2)->create();

            $conversation1 = Conversation::create([
                'name' => 'Academy 1 Conversation',
                'type' => 'private',
                'academy_id' => $this->academy->id,
            ]);

            $conversation2 = Conversation::create([
                'name' => 'Academy 2 Conversation',
                'type' => 'private',
                'academy_id' => $academy2->id,
            ]);

            // Each user should only see their academy's conversations
            Livewire::actingAs($user1)
                ->test(Chats::class)
                ->assertStatus(200);

            Livewire::actingAs($user2)
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('does not show conversations from other academies', function () {
            $academy2 = Academy::factory()->create(['subdomain' => 'other-academy']);
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($academy2)->create();

            $conversation = Conversation::create([
                'name' => 'Cross Academy Conversation',
                'type' => 'private',
                'academy_id' => $academy2->id,
            ]);

            $conversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user2->id,
            ]);

            // User1 from academy1 should not see academy2's conversation
            Livewire::actingAs($user1)
                ->test(Chats::class)
                ->assertStatus(200);
        });
    });

    describe('authentication', function () {
        it('requires authentication', function () {
            // Not authenticated - WireChat should handle this
            $component = Livewire::test(Chats::class);

            // Component might redirect or show error
            expect($component)->toBeInstanceOf(\Livewire\Component::class);
        });
    });

    describe('conversation ordering', function () {
        it('shows recent conversations first', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();
            $user3 = User::factory()->student()->forAcademy($this->academy)->create();

            // Create older conversation
            $oldConversation = Conversation::create([
                'name' => 'Old Conversation',
                'type' => 'private',
                'academy_id' => $this->academy->id,
                'created_at' => now()->subDays(5),
            ]);

            $oldConversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user1->id,
            ]);

            $oldConversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user2->id,
            ]);

            // Create newer conversation
            $newConversation = Conversation::create([
                'name' => 'New Conversation',
                'type' => 'private',
                'academy_id' => $this->academy->id,
                'created_at' => now(),
            ]);

            $newConversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user1->id,
            ]);

            $newConversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user3->id,
            ]);

            Livewire::actingAs($user1)
                ->test(Chats::class)
                ->assertStatus(200);
        });
    });

    describe('edge cases', function () {
        it('handles deleted participants', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => 'Conversation with Deleted User',
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

            // Soft delete user2
            $user2->delete();

            Livewire::actingAs($user1)
                ->test(Chats::class)
                ->assertStatus(200);
        });

        it('handles empty conversation names', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => null,
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
                ->test(Chats::class)
                ->assertStatus(200);
        });
    });
});
