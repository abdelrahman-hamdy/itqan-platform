<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\ParentStudentRelationship;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Enums\SessionStatus;

class ChildrenController extends Controller
{
    use ApiResponses;

    /**
     * Get all linked children.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with(['student.user', 'student.gradeLevel'])
            ->get();

        return $this->success([
            'children' => $children->map(fn($rel) => [
                'id' => $rel->student->id,
                'user_id' => $rel->student->user?->id,
                'name' => $rel->student->full_name,
                'student_code' => $rel->student->student_code,
                'avatar' => $rel->student->avatar ? asset('storage/' . $rel->student->avatar) : null,
                'grade_level' => $rel->student->gradeLevel?->name,
                'relationship' => $rel->relationship_type,
                'email' => $rel->student->email,
                'phone' => $rel->student->phone,
                'birth_date' => $rel->student->birth_date?->toDateString(),
                'linked_at' => $rel->created_at->toISOString(),
            ])->toArray(),
            'total' => $children->count(),
        ], __('Children retrieved successfully'));
    }

    /**
     * Link a child using student code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function link(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_code' => ['required', 'string'],
            'relationship_type' => ['required', 'in:father,mother,guardian,other'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? app('current_academy');
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Find student by code
        $student = StudentProfile::where('student_code', $request->student_code)
            ->whereHas('gradeLevel', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })
            ->first();

        if (!$student) {
            return $this->error(
                __('Student code not found in this academy.'),
                404,
                'STUDENT_CODE_NOT_FOUND'
            );
        }

        // Check if already linked
        $existingLink = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($existingLink) {
            return $this->error(
                __('This child is already linked to your account.'),
                400,
                'CHILD_ALREADY_LINKED'
            );
        }

        // Create link
        ParentStudentRelationship::create([
            'parent_id' => $parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => $request->relationship_type,
        ]);

        return $this->created([
            'child' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'student_code' => $student->student_code,
                'grade_level' => $student->gradeLevel?->name,
            ],
        ], __('Child linked successfully'));
    }

    /**
     * Get a specific child.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $relationship = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $id)
            ->with(['student.user', 'student.gradeLevel'])
            ->first();

        if (!$relationship) {
            return $this->notFound(__('Child not found.'));
        }

        $student = $relationship->student;

        return $this->success([
            'child' => [
                'id' => $student->id,
                'user_id' => $student->user?->id,
                'name' => $student->full_name,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'student_code' => $student->student_code,
                'avatar' => $student->avatar ? asset('storage/' . $student->avatar) : null,
                'email' => $student->email,
                'phone' => $student->phone,
                'birth_date' => $student->birth_date?->toDateString(),
                'age' => $student->birth_date ? $student->birth_date->age : null,
                'gender' => $student->gender,
                'nationality' => $student->nationality,
                'grade_level' => $student->gradeLevel ? [
                    'id' => $student->gradeLevel->id,
                    'name' => $student->gradeLevel->name,
                ] : null,
                'enrollment_date' => $student->enrollment_date?->toDateString(),
                'relationship' => $relationship->relationship_type,
                'linked_at' => $relationship->created_at->toISOString(),
            ],
        ], __('Child retrieved successfully'));
    }

    /**
     * Set a child as active (for parent dashboard focus).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function setActive(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $relationship = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $id)
            ->first();

        if (!$relationship) {
            return $this->notFound(__('Child not found.'));
        }

        // Update parent profile with active child (if such field exists)
        // For now, just return success
        return $this->success([
            'active_child_id' => $id,
        ], __('Active child set successfully'));
    }

    /**
     * Unlink a child.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function unlink(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $relationship = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $id)
            ->first();

        if (!$relationship) {
            return $this->notFound(__('Child not found.'));
        }

        $relationship->delete();

        return $this->success([
            'unlinked' => true,
        ], __('Child unlinked successfully'));
    }
}
