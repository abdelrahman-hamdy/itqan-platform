<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\AcademicSubjectResource\Pages;
use App\Filament\Resources\AcademicSubjectResource as SuperAdminAcademicSubjectResource;

class AcademicSubjectResource extends SuperAdminAcademicSubjectResource
{
    protected static ?int $navigationSort = 8;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicSubjects::route('/'),
            'create' => Pages\CreateAcademicSubject::route('/create'),
            'view' => Pages\ViewAcademicSubject::route('/{record}'),
            'edit' => Pages\EditAcademicSubject::route('/{record}/edit'),
        ];
    }
}
