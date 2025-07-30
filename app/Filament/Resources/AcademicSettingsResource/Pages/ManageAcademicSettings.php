<?php

namespace App\Filament\Resources\AcademicSettingsResource\Pages;

use App\Filament\Resources\AcademicSettingsResource;
use App\Models\AcademicSettings;
use App\Services\AcademyContextService;
use Filament\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ManageAcademicSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AcademicSettingsResource::class;

    protected static string $view = 'filament.resources.academic-settings-resource.pages.manage-academic-settings';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'الإعدادات الأكاديمية';
    }

    public function getSubheading(): ?string
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        $academyName = $currentAcademy?->name ?? 'الأكاديمية';
        return "إدارة إعدادات القسم الأكاديمي لـ {$academyName} | لتحرير ألوان الأكاديمية والهوية البصرية، استخدم صفحة 'إدارة الأكاديميات'";
    }

    public function mount(): void
    {
        $academyId = AcademyContextService::getCurrentAcademyId();
        
        if (!$academyId) {
            throw new \Exception('No academy context available. Please select an academy first.');
        }
        
        // Get or create settings for current academy
        $settings = AcademicSettings::getForAcademy($academyId);
        
        // Fill the form with current settings
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return AcademicSettingsResource::form($form)
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            $academyId = AcademyContextService::getCurrentAcademyId();
            
            if (!$academyId) {
                throw new \Exception('No academy context available. Please select an academy first.');
            }
            
            $settings = AcademicSettings::getForAcademy($academyId);
            
            // Add updated_by field
            $data['updated_by'] = auth()->id();
            
            $settings->update($data);

            Notification::make()
                ->title('تم حفظ الإعدادات بنجاح')
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
            Actions\Action::make('save')
                ->label('حفظ الإعدادات')
                ->action('save')
                ->keyBindings(['mod+s'])
                ->color('success')
                ->icon('heroicon-o-check'),
        ];
    }

    protected function getData(): array
    {
        $academyId = AcademyContextService::getCurrentAcademyId();
        
        if (!$academyId) {
            throw new \Exception('No academy context available. Please select an academy first.');
        }

        // Get or create settings for current academy
        $settings = AcademicSettings::getForAcademy($academyId);
        
        return $settings->toArray();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $academyId = AcademyContextService::getCurrentAcademyId();
        
        if (!$academyId) {
            throw new \Exception('No academy context available. Please select an academy first.');
        }

        $data['academy_id'] = $academyId;
        return $data;
    }
} 