<?php

namespace App\Filament\Resources\TeacherReviewResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\TeacherReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacherReview extends ViewRecord
{
    protected static string $resource = TeacherReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
