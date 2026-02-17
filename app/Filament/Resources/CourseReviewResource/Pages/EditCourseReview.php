<?php

namespace App\Filament\Resources\CourseReviewResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\CourseReviewResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditCourseReview extends EditRecord
{
    protected static string $resource = CourseReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
