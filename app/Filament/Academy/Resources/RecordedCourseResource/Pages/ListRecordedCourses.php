<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Academy\Resources\RecordedCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListRecordedCourses extends ListRecords
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إنشاء دورة جديدة')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // We can add widgets here later for course statistics
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // Filter by current user's academy
        if (Auth::user()->academy_id) {
            $query->where('academy_id', Auth::user()->academy_id);
        }

        // If user is a teacher, only show their courses
        if (Auth::user()->isAcademicTeacher()) {
            $query->where('instructor_id', Auth::user()->academicTeacher->id);
        }

        return $query;
    }
}
