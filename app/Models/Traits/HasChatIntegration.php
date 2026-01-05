<?php

namespace App\Models\Traits;

use App\Models\User;

trait HasChatIntegration
{
    /**
     * WireChat Integration Methods - Required by Chatable trait
     */

    /**
     * Get display name for WireChat
     * Required by Chatable trait
     */
    public function displayName(): string
    {
        // Return display name based on user type and profile
        if ($this->user_type === 'student' && $this->studentProfile) {
            return trim($this->studentProfile->first_name.' '.$this->studentProfile->last_name) ?: $this->name;
        } elseif ($this->user_type === 'quran_teacher' && $this->quranTeacherProfile) {
            return trim($this->quranTeacherProfile->first_name.' '.$this->quranTeacherProfile->last_name) ?: $this->name;
        } elseif ($this->user_type === 'academic_teacher' && $this->academicTeacherProfile) {
            return trim($this->academicTeacherProfile->first_name.' '.$this->academicTeacherProfile->last_name) ?: $this->name;
        }

        return $this->name;
    }

    /**
     * Get display name attribute for WireChat
     * This is an alias for displayName() to satisfy Chatable trait requirements
     */
    public function getDisplayNameAttribute(): ?string
    {
        return $this->displayName();
    }

    /**
     * Get avatar/cover URL for WireChat
     * Required by Chatable trait
     */
    public function getCoverUrlAttribute(): ?string
    {
        // Check for user's avatar
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        // Check profile avatar based on user type
        $profile = $this->getProfile();
        if ($profile && method_exists($profile, 'getAvatar') && $profile->getAvatar()) {
            return asset('storage/'.$profile->getAvatar());
        }

        // Generate avatar URL using UI Avatars service
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=0ea5e9&color=fff';
    }

    /**
     * Get profile URL for WireChat
     * Required by Chatable trait
     */
    public function getProfileUrlAttribute(): ?string
    {
        // Generate profile URL based on user type
        return match ($this->user_type) {
            'student' => '/student/profile/' . $this->id,
            'quran_teacher', 'academic_teacher' => '/teacher/profile/' . $this->id,
            'parent' => '/parent/profile/' . $this->id,
            'supervisor' => '/supervisor/profile/' . $this->id,
            'admin' => '/admin/profile/' . $this->id,
            default => null,
        };
    }

    /**
     * Get unique identifier for LiveKit and data channel communications
     */
    public function getIdentifier(): string
    {
        return $this->id.'_'.str_replace(' ', '_', strtolower($this->first_name.'_'.$this->last_name));
    }

    /**
     * Get or create a private conversation with another user
     * Used for direct messaging between two users
     */
    public function getOrCreatePrivateConversation(User $otherUser)
    {
        // Use WireChat's conversation methods to find or create a private conversation
        // The Chatable trait provides access to WireChat functionality
        try {
            // Try to find an existing private conversation between these two users
            $conversation = \Namu\WireChat\Models\Conversation::where('type', 'private')
                ->whereHas('participants', function ($query) {
                    $query->where('participantable_id', $this->id)
                          ->where('participantable_type', User::class);
                })
                ->whereHas('participants', function ($query) use ($otherUser) {
                    $query->where('participantable_id', $otherUser->id)
                          ->where('participantable_type', User::class);
                })
                ->first();

            if ($conversation) {
                return $conversation;
            }

            // If no conversation exists, create a new one
            // Note: 'type' is not in fillable, so we need to set it directly
            $newConversation = new \Namu\WireChat\Models\Conversation();
            $newConversation->type = 'private';
            $newConversation->save();

            // Add both participants
            \Namu\WireChat\Models\Participant::create([
                'conversation_id' => $newConversation->id,
                'participantable_id' => $this->id,
                'participantable_type' => User::class,
                'role' => 'participant',
            ]);

            \Namu\WireChat\Models\Participant::create([
                'conversation_id' => $newConversation->id,
                'participantable_id' => $otherUser->id,
                'participantable_type' => User::class,
                'role' => 'participant',
            ]);

            return $newConversation;
        } catch (\Exception $e) {
            // Fallback: just return null if conversation creation fails
            \Log::error('Error creating private conversation', [
                'user_id' => $this->id,
                'other_user_id' => $otherUser->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get the count of unread messages for this user.
     * Used for inbox badge and notifications.
     */
    public function unreadMessagesCount(): int
    {
        $userId = $this->id;
        $userType = static::class;

        // Get all conversations where this user is a participant
        $participants = \Namu\WireChat\Models\Participant::query()
            ->where('participantable_id', $userId)
            ->where('participantable_type', $userType)
            ->whereNull('exited_at')
            ->get();

        $unreadCount = 0;

        foreach ($participants as $participant) {
            // Count messages in this conversation that:
            // 1. Were created after the user last read the conversation
            // 2. Were NOT sent by the user themselves
            $query = \Namu\WireChat\Models\Message::query()
                ->where('conversation_id', $participant->conversation_id)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($userId, $userType) {
                    $q->where('sendable_id', '!=', $userId)
                      ->orWhere('sendable_type', '!=', $userType);
                });

            // If conversation_read_at is set, only count messages after that time
            if ($participant->conversation_read_at) {
                $query->where('created_at', '>', $participant->conversation_read_at);
            }

            $unreadCount += $query->count();
        }

        return $unreadCount;
    }
}
