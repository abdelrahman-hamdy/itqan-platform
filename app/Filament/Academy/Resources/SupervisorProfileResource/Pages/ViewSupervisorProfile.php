<?php
namespace App\Filament\Academy\Resources\SupervisorProfileResource\Pages;
use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\SupervisorProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
class ViewSupervisorProfile extends ViewRecord {
    protected static string $resource = SupervisorProfileResource::class;
    protected function getHeaderActions(): array { return [EditAction::make()]; }
}
