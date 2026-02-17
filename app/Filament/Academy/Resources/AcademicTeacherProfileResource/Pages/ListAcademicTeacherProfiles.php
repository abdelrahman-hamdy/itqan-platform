<?php
namespace App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAcademicTeacherProfiles extends ListRecords {
    protected static string $resource = AcademicTeacherProfileResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
