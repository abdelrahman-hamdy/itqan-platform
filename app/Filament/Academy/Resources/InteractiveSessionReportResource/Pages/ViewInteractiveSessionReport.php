<?php

namespace App\Filament\Academy\Resources\InteractiveSessionReportResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Services\AttendanceCalculationService;
use App\Models\InteractiveSessionReport;
use App\Filament\Academy\Resources\InteractiveSessionReportResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

/**
 * @property InteractiveSessionReport $record
 */
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
            EditAction::make()
                ->label('تعديل'),
            Action::make('recalculate')
                ->label('إعادة الحساب')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('إعادة حساب التقرير')
                ->modalDescription('سيتم إعادة حساب الحضور بناءً على سجلات الاجتماع. هل تريد المتابعة؟')
                ->action(function () {
                    $session = $this->record->session;
                    if ($session && $session->meeting) {
                        $calculationService = app(AttendanceCalculationService::class);
                        $calculationService->recalculateAttendance($session);
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
