<?php

namespace App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;

class ViewMonitoredInteractiveCourseSession extends ViewRecord
{
    protected static string $resource = MonitoredInteractiveCourseSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            DeleteAction::make()
                ->label('حذف')
                ->successRedirectUrl(fn () => MonitoredInteractiveCourseSessionsResource::getUrl('index')),
        ];
    }

    protected function getFooterSchemas(): array
    {
        return [
            Section::make(__('recordings.session_recordings'))
                ->icon('heroicon-o-video-camera')
                ->schema([
                    ViewEntry::make('recordings_view')
                        ->view('filament.infolists.components.session-recordings'),
                ])
                ->visible(fn ($record) => $record instanceof \App\Contracts\RecordingCapable && $record->isRecordingEnabled()),
        ];
    }
}
