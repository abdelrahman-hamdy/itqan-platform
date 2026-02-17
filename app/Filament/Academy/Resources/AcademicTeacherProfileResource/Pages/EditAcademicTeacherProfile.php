<?php
namespace App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAcademicTeacherProfile extends EditRecord {
    protected static string $resource = AcademicTeacherProfileResource::class;
    protected function getHeaderActions(): array { return [Actions\ViewAction::make()]; }
}
