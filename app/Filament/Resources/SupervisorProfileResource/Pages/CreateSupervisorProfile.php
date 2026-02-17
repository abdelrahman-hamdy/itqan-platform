<?php

namespace App\Filament\Resources\SupervisorProfileResource\Pages;

use Exception;
use Log;
use App\Filament\Resources\SupervisorProfileResource;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Notifications\Notification;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateSupervisorProfile extends CreateRecord
{
    protected static string $resource = SupervisorProfileResource::class;

    /**
     * Ensure academy_id is set before creating the record
     * This is critical because the academy_id field may be hidden in the form
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If academy_id is not set (because field was hidden), set it from context
        if (empty($data['academy_id'])) {
            $currentAcademyId = AcademyContextService::getCurrentAcademyId();

            if (! $currentAcademyId) {
                throw new Exception('لا يمكن إنشاء مشرف بدون تحديد أكاديمية. يرجى اختيار أكاديمية من القائمة أعلاه.');
            }

            $data['academy_id'] = $currentAcademyId;
        }

        // Remove password fields from supervisor profile data (will be handled in afterCreate)
        unset($data['password'], $data['password_confirmation']);

        return $data;
    }

    /**
     * Create User account after supervisor profile is created
     */
    protected function afterCreate(): void
    {
        $supervisorProfile = $this->record;
        $formData = $this->form->getRawState();

        // Check if password was provided in the form
        if (! empty($formData['password'])) {
            try {
                // Create User account (name is auto-generated from first_name + last_name)
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

                // Link the User account to the supervisor profile
                $supervisorProfile->update([
                    'user_id' => $user->id,
                ]);

                Notification::make()
                    ->success()
                    ->title('تم إنشاء حساب المشرف بنجاح')
                    ->body('يمكن للمشرف الآن تسجيل الدخول باستخدام البريد الإلكتروني وكلمة المرور.')
                    ->send();
            } catch (Exception $e) {
                // Log detailed error for debugging
                Log::error('Failed to create user account for supervisor', [
                    'supervisor_id' => $supervisorProfile->id,
                    'supervisor_email' => $supervisorProfile->email,
                    'academy_id' => $supervisorProfile->academy_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                Notification::make()
                    ->warning()
                    ->title('تم إنشاء ملف المشرف بنجاح')
                    ->body('لكن حدث خطأ أثناء إنشاء حساب المستخدم: '.$e->getMessage())
                    ->send();
            }
        }
    }

    /**
     * Redirect to the index page after creation instead of trying to view the record
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
