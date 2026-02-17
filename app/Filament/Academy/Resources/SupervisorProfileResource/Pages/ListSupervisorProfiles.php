<?php
namespace App\Filament\Academy\Resources\SupervisorProfileResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Academy\Resources\SupervisorProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListSupervisorProfiles extends ListRecords {
    protected static string $resource = SupervisorProfileResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
