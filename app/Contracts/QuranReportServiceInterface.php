<?php

namespace App\Contracts;

use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;

interface QuranReportServiceInterface
{
    public function getIndividualCircleReport(QuranIndividualCircle $circle, ?array $dateRange = null): array;

    public function getGroupCircleReport(QuranCircle $circle): array;

    public function getStudentReportInGroupCircle(QuranCircle $circle, User $student, ?array $dateRange = null): array;
}
