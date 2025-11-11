<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChMessage;
use App\Models\User;
use App\Services\ChatPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

/**
 * Mobile-Ready Chat API Controller
 *
 * Provides RESTful API endpoints for mobile applications
 * All responses follow a consistent JSON structure
 */
class ChatApiController extends Controller
{
    protected ChatPermissionService $permissionService;

    public function __construct(ChatPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get user's contacts list with pagination and search
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContacts(Request $request)
    {
        try {
            $user = auth()->user();
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search', '');
            $page = $request->input('page', 1);

            // Get potential contacts based on user role
            $contactsQuery = $this->getContactsQuery($user);

            // Apply search filter
            if ($search) {
                $contactsQuery->where('name', 'like', "%{$search}%");
            }

            // Paginate
            $contacts = $contactsQuery->paginate($perPage);

            // Transform contacts with last message and unread count
            $contactsData = $contacts->getCollection()->map(function ($contact) use ($user) {
                return $this->transformContact($contact, $user);
            });

            return response()->json([
                'success' => true,
                'data' => $contactsData,
                'meta' => [
                    'current_page' => $contacts->currentPage(),
                    'per_page' => $contacts->perPage(),
                    'total' => $contacts->total(),
                    'last_page' => $contacts->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch contacts', $e->getMessage());
        }
    }

    /**
     * Get messages for a conversation with pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_id' => 'required|integer|exists:users,id',
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();
            $contactId = $request->input('contact_id');
            $perPage = $request->input('per_page', 30);

            // Check permission
            $contact = User::find($contactId);
            if (!$this->permissionService->canMessage($user, $contact)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to message this user',
                ], 403);
            }

            // Fetch messages
            $messages = ChMessage::where(function ($query) use ($user, $contactId) {
                $query->where('from_id', $user->id)->where('to_id', $contactId);
            })->orWhere(function ($query) use ($user, $contactId) {
                $query->where('from_id', $contactId)->where('to_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

            // Transform messages
            $messagesData = $messages->getCollection()->map(function ($message) use ($user) {
                return $this->transformMessage($message, $user);
            });

            return response()->json([
                'success' => true,
                'data' => $messagesData->reverse()->values(),
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'last_page' => $messages->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch messages', $e->getMessage());
        }
    }

    /**
     * Send a new message
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'to_id' => 'required|integer|exists:users,id',
                'message' => 'required|string|max:5000',
                'attachment' => 'nullable|file|max:' . (config('chat.attachments.max_upload_size') * 1024),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();
            $toId = $request->input('to_id');
            $messageText = $request->input('message');

            // Check permission
            $recipient = User::find($toId);
            if (!$this->permissionService->canMessage($user, $recipient)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to message this user',
                ], 403);
            }

            // Handle attachment if present
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->handleAttachment($request->file('attachment'));
            }

            // Create message
            $message = ChMessage::create([
                'from_id' => $user->id,
                'to_id' => $toId,
                'body' => $messageText,
                'attachment' => $attachmentPath,
                'seen' => false,
            ]);

            // Broadcast the message via Reverb
            broadcast(new \App\Events\MessageSent($message))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $this->transformMessage($message, $user),
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send message', $e->getMessage());
        }
    }

    /**
     * Mark messages as read
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_id' => 'required|integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();
            $contactId = $request->input('contact_id');

            // Mark all messages from contact as seen
            ChMessage::where('from_id', $contactId)
                ->where('to_id', $user->id)
                ->where('seen', false)
                ->update(['seen' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark messages as read', $e->getMessage());
        }
    }

    /**
     * Get unread messages count
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        try {
            $user = auth()->user();

            $unreadCount = ChMessage::where('to_id', $user->id)
                ->where('seen', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $unreadCount,
                ],
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get unread count', $e->getMessage());
        }
    }

    /**
     * Get user info by ID
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserInfo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $currentUser = auth()->user();
            $targetUser = User::find($request->input('user_id'));

            // Check permission
            if (!$this->permissionService->canMessage($currentUser, $targetUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to view this user',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $this->transformContact($targetUser, $currentUser),
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get user info', $e->getMessage());
        }
    }

    /**
     * Search for users to start a conversation
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUsers(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2',
                'per_page' => 'integer|min:1|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();
            $query = $request->input('query');
            $perPage = $request->input('per_page', 10);

            // Get potential contacts and search
            $contactsQuery = $this->getContactsQuery($user)
                ->where('name', 'like', "%{$query}%");

            $results = $contactsQuery->paginate($perPage);

            $resultsData = $results->getCollection()->map(function ($contact) use ($user) {
                return $this->transformContact($contact, $user, false); // Don't include last message for search
            });

            return response()->json([
                'success' => true,
                'data' => $resultsData,
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Search failed', $e->getMessage());
        }
    }

    /**
     * Delete a message
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteMessage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message_id' => 'required|integer|exists:ch_messages,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();
            $messageId = $request->input('message_id');

            $message = ChMessage::find($messageId);

            // Only sender can delete their message
            if ($message->from_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete your own messages',
                ], 403);
            }

            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete message', $e->getMessage());
        }
    }

    /**
     * Get contacts query based on user role
     */
    protected function getContactsQuery(User $user)
    {
        // Base query - same academy
        $query = User::where('academy_id', $user->academy_id)
            ->where('id', '!=', $user->id);

        // Filter based on user type using permission service
        // For now, return all potential contacts in same academy
        // Permission checking happens when actually messaging

        return $query;
    }

    /**
     * Transform contact for API response
     */
    protected function transformContact(User $contact, User $currentUser, bool $includeLastMessage = true)
    {
        $data = [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'user_type' => $contact->user_type,
            'avatar' => $contact->profile_picture_url ?? null,
            'is_online' => Cache::has('user-online-' . $contact->id),
            'last_seen' => $contact->last_activity_at,
        ];

        if ($includeLastMessage) {
            $lastMessage = ChMessage::where(function ($query) use ($currentUser, $contact) {
                $query->where('from_id', $currentUser->id)->where('to_id', $contact->id);
            })->orWhere(function ($query) use ($currentUser, $contact) {
                $query->where('from_id', $contact->id)->where('to_id', $currentUser->id);
            })
            ->orderBy('created_at', 'desc')
            ->first();

            $data['last_message'] = $lastMessage ? [
                'id' => $lastMessage->id,
                'body' => $lastMessage->body,
                'is_own' => $lastMessage->from_id === $currentUser->id,
                'created_at' => $lastMessage->created_at->toIso8601String(),
            ] : null;

            // Unread count
            $data['unread_count'] = ChMessage::where('from_id', $contact->id)
                ->where('to_id', $currentUser->id)
                ->where('seen', false)
                ->count();
        }

        return $data;
    }

    /**
     * Transform message for API response
     */
    protected function transformMessage(ChMessage $message, User $currentUser)
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'from_id' => $message->from_id,
            'to_id' => $message->to_id,
            'is_own' => $message->from_id === $currentUser->id,
            'seen' => $message->seen,
            'attachment' => $message->attachment ? url('storage/' . $message->attachment) : null,
            'created_at' => $message->created_at->toIso8601String(),
            'updated_at' => $message->updated_at->toIso8601String(),
        ];
    }

    /**
     * Handle file attachment upload
     */
    protected function handleAttachment($file)
    {
        $allowedMimes = array_merge(
            config('chat.attachments.allowed_images'),
            config('chat.attachments.allowed_files')
        );

        if (!in_array($file->getClientOriginalExtension(), $allowedMimes)) {
            throw new \Exception('File type not allowed');
        }

        return $file->store(config('chat.attachments.folder'), config('chat.storage_disk_name'));
    }

    /**
     * Standard error response
     */
    protected function errorResponse(string $message, string $details = null, int $code = 500)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (config('app.debug') && $details) {
            $response['error'] = $details;
        }

        return response()->json($response, $code);
    }
}
