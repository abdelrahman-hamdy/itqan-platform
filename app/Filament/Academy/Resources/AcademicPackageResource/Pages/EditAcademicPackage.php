<?php
namespace App\Filament\Academy\Resources\AcademicPackageResource\Pages;
use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\AcademicPackageResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;
class EditAcademicPackage extends EditRecord {
    protected static string $resource = AcademicPackageResource::class;
    protected function getHeaderActions(): array { return [ViewAction::make()]; }
}
