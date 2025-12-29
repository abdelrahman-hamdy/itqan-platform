<?php

namespace App\Http\Resources\Api\V1\Session;

use App\Http\Resources\Api\V1\Student\StudentListResource;
use App\Http\Resources\Api\V1\Teacher\TeacherListResource;
use App\Models\AcademicSession;
use Illuminate\Http\Request;

/**
 * Academic Session Resource
 *
 * Extends base session with academic-specific data:
 * - Teacher information
 * - Student information
 * - Lesson content
 * - Homework assignments and submissions
 * - Recording information
 *
 * @mixin AcademicSession
 */
class AcademicSessionResource extends SessionResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            // Session type
            'session_type' => $this->session_type,

            // Teacher
            'teacher' => $this->whenLoaded('academicTeacher', function () {
                return new TeacherListResource($this->academicTeacher);
            }),

            // Student
            'student' => $this->whenLoaded('student', fn() => new StudentListResource($this->student)),

            // Individual lesson
            'individual_lesson' => $this->whenLoaded('academicIndividualLesson', [
                'id' => $this->academicIndividualLesson?->id,
                'subject' => [
                    'id' => $this->academicIndividualLesson?->academicSubject?->id,
                    'name' => $this->academicIndividualLesson?->academicSubject?->name,
                ],
            ]),

            // Lesson content
            'lesson_content' => $this->lesson_content,

            // Homework
            'homework' => [
                'assigned' => $this->homework_assigned,
                'description' => $this->when($this->homework_assigned, $this->homework_description),
                'file_url' => $this->when(
                    $this->homework_assigned && $this->homework_file,
                    fn() => $this->getFileUrl($this->homework_file)
                ),
            ],

            // Recording
            'recording' => [
                'enabled' => $this->recording_enabled,
                'url' => $this->when($this->recording_url, $this->recording_url),
            ],

            // Subscription
            'subscription' => $this->whenLoaded('academicSubscription', [
                'id' => $this->academicSubscription?->id,
                'subscription_code' => $this->academicSubscription?->subscription_code,
                'status' => [
                    'value' => $this->academicSubscription?->status?->value,
                    'label' => $this->academicSubscription?->status?->label(),
                ],
            ]),

            // Subscription counting
            'subscription_counted' => $this->subscription_counted,
        ]);
    }

    /**
     * Get file URL
     */
    protected function getFileUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return asset('storage/' . $path);
    }
}
