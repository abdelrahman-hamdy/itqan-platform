<?php

namespace App\Filament\Resources\CourseReviewResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\CourseReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewCourseReview extends ViewRecord
{
    protected static string $resource = CourseReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
