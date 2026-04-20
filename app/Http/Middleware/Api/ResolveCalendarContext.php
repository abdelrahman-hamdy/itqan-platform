<?php

namespace App\Http\Middleware\Api;

use App\Models\User;
use App\Services\AcademyContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the "effective teacher" for a calendar request.
 *
 * - Teacher caller: effective teacher is the caller; any teacher_id param is ignored
 *   (prevents lateral access between teachers).
 * - Supervisor caller: teacher_id is required and must be in the supervisor's
 *   assigned teachers list.
 * - Admin / Super-admin caller: teacher_id is required and must belong to the
 *   current academy.
 *
 * The resolved User and the resolved teacher type ('quran_teacher' | 'academic_teacher')
 * are attached to the request under the 'effective_teacher' and 'effective_teacher_type'
 * attributes for downstream controllers.
 */
class ResolveCalendarContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $caller = $request->user();

        if (! $caller) {
            return $this->deny('Unauthenticated.', 401, 'UNAUTHENTICATED');
        }

        $teacher = $this->resolveTeacher($request, $caller);

        if ($teacher instanceof Response) {
            return $teacher;
        }

        $teacherType = $this->resolveTeacherType($teacher);

        if ($teacherType === null) {
            return $this->deny('Resolved user is not a teacher.', 422, 'INVALID_TEACHER');
        }

        $request->attributes->set('effective_teacher', $teacher);
        $request->attributes->set('effective_teacher_type', $teacherType);
        $request->attributes->set('effective_teacher_is_self', $teacher->id === $caller->id);

        return $next($request);
    }

    private function resolveTeacher(Request $request, User $caller): User|Response
    {
        if ($caller->isTeacher()) {
            return $caller;
        }

        $teacherId = (int) $request->input('teacher_id');

        if (! $teacherId) {
            return $this->deny('teacher_id is required.', 422, 'TEACHER_ID_REQUIRED');
        }

        $teacher = User::find($teacherId);

        if (! $teacher || ! $teacher->isTeacher()) {
            return $this->deny('Teacher not found.', 404, 'TEACHER_NOT_FOUND');
        }

        if ($caller->isSupervisor()) {
            return $this->authorizeForSupervisor($caller, $teacher);
        }

        if ($caller->isAdmin() || $caller->isSuperAdmin()) {
            return $this->authorizeForAdmin($teacher);
        }

        return $this->deny('Unauthorized to act on behalf of a teacher.', 403, 'FORBIDDEN');
    }

    private function authorizeForSupervisor(User $supervisor, User $teacher): User|Response
    {
        $profile = $supervisor->supervisorProfile;

        $assigned = $profile
            ? (array) $profile->getAllAssignedTeacherIds()
            : [];

        if (! in_array($teacher->id, $assigned, true)) {
            return $this->deny('Teacher is not assigned to this supervisor.', 403, 'FORBIDDEN');
        }

        return $teacher;
    }

    private function authorizeForAdmin(User $teacher): User|Response
    {
        $academy = AcademyContextService::getCurrentAcademy();

        if (! $academy || $teacher->academy_id !== $academy->id) {
            return $this->deny('Teacher does not belong to the current academy.', 403, 'FORBIDDEN');
        }

        return $teacher;
    }

    private function resolveTeacherType(User $teacher): ?string
    {
        return match (true) {
            $teacher->isQuranTeacher() => 'quran_teacher',
            $teacher->isAcademicTeacher() => 'academic_teacher',
            default => null,
        };
    }

    private function deny(string $message, int $status, string $code): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $code,
        ], $status);
    }
}
