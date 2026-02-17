<?php
namespace App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages;
use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\QuranTeacherProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;
class EditQuranTeacherProfile extends EditRecord {
    protected static string $resource = QuranTeacherProfileResource::class;
    protected function getHeaderActions(): array { return [ViewAction::make()]; }
}
