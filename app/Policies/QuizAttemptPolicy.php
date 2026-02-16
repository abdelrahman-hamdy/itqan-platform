<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    /**
     * Determine whether the user can take the quiz attempt.
     */
    public function take(User $user, QuizAttempt $attempt): bool
    {
        if (! $user->hasRole(UserType::STUDENT->value)) {
            return false;
        }

        $student = $user->studentProfile;
        if (! $student) {
            return false;
        }

        return $attempt->student_id === $student->id;
    }

    /**
     * Determine whether the user can submit the quiz attempt.
     */
    public function submit(User $user, QuizAttempt $attempt): bool
    {
        return $this->take($user, $attempt);
    }
}
