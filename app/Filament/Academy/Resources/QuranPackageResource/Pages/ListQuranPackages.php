<?php
namespace App\Filament\Academy\Resources\QuranPackageResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Academy\Resources\QuranPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListQuranPackages extends ListRecords {
    protected static string $resource = QuranPackageResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
