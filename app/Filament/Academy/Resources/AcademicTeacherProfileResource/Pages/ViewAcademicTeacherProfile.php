<?php
namespace App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
class ViewAcademicTeacherProfile extends ViewRecord {
    protected static string $resource = AcademicTeacherProfileResource::class;
    protected function getHeaderActions(): array { return [Actions\EditAction::make()]; }
}
