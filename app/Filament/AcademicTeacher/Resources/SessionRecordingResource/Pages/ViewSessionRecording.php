<?php

namespace App\Filament\AcademicTeacher\Resources\SessionRecordingResource\Pages;

use App\Filament\AcademicTeacher\Resources\SessionRecordingResource;
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
            Actions\Action::make('download')
                ->label('تحميل التسجيل')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->getRecord()->isAvailable())
                ->url(fn () => $this->getRecord()->getDownloadUrl())
                ->openUrlInNewTab(),

            Actions\Action::make('stream')
                ->label('تشغيل التسجيل')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn () => $this->getRecord()->isAvailable())
                ->url(fn () => $this->getRecord()->getStreamUrl())
                ->openUrlInNewTab(),

            // Teachers cannot delete recordings - no delete action here
        ];
    }
}
