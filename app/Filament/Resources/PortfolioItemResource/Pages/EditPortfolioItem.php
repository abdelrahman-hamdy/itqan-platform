<?php

namespace App\Filament\Resources\PortfolioItemResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\PortfolioItemResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditPortfolioItem extends EditRecord
{
    protected static string $resource = PortfolioItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
