<?php

namespace App\Filament\Supervisor\Resources\ManagedTeacherReviewsResource\Pages;

use App\Filament\Supervisor\Resources\ManagedTeacherReviewsResource;
use Filament\Resources\Pages\ListRecords;

class ListManagedTeacherReviews extends ListRecords
{
    protected static string $resource = ManagedTeacherReviewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors cannot create reviews
        ];
    }
}
