<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\AcademicGradeLevelResource\Pages;
use App\Filament\Resources\AcademicGradeLevelResource as SuperAdminAcademicGradeLevelResource;

class AcademicGradeLevelResource extends SuperAdminAcademicGradeLevelResource
{
    protected static ?int $navigationSort = 7;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicGradeLevels::route('/'),
            'create' => Pages\CreateAcademicGradeLevel::route('/create'),
            'view' => Pages\ViewAcademicGradeLevel::route('/{record}'),
            'edit' => Pages\EditAcademicGradeLevel::route('/{record}/edit'),
        ];
    }
}
