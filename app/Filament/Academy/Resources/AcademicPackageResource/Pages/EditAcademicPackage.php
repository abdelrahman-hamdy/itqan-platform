<?php
namespace App\Filament\Academy\Resources\AcademicPackageResource\Pages;
use App\Filament\Academy\Resources\AcademicPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAcademicPackage extends EditRecord {
    protected static string $resource = AcademicPackageResource::class;
    protected function getHeaderActions(): array { return [Actions\ViewAction::make()]; }
}
