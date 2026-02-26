<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\StudentProgressResource\Pages;
use App\Filament\Resources\StudentProgressResource as SuperAdminStudentProgressResource;
use Illuminate\Database\Eloquent\Builder;

class StudentProgressResource extends SuperAdminStudentProgressResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الدورات المسجلة';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $academyId = auth()->user()?->academy_id;
        if (!$academyId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->whereHas('recordedCourse', fn ($q) => $q->where('academy_id', $academyId));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentProgress::route('/'),
            'view' => Pages\ViewStudentProgress::route('/{record}'),
        ];
    }
}
