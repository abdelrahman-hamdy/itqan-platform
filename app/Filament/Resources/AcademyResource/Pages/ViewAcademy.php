<?php

namespace App\Filament\Resources\AcademyResource\Pages;

use App\Filament\Resources\AcademyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademy extends ViewRecord
{
    protected static string $resource = AcademyResource::class;
    
    protected static ?string $title = 'عرض الأكاديمية';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('visit_academy')
                ->label('زيارة الأكاديمية')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->record->full_url)
                ->openUrlInNewTab(),
                
            Actions\EditAction::make()
                ->label('تعديل')
                ->icon('heroicon-o-pencil'),
        ];
    }
}
