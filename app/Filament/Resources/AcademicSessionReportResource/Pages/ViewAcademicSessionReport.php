<?php

namespace App\Filament\Resources\AcademicSessionReportResource\Pages;

use App\Filament\Resources\AcademicSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSessionReport extends ViewRecord
{
    protected static string $resource = AcademicSessionReportResource::class;

    public function getTitle(): string
    {
        $studentName = $this->record->student?->name ?? 'طالب';
        $sessionTitle = $this->record->session?->title ?? 'جلسة';

        return "تقرير: {$studentName} - {$sessionTitle}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('recalculate')
                ->label('إعادة الحساب')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('إعادة حساب التقرير')
                ->modalDescription('سيتم إعادة حساب الحضور بناءً على سجلات الاجتماع. هل تريد المتابعة؟')
                ->action(function () {
                    // Recalculate attendance from meeting events
                    $session = $this->record->session;
                    if ($session && $session->meeting) {
                        $attendanceService = app(\App\Services\MeetingAttendanceService::class);
                        $attendanceService->recalculateAttendance($session);
                    }

                    $this->record->update([
                        'manually_evaluated' => false,
                        'override_reason' => null,
                    ]);

                    $this->refreshFormData(['attendance_percentage', 'actual_attendance_minutes', 'attendance_status']);
                })
                ->visible(fn () => $this->record->manually_evaluated),
        ];
    }
}
