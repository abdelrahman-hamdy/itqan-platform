<?php

namespace App\Filament\Resources\CourseReviewResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\CourseReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourseReview extends ViewRecord
{
    protected static string $resource = CourseReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
