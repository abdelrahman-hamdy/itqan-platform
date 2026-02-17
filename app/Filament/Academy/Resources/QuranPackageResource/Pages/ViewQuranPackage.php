<?php
namespace App\Filament\Academy\Resources\QuranPackageResource\Pages;
use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\QuranPackageResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
class ViewQuranPackage extends ViewRecord {
    protected static string $resource = QuranPackageResource::class;
    protected function getHeaderActions(): array { return [EditAction::make()]; }
}
