<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Events\MessageReactionAdded;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Wirechat\Wirechat\Models\Message;

/**
 * Message Reaction Controller
 *
 * Handles adding, removing, and listing emoji reactions on messages.
 */
class MessageReactionController extends Controller
{
    use ApiResponses;

    /**
     * Add a reaction to a message
     *
     * @param Request $request
     * @param int $messageId
     * @return JsonResponse
     */
    public function store(Request $request, int $messageId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'emoji' => ['required', 'string', 'max:10'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $message = Message::find($messageId);

        if (! $message) {
            return $this->notFound(__('Message not found.'));
        }

        // Check if user is participant of the conversation
        $conversation = $message->conversation;
        $isParticipant = $conversation->participants()
            ->where('participantable_id', $user->id)
            ->where('participantable_type', User::class)
            ->exists();

        if (! $isParticipant) {
            return $this->error(
                __('You cannot react to messages in conversations you are not part of.'),
                403,
                'FORBIDDEN'
            );
        }

        // Create or retrieve reaction (unique constraint prevents duplicates)
        $reaction = MessageReaction::firstOrCreate([
            'message_id' => $messageId,
            'reacted_by_id' => $user->id,
            'reacted_by_type' => User::class,
            'emoji' => $request->emoji,
        ]);

        // Broadcast event
        broadcast(new MessageReactionAdded($message, $request->emoji, $user));

        // Get reaction count for this emoji
        $count = MessageReaction::where('message_id', $messageId)
            ->where('emoji', $request->emoji)
            ->count();

        return $this->success([
            'reaction' => [
                'emoji' => $request->emoji,
                'count' => $count,
                'reacted' => true,
            ],
        ], __('Reaction added successfully'));
    }

    /**
     * Remove a reaction from a message
     *
     * @param Request $request
     * @param int $messageId
     * @param string $emoji
     * @return JsonResponse
     */
    public function destroy(Request $request, int $messageId, string $emoji): JsonResponse
    {
        $user = $request->user();
        $message = Message::find($messageId);

        if (! $message) {
            return $this->notFound(__('Message not found.'));
        }

        // Delete the reaction
        $deleted = MessageReaction::where('message_id', $messageId)
            ->where('reacted_by_id', $user->id)
            ->where('reacted_by_type', User::class)
            ->where('emoji', $emoji)
            ->delete();

        if ($deleted === 0) {
            return $this->error(__('Reaction not found.'), 404, 'NOT_FOUND');
        }

        // Broadcast event (create event class if needed)
        // broadcast(new MessageReactionRemoved($message, $emoji, $user));

        return $this->success([
            'removed' => true,
            'emoji' => $emoji,
        ], __('Reaction removed successfully'));
    }

    /**
     * Get all reactions for a message
     *
     * @param Request $request
     * @param int $messageId
     * @return JsonResponse
     */
    public function index(Request $request, int $messageId): JsonResponse
    {
        $message = Message::find($messageId);

        if (! $message) {
            return $this->notFound(__('Message not found.'));
        }

        // Get reactions grouped by emoji
        $reactions = MessageReaction::where('message_id', $messageId)
            ->with('reactedBy')
            ->get()
            ->groupBy('emoji')
            ->map(function ($group, $emoji) {
                return [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'users' => $group->map(fn ($reaction) => [
                        'id' => $reaction->reacted_by_id,
                        'name' => $reaction->reactedBy?->name,
                    ])->toArray(),
                ];
            })
            ->values()
            ->toArray();

        return $this->success([
            'reactions' => $reactions,
        ], __('Reactions retrieved successfully'));
    }
}
