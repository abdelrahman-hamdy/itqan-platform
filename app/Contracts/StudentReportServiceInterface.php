<?php

namespace App\Contracts;

use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Support\Collection;

interface StudentReportServiceInterface
{
    public function generateStudentReport(QuranSession $session, User $student): StudentSessionReport;

    public function updateTeacherEvaluation(
        StudentSessionReport $report,
        int $newMemorizationDegree,
        int $reservationDegree,
        ?string $notes = null
    ): StudentSessionReport;

    public function generateSessionReports(QuranSession $session): Collection;

    public function getSessionStats(QuranSession $session): array;

    public function getStudentStats(User $student, Collection $sessionIds): array;
}
