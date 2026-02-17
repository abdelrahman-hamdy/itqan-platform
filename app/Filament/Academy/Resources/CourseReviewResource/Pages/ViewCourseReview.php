<?php

namespace App\Filament\Academy\Resources\CourseReviewResource\Pages;

use App\Filament\Academy\Resources\CourseReviewResource;
use Filament\Actions\EditAction;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewCourseReview extends ViewRecord
{
    protected static string $resource = CourseReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
