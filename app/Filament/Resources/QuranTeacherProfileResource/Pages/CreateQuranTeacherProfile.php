<?php

namespace App\Filament\Resources\QuranTeacherProfileResource\Pages;

use Exception;
use Log;
use App\Enums\UserType;
use App\Filament\Resources\QuranTeacherProfileResource;
use App\Models\User;
use Filament\Notifications\Notification;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

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
        if (! empty($formData['password'])) {
            try {
                // Create User account — user_type and active_status are guarded,
                // so set them via direct assignment to bypass mass-assignment protection
                $user = new User([
                    'academy_id' => $teacherProfile->academy_id,
                    'first_name' => $teacherProfile->first_name,
                    'last_name' => $teacherProfile->last_name,
                    'email' => $teacherProfile->email,
                    'phone' => $teacherProfile->phone,
                    'password' => $formData['password'], // 'hashed' cast auto-hashes
                    'avatar' => $teacherProfile->avatar,
                ]);
                $user->user_type = UserType::QURAN_TEACHER->value;
                $user->active_status = true;
                $user->save();

                // Link the User account to the teacher profile
                $teacherProfile->update([
                    'user_id' => $user->id,
                ]);

                Notification::make()
                    ->success()
                    ->title('تم إنشاء حساب المعلم بنجاح')
                    ->body('يمكن للمعلم الآن تسجيل الدخول باستخدام البريد الإلكتروني وكلمة المرور.')
                    ->send();
            } catch (Exception $e) {
                // Log error but don't fail the teacher creation
                Log::error('Failed to create user account for quran teacher', [
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
