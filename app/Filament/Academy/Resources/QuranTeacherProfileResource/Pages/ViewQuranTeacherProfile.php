<?php
namespace App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages;
use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\QuranTeacherProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
class ViewQuranTeacherProfile extends ViewRecord {
    protected static string $resource = QuranTeacherProfileResource::class;
    protected function getHeaderActions(): array { return [EditAction::make()]; }
}
