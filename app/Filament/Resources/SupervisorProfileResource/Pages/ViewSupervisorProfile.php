<?php

namespace App\Filament\Resources\SupervisorProfileResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\SupervisorProfileResource;
use App\Filament\Widgets\SupervisorResponsibilitiesWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupervisorProfile extends ViewRecord
{
    protected static string $resource = SupervisorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    /**
     * Get footer widgets to display responsibilities table.
     */
    protected function getFooterWidgets(): array
    {
        return [
            SupervisorResponsibilitiesWidget::class,
        ];
    }

    /**
     * Pass the record to the widget.
     */
    public function getWidgetsData(): array
    {
        return [
            'record' => $this->record,
        ];
    }
}
