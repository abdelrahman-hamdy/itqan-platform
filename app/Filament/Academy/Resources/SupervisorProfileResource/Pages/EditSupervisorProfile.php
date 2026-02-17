<?php
namespace App\Filament\Academy\Resources\SupervisorProfileResource\Pages;
use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\SupervisorProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;
class EditSupervisorProfile extends EditRecord {
    protected static string $resource = SupervisorProfileResource::class;
    protected function getHeaderActions(): array { return [ViewAction::make()]; }
}
