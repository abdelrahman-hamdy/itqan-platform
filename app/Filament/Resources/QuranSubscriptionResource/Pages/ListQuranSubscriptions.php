<?php

namespace App\Filament\Resources\QuranSubscriptionResource\Pages;

use App\Filament\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranSubscriptions extends ListRecords
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة اشتراك جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'اشتراكات القرآن الكريم';
    }
} 