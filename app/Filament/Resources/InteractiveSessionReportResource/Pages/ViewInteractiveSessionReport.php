<?php

namespace App\Filament\Resources\InteractiveSessionReportResource\Pages;

use App\Filament\Resources\InteractiveSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInteractiveSessionReport extends ViewRecord
{
    protected static string $resource = InteractiveSessionReportResource::class;

    public function getTitle(): string
    {
        $studentName = $this->record->student?->name ?? 'طالب';
        $sessionTitle = $this->record->session?->title ?? 'جلسة تفاعلية';
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
                    $session = $this->record->session;
                    if ($session && $session->meeting) {
                        $attendanceService = app(\App\Services\UnifiedAttendanceService::class);
                        $attendanceService->calculateSessionAttendance($session);
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
