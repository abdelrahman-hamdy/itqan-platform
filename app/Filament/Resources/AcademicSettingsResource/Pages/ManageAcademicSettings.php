<?php

namespace App\Filament\Resources\AcademicSettingsResource\Pages;

use App\Filament\Resources\AcademicSettingsResource;
use App\Models\AcademicSettings;
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
        $academyName = auth()->user()->academy->name ?? 'الأكاديمية';
        return "إدارة إعدادات القسم الأكاديمي لـ {$academyName}";
    }

    public function mount(): void
    {
        $academyId = auth()->user()->academy_id ?? 1;
        
        // Get or create settings for current academy
        $settings = AcademicSettings::getForAcademy($academyId);
        
        // Fill the form with current settings
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(AcademicSettingsResource::form(new Form)->getSchema())
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            $academyId = auth()->user()->academy_id ?? 1;
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
} 