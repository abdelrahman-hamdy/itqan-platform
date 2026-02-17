<?php

namespace App\Filament\Resources\MeetingAttendanceResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\AttendanceCalculationService;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use ValueError;
use App\Models\MeetingAttendance;
use App\Enums\AttendanceStatus;
use App\Filament\Resources\MeetingAttendanceResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property MeetingAttendance $record
 */
class ViewMeetingAttendance extends ViewRecord
{
    protected static string $resource = MeetingAttendanceResource::class;

    public function getTitle(): string
    {
        $userName = $this->record->user?->name ?? 'مستخدم';
        $userType = __("enums.attendance_user_type.{$this->record->user_type}");

        return "سجل حضور: {$userName} ({$userType})";
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('recalculate')
                ->label('إعادة حساب الحضور')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('إعادة حساب الحضور')
                ->modalDescription('سيتم إعادة حساب نسبة الحضور ومدة الحضور بناءً على دورات الدخول والخروج المسجلة.')
                ->action(function () {
                    $session = $this->record->session;
                    if (! $session) {
                        Notification::make()
                            ->title('لا يمكن إعادة الحساب')
                            ->body('لم يتم العثور على الجلسة المرتبطة.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $calculationService = app(AttendanceCalculationService::class);
                    $calculationService->recalculateAttendance($session);

                    $this->record->refresh();

                    Notification::make()
                        ->title('تم إعادة الحساب')
                        ->body('تم إعادة حساب الحضور بنجاح.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->schema([
                Section::make('معلومات الحضور')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('المستخدم'),
                        TextEntry::make('user_type')
                            ->label('نوع المستخدم')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => __("enums.attendance_user_type.{$state}") ?? $state),
                        TextEntry::make('session_type')
                            ->label('نوع الجلسة')
                            ->badge()
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '-';
                                }
                                $key = "enums.session_type.{$state}";
                                $translated = __($key);

                                return $translated !== $key ? $translated : $state;
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'quran', 'individual' => 'primary',
                                'academic' => 'success',
                                'interactive' => 'warning',
                                'group' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('attendance_status')
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
                                } catch (ValueError $e) {
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

                Section::make('تفاصيل التوقيت')
                    ->schema([
                        TextEntry::make('first_join_time')
                            ->label('أول وقت دخول')
                            ->dateTime(),
                        TextEntry::make('last_leave_time')
                            ->label('آخر وقت خروج')
                            ->dateTime(),
                        TextEntry::make('total_duration_minutes')
                            ->label('إجمالي المدة')
                            ->suffix(' دقيقة'),
                        TextEntry::make('attendance_percentage')
                            ->label('نسبة الحضور')
                            ->suffix('%'),
                        TextEntry::make('join_count')
                            ->label('عدد مرات الدخول'),
                        TextEntry::make('leave_count')
                            ->label('عدد مرات الخروج'),
                        TextEntry::make('last_heartbeat_at')
                            ->label('آخر نبض')
                            ->dateTime(),
                    ])->columns(3),

                Section::make('دورات الدخول والخروج')
                    ->schema([
                        TextEntry::make('join_leave_cycles')
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
