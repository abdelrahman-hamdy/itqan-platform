<?php

namespace App\Filament\Resources\QuranPackageResource\Pages;

use App\Filament\Resources\QuranPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranPackage extends ViewRecord
{
    protected static string $resource = QuranPackageResource::class;

    public function getTitle(): string
    {
        return 'باقة القرآن: '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('activate')
                ->label('تفعيل الباقة')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['is_active' => true]))
                ->visible(fn () => ! $this->record->is_active),
            Actions\Action::make('deactivate')
                ->label('إلغاء تفعيل الباقة')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('هل أنت متأكد من إلغاء تفعيل هذه الباقة؟ لن يتمكن الطلاب من الاشتراك فيها.')
                ->action(fn () => $this->record->update(['is_active' => false]))
                ->visible(fn () => $this->record->is_active),
        ];
    }
}
