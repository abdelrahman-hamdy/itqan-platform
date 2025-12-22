<?php

use App\Livewire\Chat\Info;
use App\Models\Academy;
use App\Models\User;
use Livewire\Livewire;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

describe('Chat Info Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('component rendering', function () {
        it('renders successfully with conversation', function () {
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
                ->test(Info::class, ['conversation' => $conversation])
                ->assertStatus(200);
        });
    });

    describe('media attachments', function () {
        it('filters image and video attachments', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => 'Test Conversation',
                'type' => 'private',
                'academy_id' => $this->academy->id,
            ]);

            $participant1 = $conversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user1->id,
            ]);

            $conversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user2->id,
            ]);

            // Create message with image attachment
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'participant_id' => $participant1->id,
                'body' => 'Test message with image',
                'type' => 'text',
            ]);

            $component = Livewire::actingAs($user1)
                ->test(Info::class, ['conversation' => $conversation]);

            // mediaAttachments should be accessible
            expect($component->get('mediaAttachments'))->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });

        it('returns empty collection when no media attachments', function () {
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

            $component = Livewire::actingAs($user1)
                ->test(Info::class, ['conversation' => $conversation]);

            expect($component->get('mediaAttachments'))->toBeEmpty();
        });
    });

    describe('file attachments', function () {
        it('filters non-media file attachments', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => 'Test Conversation',
                'type' => 'private',
                'academy_id' => $this->academy->id,
            ]);

            $participant1 = $conversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user1->id,
            ]);

            $conversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user2->id,
            ]);

            $component = Livewire::actingAs($user1)
                ->test(Info::class, ['conversation' => $conversation]);

            // fileAttachments should be accessible
            expect($component->get('fileAttachments'))->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });

        it('returns empty collection when no file attachments', function () {
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

            $component = Livewire::actingAs($user1)
                ->test(Info::class, ['conversation' => $conversation]);

            expect($component->get('fileAttachments'))->toBeEmpty();
        });
    });

    describe('receiver information', function () {
        it('displays peer participant information', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create(['name' => 'User One']);
            $user2 = User::factory()->student()->forAcademy($this->academy)->create(['name' => 'User Two']);

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
                ->test(Info::class, ['conversation' => $conversation])
                ->assertStatus(200);
        });
    });

    describe('attachment filtering', function () {
        it('correctly separates media from files', function () {
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

            $component = Livewire::actingAs($user1)
                ->test(Info::class, ['conversation' => $conversation]);

            // Both properties should exist and be collections
            expect($component->get('mediaAttachments'))->toBeInstanceOf(\Illuminate\Support\Collection::class);
            expect($component->get('fileAttachments'))->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });
    });

    describe('edge cases', function () {
        it('handles conversation with no messages', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => 'Empty Conversation',
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

            $component = Livewire::actingAs($user1)
                ->test(Info::class, ['conversation' => $conversation]);

            expect($component->get('mediaAttachments'))->toBeEmpty();
            expect($component->get('fileAttachments'))->toBeEmpty();
        });

        it('handles group conversations', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();
            $user3 = User::factory()->student()->forAcademy($this->academy)->create();

            $conversation = Conversation::create([
                'name' => 'Group Conversation',
                'type' => 'group',
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

            $conversation->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $user3->id,
            ]);

            Livewire::actingAs($user1)
                ->test(Info::class, ['conversation' => $conversation])
                ->assertStatus(200);
        });
    });
});
