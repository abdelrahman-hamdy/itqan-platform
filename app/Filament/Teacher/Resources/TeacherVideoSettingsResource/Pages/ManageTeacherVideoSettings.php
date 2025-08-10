<?php

namespace App\Filament\Teacher\Resources\TeacherVideoSettingsResource\Pages;

use App\Filament\Teacher\Resources\TeacherVideoSettingsResource;
use App\Models\TeacherVideoSettings;
use App\Models\VideoSettings;
use App\Models\Academy;
use App\Services\AcademyContextService;
use Filament\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class ManageTeacherVideoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TeacherVideoSettingsResource::class;
    protected static string $view = 'filament.teacher.resources.teacher-video-settings-resource.pages.manage-teacher-video-settings';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'إعدادات الفيديو الشخصية';
    }

    public function getSubheading(): ?string
    {
        return 'تخصيص إعداداتك الشخصية للاجتماعات المرئية - هذه الإعدادات خاصة بك وتطبق على جلساتك فقط';
    }

    public function form(Form $form): Form
    {
        return TeacherVideoSettingsResource::form($form)
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('test_settings')
                ->label('اختبار الإعدادات')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('info')
                ->action(function () {
                    try {
                        $user = Auth::user();
                        $academy = app(AcademyContextService::class)->getCurrentAcademy();
                        
                        if (!$academy || !$user) {
                            throw new \Exception('لم يتم العثور على البيانات المطلوبة');
                        }

                        $teacherSettings = TeacherVideoSettings::forTeacher($user, $academy);
                        $academySettings = VideoSettings::forAcademy($academy);
                        
                        $testResult = $teacherSettings->testSettings($academySettings);
                        
                        if ($testResult['status'] === 'success') {
                            Notification::make()
                                ->title('نجح الاختبار!')
                                ->body('جميع إعداداتك الشخصية تعمل بشكل صحيح')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('تحذير في الاختبار')
                                ->body($testResult['message'] ?? 'بعض الإعدادات قد تحتاج إلى مراجعة')
                                ->warning()
                                ->send();
                        }
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في الاختبار')
                            ->body('فشل اختبار الإعدادات: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('reset_to_defaults')
                ->label('إعادة تعيين للافتراضي')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('إعادة تعيين الإعدادات')
                ->modalDescription('هل أنت متأكد من إعادة تعيين جميع إعداداتك الشخصية للقيم الافتراضية؟')
                ->modalSubmitActionLabel('إعادة تعيين')
                ->action(function () {
                    try {
                        $user = Auth::user();
                        $academy = app(AcademyContextService::class)->getCurrentAcademy();
                        
                        if (!$academy || !$user) {
                            throw new \Exception('لم يتم العثور على البيانات المطلوبة');
                        }

                        // Delete existing settings to reset to defaults
                        TeacherVideoSettings::where('user_id', $user->id)
                            ->where('academy_id', $academy->id)
                            ->delete();

                        // Re-fill form with new defaults
                        $newRecord = $this->getRecord();
                        $data = $newRecord->toArray();
                        
                        if (isset($data['notification_methods']) && is_string($data['notification_methods'])) {
                            $data['notification_methods'] = json_decode($data['notification_methods'], true) ?? ['email'];
                        }
                        
                        if (isset($data['unavailable_days']) && is_string($data['unavailable_days'])) {
                            $data['unavailable_days'] = json_decode($data['unavailable_days'], true) ?? [];
                        }

                        $this->form->fill($data);
                        
                        Notification::make()
                            ->title('تم إعادة التعيين')
                            ->body('تم إعادة تعيين إعداداتك الشخصية للقيم الافتراضية')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('فشل إعادة التعيين')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Get the teacher's settings record
     */
    public function getRecord(): Model
    {
        $user = Auth::user();
        $academy = app(AcademyContextService::class)->getCurrentAcademy();
        
        if (!$academy || !$user) {
            throw new \Exception('لم يتم العثور على البيانات المطلوبة');
        }

        if (!$user->isQuranTeacher() && !$user->isAcademicTeacher()) {
            throw new \Exception('الوصول مرفوض. هذه الصفحة للمعلمين فقط.');
        }

        return TeacherVideoSettings::forTeacher($user, $academy);
    }

    /**
     * Handle form submission
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        DB::beginTransaction();
        
        try {
            // Handle JSON fields properly
            if (isset($data['notification_methods']) && is_array($data['notification_methods'])) {
                $data['notification_methods'] = json_encode($data['notification_methods']);
            }
            
            if (isset($data['unavailable_days']) && is_array($data['unavailable_days'])) {
                $data['unavailable_days'] = json_encode($data['unavailable_days']);
            }

            // Update the record
            $record->fill($data);
            $record->save();

            DB::commit();

            return $record;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }



    /**
     * Mount the page
     */
    public function mount(): void
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() && !$user->isAcademicTeacher()) {
            throw new \Exception('الوصول مرفوض. هذه الصفحة للمعلمين فقط.');
        }
        
        // Ensure we have a record for the current teacher
        $record = $this->getRecord();
        
        // Convert JSON fields back to arrays for the form
        $data = $record->toArray();
        
        if (isset($data['notification_methods']) && is_string($data['notification_methods'])) {
            $data['notification_methods'] = json_decode($data['notification_methods'], true) ?? ['email'];
        }
        
        if (isset($data['unavailable_days']) && is_string($data['unavailable_days'])) {
            $data['unavailable_days'] = json_decode($data['unavailable_days'], true) ?? [];
        }

        // Fill the form with current data
        $this->form->fill($data);
    }

    /**
     * Save the form
     */
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $record = $this->getRecord();
            
            $this->handleRecordUpdate($record, $data);
            
            Notification::make()
                ->title('تم الحفظ بنجاح')
                ->body('تم حفظ إعداداتك الشخصية للفيديو')
                ->success()
                ->send();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في الحفظ')
                ->body('فشل حفظ الإعدادات: ' . $e->getMessage())
                ->danger()
                ->send();
                
            throw $e;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('حفظ الإعدادات')
                ->submit('save'),
        ];
    }
}
