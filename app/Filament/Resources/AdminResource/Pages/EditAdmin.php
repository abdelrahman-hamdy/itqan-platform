<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_type'] = 'admin';

        $isActive = array_key_exists('active_status', $data) ? (bool) $data['active_status'] : true;
        $data['active_status'] = $isActive;
        $data['status'] = $isActive ? 'active' : 'inactive';

        return $data;
    }
}
