<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Enums\UserType;
use App\Filament\Resources\AdminResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Always persist the correct user type
        $data['user_type'] = UserType::ADMIN->value;

        // Normalize active status and sync legacy status column
        $isActive = array_key_exists('active_status', $data) ? (bool) $data['active_status'] : true;
        $data['active_status'] = $isActive;
        $data['status'] = $isActive ? 'active' : 'inactive';

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء المدير بنجاح';
    }
}
