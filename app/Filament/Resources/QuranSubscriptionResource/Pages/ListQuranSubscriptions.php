<?php

namespace App\Filament\Resources\QuranSubscriptionResource\Pages;

use Filament\Actions\CreateAction;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Models\QuranSubscription;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListQuranSubscriptions extends ListRecords
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة اشتراك جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'اشتراكات القرآن الكريم';
    }

}
