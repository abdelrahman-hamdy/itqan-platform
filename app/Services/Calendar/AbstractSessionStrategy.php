<?php

namespace App\Services\Calendar;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Abstract base class for session strategies
 *
 * Provides common functionality for handling target user context,
 * allowing strategies to work for either the authenticated user
 * or a specific user (e.g., when supervisor views teacher's calendar).
 */
abstract class AbstractSessionStrategy implements SessionStrategyInterface
{
    /**
     * The target user for this strategy instance
     * When null, defaults to Auth::user()
     */
    protected ?User $targetUser = null;

    /**
     * Set the target user for this strategy
     *
     * @param User|int|null $user User model, user ID, or null for Auth::user()
     * @return static For method chaining
     */
    public function forUser(User|int|null $user): static
    {
        if ($user === null) {
            $this->targetUser = null;
        } elseif (is_int($user)) {
            $this->targetUser = User::find($user);
        } else {
            $this->targetUser = $user;
        }

        return $this;
    }

    /**
     * Get the target user for this strategy
     *
     * @return User|null The target user, or Auth::user() if not set
     */
    public function getTargetUser(): ?User
    {
        return $this->targetUser ?? Auth::user();
    }

    /**
     * Get the target user's ID
     *
     * @return int|null The target user's ID
     */
    protected function getTargetUserId(): ?int
    {
        return $this->getTargetUser()?->id;
    }

    /**
     * Reset the target user to default (Auth::user())
     *
     * @return static For method chaining
     */
    public function resetUser(): static
    {
        $this->targetUser = null;
        return $this;
    }

    /**
     * Check if a custom target user is set
     *
     * @return bool True if a custom target user is set
     */
    public function hasCustomUser(): bool
    {
        return $this->targetUser !== null;
    }
}
