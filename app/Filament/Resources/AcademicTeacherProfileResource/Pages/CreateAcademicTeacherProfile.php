<?php

namespace App\Filament\Resources\AcademicTeacherProfileResource\Pages;

use App\Filament\Resources\AcademicTeacherProfileResource;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateAcademicTeacherProfile extends CreateRecord
{
    protected static string $resource = AcademicTeacherProfileResource::class;

    /**
     * Ensure academy_id is set before creating the record
     * This is critical because the academy_id field may be hidden in the form
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If academy_id is not set (because field was hidden), set it from context
        if (empty($data['academy_id'])) {
            $currentAcademyId = AcademyContextService::getCurrentAcademyId();

            if (!$currentAcademyId) {
                throw new \Exception('لا يمكن إنشاء مدرس بدون تحديد أكاديمية. يرجى اختيار أكاديمية من القائمة أعلاه.');
            }

            $data['academy_id'] = $currentAcademyId;
        }

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
                    'user_type' => User::ROLE_ACADEMIC_TEACHER,
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
                \Log::error('Failed to create user account for academic teacher', [
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

    /**
     * Redirect to the index page after creation instead of trying to view the record
     * (since we don't have a view page for this resource)
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
