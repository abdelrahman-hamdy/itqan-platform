<?php

namespace App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Teacher\Resources\QuranIndividualCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranIndividualCircles extends ListRecords
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';

        return [
            route('teacher.profile', ['subdomain' => $subdomain]) => 'ملفي الشخصي',
            '' => 'الحلقات الفردية',
        ];
    }
}
