<?php

namespace App\Filament\Resources\CourseReviewResource\Pages;

use App\Filament\Resources\CourseReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseReviews extends ListRecords
{
    protected static string $resource = CourseReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
