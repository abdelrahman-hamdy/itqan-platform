<?php
namespace App\Filament\Academy\Resources\AcademicPackageResource\Pages;
use App\Filament\Academy\Resources\AcademicPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAcademicPackages extends ListRecords {
    protected static string $resource = AcademicPackageResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
