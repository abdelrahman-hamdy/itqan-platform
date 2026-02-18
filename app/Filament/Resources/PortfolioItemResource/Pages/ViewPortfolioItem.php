<?php

namespace App\Filament\Resources\PortfolioItemResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\PortfolioItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewPortfolioItem extends ViewRecord
{
    protected static string $resource = PortfolioItemResource::class;

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
