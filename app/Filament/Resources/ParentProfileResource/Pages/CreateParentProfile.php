<?php

namespace App\Filament\Resources\ParentProfileResource\Pages;

use App\Filament\Resources\ParentProfileResource;
use App\Models\User;
use App\Notifications\ParentInvitationNotification;
use App\Services\AcademyContextService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateParentProfile extends CreateRecord
{
    protected static string $resource = ParentProfileResource::class;

    /**
     * Prepare form data before creating the record
     * Following the same pattern as QuranTeacherProfile and AcademicTeacherProfile
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Get the current academy ID from context
        $academyContextService = app(AcademyContextService::class);
        $academyId = $academyContextService->getCurrentAcademyId();

        // If no academy selected, throw error
        if (!$academyId) {
            throw new \Exception('Please select an academy before creating a parent profile.');
        }

        // Set academy_id for the parent profile
        $data['academy_id'] = $academyId;

        return $data;
    }

    /**
     * Create User account after parent profile is created
     * This matches the pattern used by QuranTeacherProfile and AcademicTeacherProfile
     */
    protected function afterCreate(): void
    {
        $parentProfile = $this->record;

        try {
            // Create User account
            $user = User::create([
                'academy_id' => $parentProfile->academy_id,
                'first_name' => $parentProfile->first_name,
                'last_name' => $parentProfile->last_name,
                'email' => $parentProfile->email,
                'phone' => $parentProfile->phone,
                'password' => Hash::make(Str::random(32)), // Generate random password (will be reset by user)
                'user_type' => 'parent',
                'email_verified_at' => now(), // Auto-verify email
                'avatar' => $parentProfile->avatar,
            ]);

            // Link the User account to the parent profile
            $parentProfile->update([
                'user_id' => $user->id,
            ]);

            // Generate password reset token
            $token = Password::broker()->createToken($user);

            // Send invitation email with password reset link
            $user->notify(new ParentInvitationNotification($parentProfile, $token));

            Notification::make()
                ->success()
                ->title('تم إنشاء حساب ولي الأمر بنجاح')
                ->body('تم إرسال رسالة دعوة إلى البريد الإلكتروني لولي الأمر لتعيين كلمة المرور.')
                ->send();
        } catch (\Exception $e) {
            // Log error but don't fail the parent creation
            \Log::error('Failed to create user account for parent', [
                'parent_id' => $parentProfile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->warning()
                ->title('تم إنشاء ملف ولي الأمر بنجاح')
                ->body('لكن حدث خطأ أثناء إنشاء حساب المستخدم أو إرسال الدعوة. يمكنك إنشاؤه لاحقاً من صفحة التعديل.')
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
