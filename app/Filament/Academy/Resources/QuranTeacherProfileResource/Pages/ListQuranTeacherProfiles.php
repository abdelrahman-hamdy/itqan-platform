<?php
namespace App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages;
use App\Filament\Academy\Resources\QuranTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListQuranTeacherProfiles extends ListRecords {
    protected static string $resource = QuranTeacherProfileResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
