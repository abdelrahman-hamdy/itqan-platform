<?php
namespace App\Filament\Academy\Resources\AcademicPackageResource\Pages;
use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\AcademicPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
class ViewAcademicPackage extends ViewRecord {
    protected static string $resource = AcademicPackageResource::class;
    protected function getHeaderActions(): array { return [EditAction::make()]; }
}
