<?php

namespace App\Filament\Resources\QuranTeacherProfileResource\Pages;

use App\Filament\Resources\QuranTeacherProfileResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateQuranTeacherProfile extends CreateRecord
{
    protected static string $resource = QuranTeacherProfileResource::class;

    /**
     * Prepare form data before creating the record
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove password fields from teacher profile data (will be handled in afterCreate)
        unset($data['password'], $data['password_confirmation']);

        return $data;
    }

    /**
     * Create User account after teacher profile is created
     */
    protected function afterCreate(): void
    {
        $teacherProfile = $this->record;
        $formData = $this->form->getRawState();

        // Check if password was provided in the form
        if (!empty($formData['password'])) {
            try {
                // Create User account
                $user = User::create([
                    'academy_id' => $teacherProfile->academy_id,
                    'first_name' => $teacherProfile->first_name,
                    'last_name' => $teacherProfile->last_name,
                    'email' => $teacherProfile->email,
                    'phone' => $teacherProfile->phone,
                    'password' => Hash::make($formData['password']),
                    'user_type' => User::ROLE_QURAN_TEACHER,
                    'active_status' => true,
                    'avatar' => $teacherProfile->avatar,
                ]);

                // Link the User account to the teacher profile
                $teacherProfile->update([
                    'user_id' => $user->id,
                ]);

                Notification::make()
                    ->success()
                    ->title('تم إنشاء حساب المعلم بنجاح')
                    ->body('يمكن للمعلم الآن تسجيل الدخول باستخدام البريد الإلكتروني وكلمة المرور.')
                    ->send();
            } catch (\Exception $e) {
                // Log error but don't fail the teacher creation
                \Log::error('Failed to create user account for quran teacher', [
                    'teacher_id' => $teacherProfile->id,
                    'error' => $e->getMessage(),
                ]);

                Notification::make()
                    ->warning()
                    ->title('تم إنشاء ملف المعلم بنجاح')
                    ->body('لكن حدث خطأ أثناء إنشاء حساب المستخدم. يمكنك إنشاؤه لاحقاً من صفحة التعديل.')
                    ->send();
            }
        }
    }
}
