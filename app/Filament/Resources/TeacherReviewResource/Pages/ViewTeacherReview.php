<?php

namespace App\Filament\Resources\TeacherReviewResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\TeacherReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewTeacherReview extends ViewRecord
{
    protected static string $resource = TeacherReviewResource::class;

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
