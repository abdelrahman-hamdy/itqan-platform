<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface UnifiedHomeworkServiceInterface
{
    public function getStudentHomework(
        int $studentId,
        int $academyId,
        ?string $status = null,
        ?string $type = null
    ): Collection;

    public function getStudentHomeworkStatistics(int $studentId, int $academyId): array;
}
