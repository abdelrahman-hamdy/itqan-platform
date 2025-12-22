<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('Chat API', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->academy = createAcademy();
        $this->user = createUser('student', $this->academy);
        $this->otherUser = createUser('student', $this->academy);
        Sanctum::actingAs($this->user);
    });

    describe('GET /api/v1/common/chat/conversations', function () {
        it('retrieves all conversations for authenticated user', function () {
            // Create conversations
            $conversation1 = Conversation::create(['type' => 'private']);
            $conversation1->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $conversation1->participants()->create([
                'participantable_id' => $this->otherUser->id,
                'participantable_type' => User::class,
            ]);

            // Add a message
            Message::create([
                'conversation_id' => $conversation1->id,
                'senderable_id' => $this->otherUser->id,
                'senderable_type' => User::class,
                'body' => 'Hello!',
                'type' => 'text',
            ]);

            $response = $this->getJson('/api/v1/common/chat/conversations');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'conversations' => [
                            '*' => [
                                'id',
                                'type',
                                'title',
                                'participants',
                                'last_message',
                                'unread_count',
                                'updated_at',
                            ],
                        ],
                        'pagination',
                    ],
                ]);

            expect($response->json('data.conversations'))->toHaveCount(1);
        });

        it('shows unread message count correctly', function () {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $conversation->participants()->create([
                'participantable_id' => $this->otherUser->id,
                'participantable_type' => User::class,
            ]);

            // Create unread messages
            Message::create([
                'conversation_id' => $conversation->id,
                'senderable_id' => $this->otherUser->id,
                'senderable_type' => User::class,
                'body' => 'Unread message 1',
                'type' => 'text',
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'senderable_id' => $this->otherUser->id,
                'senderable_type' => User::class,
                'body' => 'Unread message 2',
                'type' => 'text',
            ]);

            $response = $this->getJson('/api/v1/common/chat/conversations');

            $response->assertStatus(200);
            $conversations = $response->json('data.conversations');
            expect($conversations[0]['unread_count'])->toBe(2);
        });

        it('supports pagination', function () {
            // Create multiple conversations
            for ($i = 0; $i < 25; $i++) {
                $user = createUser('student', $this->academy);
                $conversation = Conversation::create(['type' => 'private']);
                $conversation->participants()->create([
                    'participantable_id' => $this->user->id,
                    'participantable_type' => User::class,
                ]);
                $conversation->participants()->create([
                    'participantable_id' => $user->id,
                    'participantable_type' => User::class,
                ]);
            }

            $response = $this->getJson('/api/v1/common/chat/conversations?per_page=10');

            $response->assertStatus(200);
            expect($response->json('data.pagination.per_page'))->toBe(10)
                ->and($response->json('data.conversations'))->toHaveCount(10);
        });

        it('requires authentication', function () {
            Sanctum::actingAs(null);

            $response = $this->getJson('/api/v1/common/chat/conversations');

            $response->assertStatus(401);
        });
    });

    describe('POST /api/v1/common/chat/conversations', function () {
        it('creates new conversation with initial message', function () {
            $response = $this->postJson('/api/v1/common/chat/conversations', [
                'participant_id' => $this->otherUser->id,
                'message' => 'Hello, this is the first message!',
            ]);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'is_new' => true,
                    ],
                ]);

            expect($response->json('data.conversation_id'))->not->toBeNull();

            $conversation = Conversation::find($response->json('data.conversation_id'));
            expect($conversation)->not->toBeNull()
                ->and($conversation->participants)->toHaveCount(2)
                ->and($conversation->messages)->toHaveCount(1);
        });

        it('returns existing conversation if it already exists', function () {
            // Create existing conversation
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $conversation->participants()->create([
                'participantable_id' => $this->otherUser->id,
                'participantable_type' => User::class,
            ]);

            $response = $this->postJson('/api/v1/common/chat/conversations', [
                'participant_id' => $this->otherUser->id,
                'message' => 'Another message',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'conversation_id' => $conversation->id,
                        'is_new' => false,
                    ],
                ]);
        });

        it('validates required participant_id', function () {
            $response = $this->postJson('/api/v1/common/chat/conversations', [
                'message' => 'Test message',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['participant_id']);
        });

        it('validates participant exists', function () {
            $response = $this->postJson('/api/v1/common/chat/conversations', [
                'participant_id' => 99999,
                'message' => 'Test message',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['participant_id']);
        });

        it('can create conversation without initial message', function () {
            $response = $this->postJson('/api/v1/common/chat/conversations', [
                'participant_id' => $this->otherUser->id,
            ]);

            $response->assertStatus(201);
            $conversation = Conversation::find($response->json('data.conversation_id'));
            expect($conversation->messages)->toHaveCount(0);
        });
    });

    describe('GET /api/v1/common/chat/conversations/{id}', function () {
        it('retrieves specific conversation details', function () {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $conversation->participants()->create([
                'participantable_id' => $this->otherUser->id,
                'participantable_type' => User::class,
            ]);

            $response = $this->getJson("/api/v1/common/chat/conversations/{$conversation->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'conversation' => [
                            'id',
                            'type',
                            'title',
                            'participants',
                            'created_at',
                        ],
                    ],
                ]);
        });

        it('returns 404 for non-existent conversation', function () {
            $response = $this->getJson('/api/v1/common/chat/conversations/99999');

            $response->assertStatus(404);
        });

        it('returns 404 when user is not participant', function () {
            $conversation = Conversation::create(['type' => 'private']);
            $user1 = createUser('student', $this->academy);
            $user2 = createUser('student', $this->academy);

            $conversation->participants()->create([
                'participantable_id' => $user1->id,
                'participantable_type' => User::class,
            ]);
            $conversation->participants()->create([
                'participantable_id' => $user2->id,
                'participantable_type' => User::class,
            ]);

            $response = $this->getJson("/api/v1/common/chat/conversations/{$conversation->id}");

            $response->assertStatus(404);
        });
    });

    describe('GET /api/v1/common/chat/conversations/{id}/messages', function () {
        it('retrieves messages for conversation', function () {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $conversation->participants()->create([
                'participantable_id' => $this->otherUser->id,
                'participantable_type' => User::class,
            ]);

            // Create messages
            for ($i = 0; $i < 5; $i++) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'senderable_id' => $this->user->id,
                    'senderable_type' => User::class,
                    'body' => "Message $i",
                    'type' => 'text',
                ]);
            }

            $response = $this->getJson("/api/v1/common/chat/conversations/{$conversation->id}/messages");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'messages' => [
                            '*' => [
                                'id',
                                'body',
                                'type',
                                'attachments',
                                'is_mine',
                                'sender',
                                'created_at',
                            ],
                        ],
                        'pagination',
                    ],
                ]);

            expect($response->json('data.messages'))->toHaveCount(5);
        });

        it('marks messages as mine correctly', function () {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $conversation->participants()->create([
                'participantable_id' => $this->otherUser->id,
                'participantable_type' => User::class,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'senderable_id' => $this->user->id,
                'senderable_type' => User::class,
                'body' => 'My message',
                'type' => 'text',
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'senderable_id' => $this->otherUser->id,
                'senderable_type' => User::class,
                'body' => 'Their message',
                'type' => 'text',
            ]);

            $response = $this->getJson("/api/v1/common/chat/conversations/{$conversation->id}/messages");

            $messages = $response->json('data.messages');
            $myMessage = collect($messages)->firstWhere('body', 'My message');
            $theirMessage = collect($messages)->firstWhere('body', 'Their message');

            expect($myMessage['is_mine'])->toBeTrue()
                ->and($theirMessage['is_mine'])->toBeFalse();
        });

        it('supports pagination', function () {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);

            for ($i = 0; $i < 60; $i++) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'senderable_id' => $this->user->id,
                    'senderable_type' => User::class,
                    'body' => "Message $i",
                    'type' => 'text',
                ]);
            }

            $response = $this->getJson("/api/v1/common/chat/conversations/{$conversation->id}/messages?per_page=20");

            $response->assertStatus(200);
            expect($response->json('data.pagination.per_page'))->toBe(20)
                ->and($response->json('data.messages'))->toHaveCount(20);
        });
    });

    describe('POST /api/v1/common/chat/conversations/{id}/messages', function () {
        beforeEach(function () {
            $this->conversation = Conversation::create(['type' => 'private']);
            $this->conversation->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $this->conversation->participants()->create([
                'participantable_id' => $this->otherUser->id,
                'participantable_type' => User::class,
            ]);
        });

        it('sends text message', function () {
            $response = $this->postJson("/api/v1/common/chat/conversations/{$this->conversation->id}/messages", [
                'body' => 'Hello, this is a test message!',
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'message' => [
                            'id',
                            'body',
                            'type',
                            'is_mine',
                            'created_at',
                        ],
                    ],
                ]);

            expect($response->json('data.message.body'))->toBe('Hello, this is a test message!')
                ->and($response->json('data.message.is_mine'))->toBeTrue();
        });

        it('sends message with file attachment', function () {
            $file = UploadedFile::fake()->image('test.jpg', 800, 600);

            $response = $this->postJson("/api/v1/common/chat/conversations/{$this->conversation->id}/messages", [
                'body' => 'Check out this image',
                'attachment' => $file,
            ]);

            $response->assertStatus(201);
            expect($response->json('data.message.attachments'))->not->toBeEmpty()
                ->and($response->json('data.message.type'))->toBe('image');

            Storage::disk('public')->assertExists('chat-attachments/' . $this->user->id . '/' . $file->hashName());
        });

        it('validates max file size', function () {
            $file = UploadedFile::fake()->create('large.pdf', 11000); // 11MB, exceeds 10MB limit

            $response = $this->postJson("/api/v1/common/chat/conversations/{$this->conversation->id}/messages", [
                'attachment' => $file,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['attachment']);
        });

        it('requires either body or attachment', function () {
            $response = $this->postJson("/api/v1/common/chat/conversations/{$this->conversation->id}/messages", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['body']);
        });

        it('returns 404 for non-existent conversation', function () {
            $response = $this->postJson('/api/v1/common/chat/conversations/99999/messages', [
                'body' => 'Test',
            ]);

            $response->assertStatus(404);
        });

        it('returns 404 when user is not participant', function () {
            $conversation = Conversation::create(['type' => 'private']);
            $user1 = createUser('student', $this->academy);
            $user2 = createUser('student', $this->academy);

            $conversation->participants()->create([
                'participantable_id' => $user1->id,
                'participantable_type' => User::class,
            ]);
            $conversation->participants()->create([
                'participantable_id' => $user2->id,
                'participantable_type' => User::class,
            ]);

            $response = $this->postJson("/api/v1/common/chat/conversations/{$conversation->id}/messages", [
                'body' => 'Test',
            ]);

            $response->assertStatus(404);
        });
    });

    describe('PUT /api/v1/common/chat/conversations/{id}/read', function () {
        it('marks conversation messages as read', function () {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $conversation->participants()->create([
                'participantable_id' => $this->otherUser->id,
                'participantable_type' => User::class,
            ]);

            // Create unread messages from other user
            for ($i = 0; $i < 3; $i++) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'senderable_id' => $this->otherUser->id,
                    'senderable_type' => User::class,
                    'body' => "Message $i",
                    'type' => 'text',
                ]);
            }

            $response = $this->putJson("/api/v1/common/chat/conversations/{$conversation->id}/read");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'marked' => true,
                        'count' => 3,
                    ],
                ]);
        });
    });

    describe('GET /api/v1/common/chat/unread-count', function () {
        it('returns total unread message count across all conversations', function () {
            // Conversation 1 with 3 unread messages
            $conversation1 = Conversation::create(['type' => 'private']);
            $conversation1->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $conversation1->participants()->create([
                'participantable_id' => $this->otherUser->id,
                'participantable_type' => User::class,
            ]);

            for ($i = 0; $i < 3; $i++) {
                Message::create([
                    'conversation_id' => $conversation1->id,
                    'senderable_id' => $this->otherUser->id,
                    'senderable_type' => User::class,
                    'body' => "Message $i",
                    'type' => 'text',
                ]);
            }

            // Conversation 2 with 2 unread messages
            $user3 = createUser('student', $this->academy);
            $conversation2 = Conversation::create(['type' => 'private']);
            $conversation2->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);
            $conversation2->participants()->create([
                'participantable_id' => $user3->id,
                'participantable_type' => User::class,
            ]);

            for ($i = 0; $i < 2; $i++) {
                Message::create([
                    'conversation_id' => $conversation2->id,
                    'senderable_id' => $user3->id,
                    'senderable_type' => User::class,
                    'body' => "Message $i",
                    'type' => 'text',
                ]);
            }

            $response = $this->getJson('/api/v1/common/chat/unread-count');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'unread_count' => 5,
                    ],
                ]);
        });

        it('does not count own messages as unread', function () {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->participants()->create([
                'participantable_id' => $this->user->id,
                'participantable_type' => User::class,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'senderable_id' => $this->user->id,
                'senderable_type' => User::class,
                'body' => 'My own message',
                'type' => 'text',
            ]);

            $response = $this->getJson('/api/v1/common/chat/unread-count');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => ['unread_count' => 0],
                ]);
        });
    });
});
