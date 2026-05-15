<?php

namespace App\Http\Resources\Api\V1\Homework;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail payload for a Quran homework item (always view-only for students).
 *
 * Quran homework is graded orally and recorded on StudentSessionReport. The
 * `evaluation` block is populated only when the student's session report has
 * an `evaluated_at` timestamp.
 *
 * @property-read QuranSession $resource
 */
class QuranHomeworkDetailResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        /** @var QuranSession $session */
        $session = $this->resource;
        $hw = $session->sessionHomework;
        $report = $this->reportFor($session, $request->user()->id);
        $isEvaluated = $report && $report->evaluated_at !== null;
        $status = $isEvaluated ? HomeworkSubmissionStatus::GRADED : HomeworkSubmissionStatus::PENDING;

        return [
            'id' => $hw?->id ?? $session->id,
            'type' => 'quran',
            'session_id' => $session->id,
            'title' => $session->title ?? __('homework.quran_homework'),
            'subject' => __('homework.quran_subject'),
            'description' => $hw?->additional_instructions,
            'due_date' => $hw?->due_date?->toISOString(),
            'session_date' => $session->scheduled_at?->toISOString(),
            'submission_status' => $status->value,
            'can_submit' => false,
            'is_overdue' => $hw && $hw->due_date && $hw->due_date->isPast() && ! $isEvaluated,
            'difficulty_level' => $hw?->difficulty_level,
            'teacher' => $this->teacherPayload($session->quranTeacher),
            'new_memorization' => $this->newMemorization($hw),
            'review' => $this->review($hw),
            'comprehensive_review' => $this->comprehensiveReview($hw),
            'evaluation' => $isEvaluated ? $this->evaluationPayload($report) : null,
        ];
    }

    private function teacherPayload($teacher): ?array
    {
        if (! $teacher) {
            return null;
        }

        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => $teacher->avatar ? asset('storage/'.$teacher->avatar) : null,
        ];
    }

    private function newMemorization($hw): ?array
    {
        if (! $hw || ! $hw->has_new_memorization) {
            return null;
        }

        return [
            'surah' => $hw->new_memorization_surah,
            'from_verse' => $hw->new_memorization_from_verse,
            'to_verse' => $hw->new_memorization_to_verse,
            'pages' => $hw->new_memorization_pages !== null ? (float) $hw->new_memorization_pages : null,
        ];
    }

    private function review($hw): ?array
    {
        if (! $hw || ! $hw->has_review) {
            return null;
        }

        return [
            'surah' => $hw->review_surah,
            'from_verse' => $hw->review_from_verse,
            'to_verse' => $hw->review_to_verse,
            'pages' => $hw->review_pages !== null ? (float) $hw->review_pages : null,
        ];
    }

    private function comprehensiveReview($hw): ?array
    {
        if (! $hw || ! $hw->has_comprehensive_review) {
            return null;
        }

        return [
            'surahs' => $hw->comprehensive_review_surahs ?? [],
        ];
    }

    private function evaluationPayload(StudentSessionReport $report): array
    {
        $score = $report->overall_performance;
        $tier = $this->tierFromScore($score);

        return [
            'tier' => $tier,
            'percentage' => $this->percentageForTier($tier),
            'teacher_notes' => $report->notes,
            'evaluated_at' => $report->evaluated_at?->toISOString(),
        ];
    }

    private function tierFromScore(?float $score): string
    {
        if ($score === null) {
            return 'weak';
        }

        return match (true) {
            $score >= 9 => 'excellent',
            $score >= 8 => 'very_good',
            $score >= 7 => 'good',
            $score >= 6 => 'acceptable',
            default => 'weak',
        };
    }

    private function percentageForTier(string $tier): int
    {
        return match ($tier) {
            'excellent' => 95,
            'very_good' => 85,
            'good' => 75,
            'acceptable' => 65,
            default => 50,
        };
    }

    private function reportFor(QuranSession $session, int $userId): ?StudentSessionReport
    {
        if ($session->relationLoaded('studentReports')) {
            return $session->studentReports->firstWhere('student_id', $userId);
        }

        return $session->studentReports()->where('student_id', $userId)->first();
    }
}
