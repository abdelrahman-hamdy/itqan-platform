<?php

namespace App\Filament\Resources\QuranPackageResource\Pages;

use App\Filament\Resources\QuranPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranPackages extends ListRecords
{
    protected static string $resource = QuranPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة باقة قرآن جديدة'),
        ];
    }

    public function getTitle(): string
    {
        return 'باقات القرآن الكريم';
    }
}
