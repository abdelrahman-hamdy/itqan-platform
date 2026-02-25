<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Contracts\SupervisedChatGroupServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Wirechat\Wirechat\Models\Conversation;

/**
 * Supervised Chat Controller
 *
 * Handles creation of supervised chat groups:
 * - Triple chat: Teacher + Student + Supervisor
 * - Double chat: Supervisor + Student
 */
class SupervisedChatController extends Controller
{
    use ApiResponses;

    protected SupervisedChatGroupServiceInterface $chatGroupService;

    public function __construct(SupervisedChatGroupServiceInterface $chatGroupService)
    {
        $this->chatGroupService = $chatGroupService;
    }

    /**
     * Create supervised triple chat (Teacher-Student-Supervisor)
     *
     * Creates a supervised chat group with:
     * - Teacher (ADMIN role)
     * - Student (MEMBER role)
     * - Supervisor (MODERATOR role)
     *
     * Requires:
     * - Teacher must have assigned supervisor
     * - Entity must exist and be valid
     * - Requestor must be teacher, student, or supervisor
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createSupervisedChat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => ['required', 'exists:users,id'],
            'student_id' => ['required', 'exists:users,id'],
            'entity_type' => ['required', 'in:quran_individual,academic_lesson,quran_circle,interactive_course'],
            'entity_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $currentUser = $request->user();
        $teacher = User::find($request->teacher_id);
        $student = User::find($request->student_id);

        if (! $teacher || ! $student) {
            return $this->notFound(__('Teacher or student not found.'));
        }

        // Get supervisor
        $supervisor = $teacher->getPrimarySupervisor();

        // Teacher must have supervisor for supervised chats
        if (! $supervisor) {
            return $this->error(
                __('Teacher has no assigned supervisor. Supervised chat cannot be created.'),
                400,
                'NO_SUPERVISOR'
            );
        }

        // Authorization: User must be teacher, student, or supervisor
        if (! in_array($currentUser->id, [$teacher->id, $student->id, $supervisor->id])) {
            return $this->error(
                __('Access denied. Only the teacher, student, or supervisor can create this chat.'),
                403,
                'FORBIDDEN'
            );
        }

        // Validate entity ownership: ensure the entity actually links this teacher to this student
        if (! $this->validateEntityOwnership($request->entity_type, (int) $request->entity_id, $teacher, $student)) {
            return $this->error(
                __('The specified entity does not belong to this teacher and student.'),
                403,
                'ENTITY_OWNERSHIP_MISMATCH'
            );
        }

        // Get or create supervised chat group
        $group = $this->chatGroupService->getOrCreateSupervisedChat(
            $teacher,
            $student,
            $request->entity_type,
            $request->entity_id
        );

        if (! $group || ! $group->conversation) {
            return $this->error(
                __('Failed to create supervised chat. Please try again.'),
                500,
                'CREATION_FAILED'
            );
        }

        // Get participant details
        $conversation = $group->conversation;
        $participants = $conversation->participants()->with('participantable')->get();

        return $this->success([
            'conversation_id' => $group->conversation_id,
            'group_id' => $group->id,
            'type' => 'supervised',
            'participants' => $participants->map(fn ($participant) => [
                'id' => $participant->participantable_id,
                'name' => $participant->participantable?->name,
                'role' => $participant->role->value,
            ])->toArray(),
            'entity' => [
                'type' => $request->entity_type,
                'id' => $request->entity_id,
            ],
        ], __('Supervised chat created successfully'));
    }

    /**
     * Create double chat (Supervisor-Student)
     *
     * Creates a private conversation between supervisor and student.
     * Only supervisors can initiate this type of chat.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createSupervisorStudentChat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $supervisor = $request->user();

        if (! $supervisor->isSupervisor()) {
            return $this->error(
                __('Only supervisors can create supervisor-student chats.'),
                403,
                'FORBIDDEN'
            );
        }

        $student = User::find($request->student_id);

        if (! $student) {
            return $this->notFound(__('Student not found.'));
        }

        // Check if conversation already exists
        $conversation = Conversation::where('type', 'private')
            ->whereHas('participants', fn ($q) => $q->where('participantable_id', $supervisor->id)
                ->where('participantable_type', User::class))
            ->whereHas('participants', fn ($q) => $q->where('participantable_id', $student->id)
                ->where('participantable_type', User::class))
            ->first();

        if ($conversation) {
            return $this->success([
                'conversation_id' => $conversation->id,
                'is_new' => false,
                'participants' => [
                    ['id' => $supervisor->id, 'name' => $supervisor->name],
                    ['id' => $student->id, 'name' => $student->name],
                ],
            ], __('Conversation already exists'));
        }

        // Create new private conversation
        $conversation = Conversation::create(['type' => 'private']);

        $conversation->participants()->create([
            'participantable_id' => $supervisor->id,
            'participantable_type' => User::class,
        ]);

        $conversation->participants()->create([
            'participantable_id' => $student->id,
            'participantable_type' => User::class,
        ]);

        return $this->created([
            'conversation_id' => $conversation->id,
            'is_new' => true,
            'participants' => [
                ['id' => $supervisor->id, 'name' => $supervisor->name],
                ['id' => $student->id, 'name' => $student->name],
            ],
        ], __('Supervisor-student conversation created successfully'));
    }

    /**
     * Validate that the entity actually belongs to the given teacher and student.
     */
    private function validateEntityOwnership(string $entityType, int $entityId, User $teacher, User $student): bool
    {
        return match ($entityType) {
            'quran_individual' => QuranIndividualCircle::where('id', $entityId)
                ->where('quran_teacher_id', $teacher->id)
                ->where('student_id', $student->id)
                ->exists(),

            'academic_lesson' => AcademicIndividualLesson::where('id', $entityId)
                ->where('academic_teacher_id', $teacher->academicTeacherProfile?->id ?? 0)
                ->where('student_id', $student->id)
                ->exists(),

            'quran_circle' => QuranCircle::where('id', $entityId)
                ->where('quran_teacher_id', $teacher->id)
                ->exists(),

            'interactive_course' => InteractiveCourse::where('id', $entityId)
                ->where('assigned_teacher_id', $teacher->academicTeacherProfile?->id ?? 0)
                ->exists(),

            default => false,
        };
    }
}
