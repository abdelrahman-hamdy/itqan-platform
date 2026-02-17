<?php
namespace App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages;
use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
class ViewAcademicTeacherProfile extends ViewRecord {
    protected static string $resource = AcademicTeacherProfileResource::class;
    protected function getHeaderActions(): array { return [EditAction::make()]; }
}
