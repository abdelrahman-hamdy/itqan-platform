<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use App\Services\AcademyContextService;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirect to the admins list page after creation
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure academy_id is set if not already provided
        if (empty($data['academy_id'])) {
            $data['academy_id'] = AcademyContextService::getCurrentAcademyId();
        }

        // Always persist the correct user type
        $data['user_type'] = 'admin';

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
