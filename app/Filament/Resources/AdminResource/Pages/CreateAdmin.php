<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Enums\UserType;
use App\Filament\Resources\AdminResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove guarded fields from mass-assignment data — set directly in handleRecordCreation
        unset($data['user_type'], $data['active_status'], $data['status']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = static::getModel()::create($data);

        // Set guarded fields directly (not mass-assignable for security)
        $record->user_type = UserType::ADMIN->value;
        $record->active_status = true;
        $record->status = 'active';
        $record->save();

        return $record;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء المدير بنجاح';
    }
}
