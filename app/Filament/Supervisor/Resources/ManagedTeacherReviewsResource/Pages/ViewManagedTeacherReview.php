<?php

namespace App\Filament\Supervisor\Resources\ManagedTeacherReviewsResource\Pages;

use App\Filament\Supervisor\Resources\ManagedTeacherReviewsResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewManagedTeacherReview extends ViewRecord
{
    protected static string $resource = ManagedTeacherReviewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // View only - no edit action
        ];
    }
}
