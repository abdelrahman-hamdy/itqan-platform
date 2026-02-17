<?php

namespace App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMonitoredSession extends EditRecord
{
    protected static string $resource = MonitoredAllSessionsResource::class;

    /**
     * Get the record type from query parameter
     */
    protected function getSessionType(): string
    {
        return request()->query('type', 'quran');
    }

    /**
     * Resolve the record based on type
     */
    protected function resolveRecord(int|string $key): Model
    {
        $type = $this->getSessionType();

        return match ($type) {
            'academic' => AcademicSession::with(['academicTeacher.user', 'academicIndividualLesson.academicSubject', 'student'])->findOrFail($key),
            'interactive' => InteractiveCourseSession::with(['course.assignedTeacher.user', 'course.subject'])->findOrFail($key),
            default => QuranSession::with(['quranTeacher', 'circle', 'student', 'individualCircle'])->findOrFail($key),
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ التغييرات بنجاح';
    }
}
