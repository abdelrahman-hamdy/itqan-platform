<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\StudentProgressResource\Pages;
use App\Filament\Resources\StudentProgressResource as SuperAdminStudentProgressResource;

class StudentProgressResource extends SuperAdminStudentProgressResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الدورات المسجلة';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentProgress::route('/'),
            'view' => Pages\ViewStudentProgress::route('/{record}'),
        ];
    }
}
