<?php

namespace App\Filament\Academy\Resources\SessionRecordingResource\Pages;

use Filament\Actions\Action;
use App\Filament\Academy\Resources\SessionRecordingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSessionRecording extends ViewRecord
{
    protected static string $resource = SessionRecordingResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->display_name ?? 'تفاصيل التسجيل';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('تحميل التسجيل')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->getRecord()->isAvailable())
                ->url(fn () => $this->getRecord()->getDownloadUrl())
                ->openUrlInNewTab(),

            Action::make('stream')
                ->label('تشغيل التسجيل')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn () => $this->getRecord()->isAvailable())
                ->url(fn () => $this->getRecord()->getStreamUrl())
                ->openUrlInNewTab(),

            Action::make('delete')
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('حذف التسجيل')
                ->modalDescription('هل أنت متأكد من حذف هذا التسجيل؟')
                ->modalSubmitActionLabel('نعم، احذف')
                ->visible(fn () => $this->getRecord()->status->canDelete() &&
                    auth()->user()?->can('delete', $this->getRecord()))
                ->action(function () {
                    $this->getRecord()->markAsDeleted();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
