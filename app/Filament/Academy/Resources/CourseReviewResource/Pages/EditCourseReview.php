<?php

namespace App\Filament\Academy\Resources\CourseReviewResource\Pages;

use App\Filament\Academy\Resources\CourseReviewResource;
use Filament\Actions\DeleteAction;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditCourseReview extends EditRecord
{
    protected static string $resource = CourseReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
