<?php

namespace App\Filament\Resources\TeacherReviewResource\Pages;

use App\Filament\Resources\TeacherReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherReviews extends ListRecords
{
    protected static string $resource = TeacherReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
