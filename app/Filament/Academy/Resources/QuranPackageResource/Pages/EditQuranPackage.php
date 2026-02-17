<?php
namespace App\Filament\Academy\Resources\QuranPackageResource\Pages;
use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\QuranPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditQuranPackage extends EditRecord {
    protected static string $resource = QuranPackageResource::class;
    protected function getHeaderActions(): array { return [ViewAction::make()]; }
}
