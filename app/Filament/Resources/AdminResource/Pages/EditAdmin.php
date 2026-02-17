<?php

namespace App\Filament\Resources\AdminResource\Pages;

use Filament\Actions\DeleteAction;
use App\Enums\UserType;
use App\Filament\Resources\AdminResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_type'] = UserType::ADMIN->value;

        $isActive = array_key_exists('active_status', $data) ? (bool) $data['active_status'] : true;
        $data['active_status'] = $isActive;
        $data['status'] = $isActive ? 'active' : 'inactive';

        return $data;
    }
}
