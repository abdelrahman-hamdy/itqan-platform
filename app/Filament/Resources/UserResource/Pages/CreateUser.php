<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected static ?string $title = 'إضافة مستخدم جديد';
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء المستخدم بنجاح';
    }
    
    protected function afterCreate(): void
    {
        $user = $this->record;
        
        // Send notification based on user role
        $roleNames = [
            'super_admin' => 'مدير النظام',
            'academy_admin' => 'مدير أكاديمية',
            'teacher' => 'معلم',
            'supervisor' => 'مشرف',
            'student' => 'طالب',
            'parent' => 'ولي أمر',
        ];
        
        $roleName = $roleNames[$user->role] ?? $user->role;
        
        Notification::make()
            ->title('مستخدم جديد')
            ->body("تم إنشاء حساب {$roleName} جديد: {$user->full_name}")
            ->success()
            ->send();
    }
} 