<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use App\Enums\SessionStatus;

class ChatController extends Controller
{
    use ApiResponses;

    /**
     * Get all conversations for the user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('participantable_id', $user->id)
                ->where('participantable_type', User::class);
        })
            ->with(['participants.participantable', 'lastMessage'])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->whereDoesntHave('reads', function ($q) use ($user) {
                    $q->where('readable_id', $user->id)
                        ->where('readable_type', User::class);
                });
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success([
            'conversations' => collect($conversations->items())->map(function ($conversation) use ($user) {
                $otherParticipants = $conversation->participants
                    ->filter(fn($p) => !($p->participantable_id === $user->id && $p->participantable_type === User::class))
                    ->map(fn($p) => [
                        'id' => $p->participantable_id,
                        'name' => $p->participantable?->name,
                        'avatar' => $p->participantable?->avatar
                            ? asset('storage/' . $p->participantable->avatar)
                            : null,
                    ])
                    ->values();

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
                    'unread_count' => $conversation->unread_count ?? 0,
                    'updated_at' => $conversation->updated_at->toISOString(),
                ];
            })->toArray(),
            'pagination' => PaginationHelper::fromPaginator($conversations),
        ], __('Conversations retrieved successfully'));
    }

    /**
     * Create a new conversation.
     *
     * @param Request $request
     * @return JsonResponse
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
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
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

        if (!$conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        $otherParticipants = $conversation->participants
            ->filter(fn($p) => !($p->participantable_id === $user->id && $p->participantable_type === User::class))
            ->map(fn($p) => [
                'id' => $p->participantable_id,
                'name' => $p->participantable?->name,
                'avatar' => $p->participantable?->avatar
                    ? asset('storage/' . $p->participantable->avatar)
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
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
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

        if (!$conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        $messages = Message::where('conversation_id', $id)
            ->with(['sendable'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return $this->success([
            'messages' => collect($messages->items())->map(fn($message) => [
                'id' => $message->id,
                'body' => $message->body,
                'type' => $message->type,
                'attachments' => $message->attachments ?? [],
                'is_mine' => $message->sendable_id === $user->id,
                'sender' => [
                    'id' => $message->sendable_id,
                    'name' => $message->sendable?->name,
                    'avatar' => $message->sendable?->avatar
                        ? asset('storage/' . $message->sendable->avatar)
                        : null,
                ],
                'created_at' => $message->created_at->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($messages),
        ], __('Messages retrieved successfully'));
    }

    /**
     * Send a message.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
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

        if (!$conversation) {
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
            $path = $file->store('chat-attachments/' . $user->id, 'public');

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
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
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

        if (!$conversation) {
            return $this->notFound(__('Conversation not found.'));
        }

        // Mark conversation as read using WireChat's API
        $unreadCount = $conversation->unreadMessagesCount($user);
        $conversation->markAsRead($user);

        return $this->success([
            'marked' => true,
            'count' => $unreadCount,
        ], __('Conversation marked as read'));
    }

    /**
     * Get total unread messages count.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all conversations for the user and sum their unread counts
        $conversations = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('participantable_id', $user->id)
                ->where('participantable_type', User::class);
        })->get();

        $unreadCount = $conversations->sum(fn($conv) => $conv->unreadMessagesCount($user));

        return $this->success([
            'unread_count' => $unreadCount,
        ], __('Unread count retrieved'));
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
