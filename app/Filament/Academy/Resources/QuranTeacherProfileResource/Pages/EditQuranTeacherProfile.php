<?php
namespace App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages;
use App\Filament\Academy\Resources\QuranTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditQuranTeacherProfile extends EditRecord {
    protected static string $resource = QuranTeacherProfileResource::class;
    protected function getHeaderActions(): array { return [Actions\ViewAction::make()]; }
}
