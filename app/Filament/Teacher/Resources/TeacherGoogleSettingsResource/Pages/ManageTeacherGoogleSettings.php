<?php

namespace App\Filament\Teacher\Resources\TeacherGoogleSettingsResource\Pages;

use App\Filament\Teacher\Resources\TeacherGoogleSettingsResource;
use App\Models\User;
use App\Models\GoogleToken;
use Filament\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class ManageTeacherGoogleSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TeacherGoogleSettingsResource::class;

    protected static string $view = 'filament.teacher.resources.teacher-google-settings-resource.pages.manage-teacher-google-settings';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'إعدادات Google Meet الشخصية';
    }

    public function getSubheading(): ?string
    {
        return 'إدارة إعداداتك الشخصية للاتصال مع Google Meet و Google Calendar - هذه الإعدادات خاصة بك وستطبق على جلساتك فقط';
    }

    public function mount(): void
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() && !$user->isAcademicTeacher()) {
            throw new \Exception('Access denied. Only teachers can access this page.');
        }
        
        // Fill the form with current user data
        $this->form->fill($user->toArray());
    }

    public function form(Form $form): Form
    {
        return TeacherGoogleSettingsResource::form($form)
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            $user = Auth::user();
            
            // Update teacher preferences
            $user->update($data);
            
            Notification::make()
                ->title('تم حفظ الإعدادات بنجاح')
                ->body('تم حفظ إعداداتك الشخصية لـ Google Meet بنجاح')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء حفظ الإعدادات')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الإعدادات')
                ->submit('save'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_google_connection')
                ->label('اختبار اتصال Google')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function () {
                    $user = Auth::user();
                    $token = GoogleToken::where('user_id', $user->id)->active()->first();
                    
                    if (!$token) {
                        Notification::make()
                            ->title('غير متصل')
                            ->body('يرجى ربط حسابك مع Google أولاً')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    // Test token validity
                    if ($token->is_expired) {
                        Notification::make()
                            ->title('انتهت صلاحية الرمز')
                            ->body('يرجى إعادة ربط حسابك مع Google')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    $token->recordUsage();
                    
                    Notification::make()
                        ->title('الاتصال سليم')
                        ->body('حسابك مربوط بـ Google بشكل صحيح')
                        ->success()
                        ->send();
                }),
                
            Action::make('help')
                ->label('المساعدة')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->action(function () {
                    Notification::make()
                        ->title('المساعدة - إعدادات Google Meet')
                        ->body('لربط حسابك مع Google، انقر على "ربط حساب Google" وتابع التعليمات. بعد الربط يمكنك تخصيص إعداداتك الشخصية للاجتماعات.')
                        ->info()
                        ->send();
                }),
        ];
    }
} 