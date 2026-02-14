<?php

namespace App\Filament\Resources\MeetingAttendanceResource\Pages;

use App\Enums\AttendanceStatus;
use App\Filament\Resources\MeetingAttendanceResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property \App\Models\MeetingAttendance $record
 */
class ViewMeetingAttendance extends ViewRecord
{
    protected static string $resource = MeetingAttendanceResource::class;

    public function getTitle(): string
    {
        $userName = $this->record->user?->name ?? 'مستخدم';
        $userType = $this->record->user_type === 'teacher' ? 'معلم' : 'طالب';

        return "سجل حضور: {$userName} ({$userType})";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('recalculate')
                ->label('إعادة حساب الحضور')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('إعادة حساب الحضور')
                ->modalDescription('سيتم إعادة حساب نسبة الحضور ومدة الحضور بناءً على دورات الدخول والخروج المسجلة.')
                ->action(function () {
                    $calculationService = app(\App\Services\AttendanceCalculationService::class);
                    $calculationService->recalculateAttendance($this->record);

                    $this->refreshFormData([
                        'attendance_percentage',
                        'total_duration_minutes',
                        'attendance_status',
                        'is_calculated',
                        'attendance_calculated_at',
                    ]);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('معلومات الحضور')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('المستخدم'),
                        Components\TextEntry::make('user_type')
                            ->label('نوع المستخدم')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'student' => 'طالب',
                                'teacher' => 'معلم',
                                default => $state,
                            }),
                        Components\TextEntry::make('session_type')
                            ->label('نوع الجلسة')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'quran' => 'قرآن',
                                'academic' => 'أكاديمي',
                                'interactive' => 'تفاعلي',
                                default => $state ?? '-',
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'quran' => 'primary',
                                'academic' => 'success',
                                'interactive' => 'warning',
                                default => 'gray',
                            }),
                        Components\TextEntry::make('attendance_status')
                            ->label('حالة الحضور')
                            ->badge()
                            ->formatStateUsing(function (mixed $state): string {
                                if (! $state) {
                                    return '-';
                                }
                                if ($state instanceof AttendanceStatus) {
                                    return $state->label();
                                }
                                try {
                                    return AttendanceStatus::from($state)->label();
                                } catch (\ValueError $e) {
                                    return (string) $state;
                                }
                            })
                            ->color(fn (mixed $state): string => match (true) {
                                $state === AttendanceStatus::ATTENDED, $state === AttendanceStatus::ATTENDED->value => 'success',
                                $state === AttendanceStatus::LATE, $state === AttendanceStatus::LATE->value => 'warning',
                                $state === AttendanceStatus::LEFT, $state === AttendanceStatus::LEFT->value => 'info',
                                $state === AttendanceStatus::ABSENT, $state === AttendanceStatus::ABSENT->value => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(4),

                Components\Section::make('تفاصيل التوقيت')
                    ->schema([
                        Components\TextEntry::make('first_join_time')
                            ->label('أول وقت دخول')
                            ->dateTime(),
                        Components\TextEntry::make('last_leave_time')
                            ->label('آخر وقت خروج')
                            ->dateTime(),
                        Components\TextEntry::make('total_duration_minutes')
                            ->label('إجمالي المدة')
                            ->suffix(' دقيقة'),
                        Components\TextEntry::make('attendance_percentage')
                            ->label('نسبة الحضور')
                            ->suffix('%'),
                        Components\TextEntry::make('join_count')
                            ->label('عدد مرات الدخول'),
                        Components\TextEntry::make('leave_count')
                            ->label('عدد مرات الخروج'),
                        Components\TextEntry::make('last_heartbeat_at')
                            ->label('آخر نبض')
                            ->dateTime(),
                    ])->columns(3),

                Components\Section::make('دورات الدخول والخروج')
                    ->schema([
                        Components\TextEntry::make('join_leave_cycles')
                            ->label('السجل')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return 'لا توجد سجلات';
                                }

                                $cycles = is_string($state) ? json_decode($state, true) : $state;
                                if (! is_array($cycles)) {
                                    return 'تنسيق غير صالح';
                                }

                                $html = '<ul class="list-disc pr-4 space-y-1">';
                                foreach ($cycles as $cycle) {
                                    $join = $cycle['joined_at'] ?? '-';
                                    $leave = $cycle['left_at'] ?? 'جارٍ';
                                    $duration = $cycle['duration_minutes'] ?? '-';
                                    $html .= "<li>الدخول: {$join} | الخروج: {$leave} | المدة: {$duration} دقيقة</li>";
                                }
                                $html .= '</ul>';

                                return $html;
                            })
                            ->html(),
                    ])
                    ->visible(fn () => ! empty($this->record->join_leave_cycles)),
            ]);
    }
}
