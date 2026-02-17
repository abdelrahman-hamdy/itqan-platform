<?php

namespace App\Filament\Resources\TeacherReviewResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\TeacherReviewResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditTeacherReview extends EditRecord
{
    protected static string $resource = TeacherReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
