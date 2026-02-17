<?php

namespace App\Filament\Academy\Resources\ParentProfileResource\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\ParentProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditParentProfile extends EditRecord
{
    protected static string $resource = ParentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
