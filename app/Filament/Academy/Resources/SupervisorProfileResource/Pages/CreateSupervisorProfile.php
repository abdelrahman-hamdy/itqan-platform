<?php

namespace App\Filament\Academy\Resources\SupervisorProfileResource\Pages;

use App\Filament\Academy\Resources\SupervisorProfileResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CreateSupervisorProfile extends CreateRecord
{
    protected static string $resource = SupervisorProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['academy_id'])) {
            $data['academy_id'] = auth()->user()->academy_id;
        }

        // Remove password fields (handled in afterCreate)
        unset($data['password'], $data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $supervisorProfile = $this->record;
        $formData = $this->form->getRawState();

        if (! empty($formData['password'])) {
            try {
                $user = User::create([
                    'academy_id' => $supervisorProfile->academy_id,
                    'first_name' => $supervisorProfile->first_name,
                    'last_name' => $supervisorProfile->last_name,
                    'email' => $supervisorProfile->email,
                    'phone' => $supervisorProfile->phone,
                    'password' => Hash::make($formData['password']),
                    'user_type' => User::ROLE_SUPERVISOR,
                    'active_status' => $formData['user_active_status'] ?? true,
                    'avatar' => $supervisorProfile->avatar,
                ]);

                $supervisorProfile->update([
                    'user_id' => $user->id,
                ]);

                Notification::make()
                    ->success()
                    ->title('تم إنشاء حساب المشرف بنجاح')
                    ->body('يمكن للمشرف الآن تسجيل الدخول باستخدام البريد الإلكتروني وكلمة المرور.')
                    ->send();
            } catch (Exception $e) {
                Log::error('Failed to create user account for supervisor', [
                    'supervisor_id' => $supervisorProfile->id,
                    'error' => $e->getMessage(),
                ]);

                Notification::make()
                    ->warning()
                    ->title('تم إنشاء ملف المشرف بنجاح')
                    ->body('لكن حدث خطأ أثناء إنشاء حساب المستخدم: ' . $e->getMessage())
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
