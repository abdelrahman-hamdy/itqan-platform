<?php
namespace App\Filament\Academy\Resources\SupervisorProfileResource\Pages;
use App\Filament\Academy\Resources\SupervisorProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSupervisorProfile extends EditRecord {
    protected static string $resource = SupervisorProfileResource::class;
    protected function getHeaderActions(): array { return [Actions\ViewAction::make()]; }
}
