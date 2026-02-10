<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\User;
use App\Services\ChatPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

class ChatController extends Controller
{
    use ApiResponses;

    protected ChatPermissionService $chatPermissionService;

    public function __construct(ChatPermissionService $chatPermissionService)
    {
        $this->chatPermissionService = $chatPermissionService;
    }

    /**
     * Get all conversations for the user.
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('participantable_id', $user->id)
                ->where('participantable_type', User::class)
                ->whereNull('archived_at'); // Exclude archived conversations
        });

        // If supervisor: filter by supervisor_id on chat_groups
        // Supervisors should only see supervised conversations
        if ($user->isSupervisor()) {
            $query->whereHas('chatGroup', function ($q) use ($user) {
                $q->where('supervisor_id', $user->supervisorProfile?->id);
            });
        }

        $conversations = $query
            ->with(['participants.participantable', 'lastMessage.sendable'])
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success([
            'conversations' => collect($conversations->items())->map(function ($conversation) use ($user) {
                $otherParticipants = $conversation->participants
                    ->filter(fn ($p) => ! ($p->participantable_id === $user->id && $p->participantable_type === User::class))
                    ->map(fn ($p) => [
                        'id' => $p->participantable_id,
                        'name' => $p->participantable?->name,
                        'user_type' => $p->participantable?->user_type,
                        'avatar' => $p->participantable?->avatar
                            ? asset('storage/'.$p->participantable->avatar)
                            : null,
                    ])
                    ->values();

                // Use WireChat's built-in unread count method
                $unreadCount = $conversation->getUnreadCountFor($user);

                // Check if this is a supervised chat
                $chatGroup = \App\Models\ChatGroup::where('conversation_id', $conversation->id)->first();
                $isSupervisedChat = $chatGroup !== null;

                return [
                    'id' => $conversation->id,
                    'type' => $conversation->type,
                    'title' => $conversation->name ?? $otherParticipants->first()['name'] ?? 'محادثة',
                    'participants' => $otherParticipants->toArray(),
                    'is_supervised' => $isSupervisedChat,
                    'supervised_info' => $isSupervisedChat ? [
                        'supervisor_id' => $chatGroup->supervisor_id,
                        'teacher_id' => $chatGroup->teacher_id,
                        'student_id' => $chatGroup->student_id,
                    ] : null,
                    'last_message' => $conversation->lastMessage ? [
                        'id' => $conversation->lastMessage->id,
                        'body' => $conversation->lastMessage->body,
                        'type' => $conversation->lastMessage->type,
                        'is_mine' => $conversation->lastMessage->sendable_id === $user->id,
                        'created_at' => $conversation->lastMessage->created_at->toISOString(),
                    ] : null,
                    'unread_count' => $unreadCount,
                    'updated_at' => $conversation->updated_at->toISOString(),
                    'current_user_id' => $user->id,
                ];
            })->toArray(),
            'pagination' => PaginationHelper::fromPaginator($conversations),
        ], __('Conversations retrieved successfully'));
    }

    /**
     * Create a new conversation.
     */
    public function createConversation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'participant_id' => ['required', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $participantId = $request->participant_id;

        // Get target user
        $targetUser = User::find($participantId);

        if (! $targetUser) {
            return $this->notFound(__('User not found.'));
        }

        // Enforce supervised chat policy
        if (! $this->chatPermissionService->canStartPrivateChat($user, $targetUser)) {
            return $this->error(
                __('Private chats between teachers and students are not allowed. Please use supervised group chats.'),
                403,
                'FORBIDDEN'
            );
        }

        // Check if conversation already exists
        $existingConversation = Conversation::where('type', 'private')
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->whereHas('participants', function ($q) use ($participantId) {
                $q->where('participantable_id', $participantId)
                    ->where('participantable_type', User::class);
            })
            ->first();

        if ($existingConversation) {
            // Send message to existing conversation if provided
            if ($request->filled('message')) {
                $message = Message::create([
                    'conversation_id' => $existingConversation->id,
                    'sendable_id' => $user->id,
                    'sendable_type' => User::class,
                    'body' => $request->message,
                    'type' => 'text',
                ]);

                $existingConversation->touch();
            }

            return $this->success([
                'conversation_id' => $existingConversation->id,
                'is_new' => false,
            ], __('Conversation found'));
        }

        // Create new conversation
        $conversation = Conversation::create([
            'type' => 'private',
        ]);

        // Add participants
        $conversation->participants()->create([
            'participantable_id' => $user->id,
            'participantable_type' => User::class,
        ]);

        $conversation->participants()->create([
            'participantable_id' => $participantId,
            'participantable_type' => User::class,
        ]);

        // Send initial message if provided
        if ($request->filled('message')) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sendable_id' => $user->id,
                'sendable_type' => User::class,
                'body' => $request->message,
                'type' => 'text',
            ]);
        }

        return $this->created([
            'conversation_id' => $conversation->id,
            'is_new' => true,
        ], __('Conversation created'));
    }

    /**
     * Get a specific conversation.
     */
    public function showConversation(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->with(['participants.participantable'])
            ->first();

        if (! $conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        $otherParticipants = $conversation->participants
            ->filter(fn ($p) => ! ($p->participantable_id === $user->id && $p->participantable_type === User::class))
            ->map(fn ($p) => [
                'id' => $p->participantable_id,
                'name' => $p->participantable?->name,
                'avatar' => $p->participantable?->avatar
                    ? asset('storage/'.$p->participantable->avatar)
                    : null,
            ])
            ->values();

        return $this->success([
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'title' => $conversation->name ?? $otherParticipants->first()['name'] ?? 'محادثة',
                'participants' => $otherParticipants->toArray(),
                'created_at' => $conversation->created_at->toISOString(),
            ],
        ], __('Conversation retrieved successfully'));
    }

    /**
     * Get messages for a conversation.
     */
    public function messages(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->first();

        if (! $conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        $messages = Message::where('conversation_id', $id)
            ->with(['sendable'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return $this->success([
            'messages' => collect($messages->items())->map(fn ($message) => [
                'id' => $message->id,
                'body' => $message->body,
                'type' => $message->type,
                'attachments' => $message->attachments ?? [],
                'is_mine' => $message->sendable_id === $user->id,
                'sender' => [
                    'id' => $message->sendable_id,
                    'name' => $message->sendable?->name,
                    'avatar' => $message->sendable?->avatar
                        ? asset('storage/'.$message->sendable->avatar)
                        : null,
                ],
                'created_at' => $message->created_at->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($messages),
        ], __('Messages retrieved successfully'));
    }

    /**
     * Send a message.
     */
    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'body' => ['required_without:attachment', 'nullable', 'string', 'max:5000'],
            'attachment' => ['required_without:body', 'nullable', 'file', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->first();

        if (! $conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        $messageData = [
            'conversation_id' => $conversation->id,
            'sendable_id' => $user->id,
            'sendable_type' => User::class,
            'type' => 'text',
        ];

        if ($request->filled('body')) {
            $messageData['body'] = $request->body;
        }

        // Handle attachment
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat-attachments/'.$user->id, 'public');

            $messageData['attachments'] = [[
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]];
            $messageData['type'] = $this->getMessageType($file->getMimeType());
            $messageData['body'] = $messageData['body'] ?? $file->getClientOriginalName();
        }

        $message = Message::create($messageData);
        $conversation->touch();

        return $this->created([
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'type' => $message->type,
                'attachments' => $message->attachments ?? [],
                'is_mine' => true,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], __('Message sent'));
    }

    /**
     * Mark conversation as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->first();

        if (! $conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        // Mark conversation as read using WireChat's API
        $unreadCount = $conversation->getUnreadCountFor($user);
        $conversation->markAsRead($user);

        return $this->success([
            'marked' => true,
            'count' => $unreadCount,
        ], __('Conversation marked as read'));
    }

    /**
     * Get total unread messages count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all conversations for the user and sum their unread counts
        $conversations = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('participantable_id', $user->id)
                ->where('participantable_type', User::class);
        })->get();

        $unreadCount = $conversations->sum(fn ($conv) => $conv->unreadMessagesCount($user));

        return $this->success([
            'unread_count' => $unreadCount,
        ], __('Unread count retrieved'));
    }

    /**
     * Notify typing status
     */
    public function typing(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_typing' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->first();

        if (! $conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        // Broadcast typing event
        broadcast(new \App\Events\UserTyping($id, $user, $request->is_typing));

        return $this->success([
            'broadcasted' => true,
        ], __('Typing status updated'));
    }

    /**
     * Edit a message
     */
    public function editMessage(Request $request, int $messageId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $message = Message::find($messageId);

        if (! $message) {
            return $this->notFound(__('Message not found.'));
        }

        // Only sender can edit their message
        if ($message->sendable_id !== $user->id || $message->sendable_type !== User::class) {
            return $this->error(
                __('You can only edit your own messages.'),
                403,
                'FORBIDDEN'
            );
        }

        // Update message
        $message->body = $request->body;
        $message->edited_at = now();
        $message->save();

        // Broadcast edit event
        broadcast(new \App\Events\MessageEdited($message));

        return $this->success([
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'edited_at' => $message->edited_at->toISOString(),
            ],
        ], __('Message updated successfully'));
    }

    /**
     * Delete a message
     */
    public function deleteMessage(Request $request, int $messageId): JsonResponse
    {
        $user = $request->user();
        $message = Message::find($messageId);

        if (! $message) {
            return $this->notFound(__('Message not found.'));
        }

        // Only sender can delete their message
        if ($message->sendable_id !== $user->id || $message->sendable_type !== User::class) {
            return $this->error(
                __('You can only delete your own messages.'),
                403,
                'FORBIDDEN'
            );
        }

        $conversationId = $message->conversation_id;

        // Delete message (will trigger MessageDeleted event via WireChat)
        $message->delete();

        return $this->success([
            'deleted' => true,
        ], __('Message deleted successfully'));
    }

    /**
     * Archive a conversation
     */
    public function archiveConversation(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->first();

        if (! $conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        // Update participant to mark as archived
        $conversation->participants()
            ->where('participantable_id', $user->id)
            ->where('participantable_type', User::class)
            ->update(['archived_at' => now()]);

        return $this->success([
            'archived' => true,
        ], __('Conversation archived successfully'));
    }

    /**
     * Unarchive a conversation
     */
    public function unarchiveConversation(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->first();

        if (! $conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        // Update participant to unarchive
        $conversation->participants()
            ->where('participantable_id', $user->id)
            ->where('participantable_type', User::class)
            ->update(['archived_at' => null]);

        return $this->success([
            'unarchived' => true,
        ], __('Conversation unarchived successfully'));
    }

    /**
     * Get archived conversations
     */
    public function archivedConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('participantable_id', $user->id)
                ->where('participantable_type', User::class)
                ->whereNotNull('archived_at');
        });

        $conversations = $query
            ->with(['participants.participantable', 'lastMessage.sendable'])
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success([
            'conversations' => collect($conversations->items())->map(function ($conversation) use ($user) {
                $otherParticipants = $conversation->participants
                    ->filter(fn ($p) => ! ($p->participantable_id === $user->id && $p->participantable_type === User::class))
                    ->map(fn ($p) => [
                        'id' => $p->participantable_id,
                        'name' => $p->participantable?->name,
                        'avatar' => $p->participantable?->avatar
                            ? asset('storage/'.$p->participantable->avatar)
                            : null,
                    ])
                    ->values();

                $unreadCount = $conversation->getUnreadCountFor($user);

                return [
                    'id' => $conversation->id,
                    'type' => $conversation->type,
                    'title' => $conversation->name ?? $otherParticipants->first()['name'] ?? 'محادثة',
                    'participants' => $otherParticipants->toArray(),
                    'last_message' => $conversation->lastMessage ? [
                        'id' => $conversation->lastMessage->id,
                        'body' => $conversation->lastMessage->body,
                        'type' => $conversation->lastMessage->type,
                        'is_mine' => $conversation->lastMessage->sendable_id === $user->id,
                        'created_at' => $conversation->lastMessage->created_at->toISOString(),
                    ] : null,
                    'unread_count' => $unreadCount,
                    'updated_at' => $conversation->updated_at->toISOString(),
                ];
            })->toArray(),
            'pagination' => PaginationHelper::fromPaginator($conversations),
        ], __('Archived conversations retrieved successfully'));
    }

    /**
     * Get conversation details/info
     */
    public function conversationDetails(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->with(['participants.participantable'])
            ->first();

        if (! $conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        // Get all participants with full details
        $participants = $conversation->participants->map(fn ($p) => [
            'id' => $p->participantable_id,
            'name' => $p->participantable?->name,
            'email' => $p->participantable?->email,
            'phone' => $p->participantable?->phone,
            'user_type' => $p->participantable?->user_type,
            'avatar' => $p->participantable?->avatar
                ? asset('storage/'.$p->participantable->avatar)
                : null,
            'is_you' => $p->participantable_id === $user->id,
        ])->toArray();

        // Check if this is a supervised chat by querying ChatGroup directly
        $chatGroup = \App\Models\ChatGroup::where('conversation_id', $id)->first();
        $isSupervisedChat = $chatGroup !== null;

        return $this->success([
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'name' => $conversation->name,
                'description' => $conversation->description ?? null,
                'created_at' => $conversation->created_at->toISOString(),
                'updated_at' => $conversation->updated_at->toISOString(),
                'participants_count' => count($participants),
                'is_supervised' => $isSupervisedChat,
            ],
            'participants' => $participants,
            'supervised_info' => $isSupervisedChat ? [
                'supervisor_id' => $chatGroup->supervisor_id,
                'teacher_id' => $chatGroup->teacher_id,
                'student_id' => $chatGroup->student_id,
            ] : null,
        ], __('Conversation details retrieved successfully'));
    }

    /**
     * Get conversation media files
     */
    public function conversationMedia(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('participantable_id', $user->id)
                    ->where('participantable_type', User::class);
            })
            ->first();

        if (! $conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        // Get messages with attachments
        $mediaType = $request->get('type'); // image, video, audio, file

        $query = Message::where('conversation_id', $id)
            ->whereHas('attachment') // WireChat uses singular attachment relationship
            ->with(['sendable', 'attachment']);

        // Filter by media type if specified
        if ($mediaType) {
            $query->where('type', $mediaType);
        }

        $messages = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 30));

        return $this->success([
            'media' => collect($messages->items())
                ->filter(fn ($message) => $message->attachment !== null)
                ->map(function ($message) {
                    $attachment = $message->attachment;

                    return [
                        'message_id' => $message->id,
                        'type' => $this->getMessageType($attachment->mime_type ?? 'application/octet-stream'),
                        'name' => $attachment->name ?? 'file',
                        'url' => $attachment->path ? asset('storage/'.$attachment->path) : null,
                        'size' => $attachment->size ?? null,
                        'mime' => $attachment->mime_type ?? null,
                        'sent_by' => [
                            'id' => $message->sendable_id,
                            'name' => $message->sendable?->name,
                        ],
                        'sent_at' => $message->created_at->toISOString(),
                    ];
                })
                ->values()
                ->toArray(),
            'pagination' => PaginationHelper::fromPaginator($messages),
        ], __('Media files retrieved successfully'));
    }

    /**
     * Get message type from mime type.
     */
    protected function getMessageType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'file';
    }
}
