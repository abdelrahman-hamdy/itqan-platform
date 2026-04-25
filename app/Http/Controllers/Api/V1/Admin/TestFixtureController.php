<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin-only test-fixture endpoints used by the mobile integration_test
 * suite to seed states that are not reachable via the regular workflow
 * (e.g. a `suspended` session — the suspension transition is normally a
 * side-effect of subscription cancellation/grace).
 *
 * Guarded by `EnsureAdminOrSupervisor` middleware (parent route group).
 * Do NOT expand this controller with non-fixture write actions.
 */
class TestFixtureController extends Controller
{
    use ApiResponses;

    /**
     * Create a session with status = SUSPENDED for the given student.
     *
     * Body:
     * - student_id (required, exists:users,id)
     * - subscription_type (required, in:quran,academic)
     */
    public function createSuspendedSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'subscription_type' => ['required', Rule::in(['quran', 'academic'])],
        ]);

        $student = User::findOrFail($data['student_id']);

        if ($data['subscription_type'] === 'quran') {
            $session = QuranSession::factory()
                ->suspended()
                ->forStudent($student)
                ->create();
            $type = 'quran';
        } else {
            $session = AcademicSession::factory()
                ->suspended()
                ->create([
                    'student_id' => $student->id,
                ]);
            $type = 'academic';
        }

        return $this->successResponse([
            'id' => $session->id,
            'type' => $type,
            'status' => SessionStatus::SUSPENDED->value,
            'scheduled_at' => $session->scheduled_at?->toIso8601String(),
        ], 'Suspended session created for E2E testing.', 201);
    }
}
