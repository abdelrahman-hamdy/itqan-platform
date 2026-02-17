<?php
namespace App\Filament\Academy\Resources\QuranPackageResource\Pages;
use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\QuranPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
class ViewQuranPackage extends ViewRecord {
    protected static string $resource = QuranPackageResource::class;
    protected function getHeaderActions(): array { return [EditAction::make()]; }
}
