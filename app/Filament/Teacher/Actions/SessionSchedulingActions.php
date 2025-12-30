<?php

namespace App\Filament\Teacher\Actions;

use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Services\SessionManagementService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Enums\SubscriptionStatus;

class SessionSchedulingActions
{
    public static function quickScheduleAction(): Action
    {
        return Action::make('quick_schedule')
            ->label('جدولة سريعة')
            ->icon('heroicon-o-calendar-days')
            ->color('primary')
            ->form([
                Forms\Components\Select::make('circle_type')
                    ->label('نوع الحلقة')
                    ->options([
                        'individual' => 'حلقة فردية',
                        'group' => 'حلقة جماعية',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('circle_id', null)),

                Forms\Components\Select::make('circle_id')
                    ->label('اختر الحلقة')
                    ->options(function (Forms\Get $get) {
                        $type = $get('circle_type');
                        if (! $type) {
                            return [];
                        }

                        if ($type === 'individual') {
                            return QuranIndividualCircle::where('quran_teacher_id', Auth::id())
                                ->whereIn('status', [SubscriptionStatus::PENDING->value, SubscriptionStatus::ACTIVE->value])
                                ->with('student')
                                ->get()
                                ->pluck('student.name', 'id')
                                ->toArray();
                        } else {
                            return QuranCircle::where('quran_teacher_id', Auth::id())
                                ->where('status', SubscriptionStatus::ACTIVE->value)
                                ->pluck('name_ar', 'id')
                                ->toArray();
                        }
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        // Auto-update duration when circle is selected
                        $circleType = $get('circle_type');

                        if ($state && $circleType) {
                            if ($circleType === 'individual') {
                                $circle = QuranIndividualCircle::find($state);
                                $defaultDuration = $circle?->subscription?->session_duration_minutes
                                    ?? $circle?->subscription?->package?->session_duration_minutes
                                    ?? 45;
                            } else {
                                $circle = QuranCircle::find($state);
                                $defaultDuration = $circle?->session_duration_minutes ?? 60;
                            }

                            $set('duration_minutes', $defaultDuration);
                        }
                    }),

                Forms\Components\Section::make('تفاصيل الجدولة')
                    ->schema([
                        Forms\Components\Select::make('schedule_pattern')
                            ->label('نمط الجدولة')
                            ->options([
                                'single' => 'جلسة واحدة',
                                'weekly' => 'أسبوعي',
                                'multiple_days' => 'أيام متعددة',
                            ])
                            ->required()
                            ->live()
                            ->default('single'),

                        // Single session fields
                        Forms\Components\DateTimePicker::make('single_datetime')
                            ->label('تاريخ ووقت الجلسة')
                            ->seconds(false)
                            ->minutesStep(15)
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('schedule_pattern') === 'single'),

                        // Weekly pattern fields
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('weekly_day')
                                    ->label('اليوم')
                                    ->options([
                                        'sunday' => 'الأحد',
                                        'monday' => 'الاثنين',
                                        'tuesday' => 'الثلاثاء',
                                        'wednesday' => 'الأربعاء',
                                        'thursday' => 'الخميس',
                                        'friday' => 'الجمعة',
                                        'saturday' => 'السبت',
                                    ])
                                    ->required(),

                                Forms\Components\TimePicker::make('weekly_time')
                                    ->label('الوقت')
                                    ->seconds(false)
                                    ->minutesStep(15)
                                    ->required(),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('schedule_pattern') === 'weekly'),

                        // Multiple days fields
                        Forms\Components\Repeater::make('multiple_slots')
                            ->label('الأوقات المتعددة')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('day')
                                            ->label('اليوم')
                                            ->options([
                                                'sunday' => 'الأحد',
                                                'monday' => 'الاثنين',
                                                'tuesday' => 'الثلاثاء',
                                                'wednesday' => 'الأربعاء',
                                                'thursday' => 'الخميس',
                                                'friday' => 'الجمعة',
                                                'saturday' => 'السبت',
                                            ])
                                            ->required(),

                                        Forms\Components\TimePicker::make('time')
                                            ->label('الوقت')
                                            ->seconds(false)
                                            ->minutesStep(15)
                                            ->required(),
                                    ]),
                            ])
                            ->minItems(1)
                            ->maxItems(7)
                            ->visible(fn (Forms\Get $get) => $get('schedule_pattern') === 'multiple_days'),

                        // Date range for recurring patterns
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('تاريخ البداية')
                                    ->required()
                                    ->default(today())
                                    ->minDate(today()),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->required()
                                    ->after('start_date')
                                    ->default(today()->addMonth()),
                            ])
                            ->visible(fn (Forms\Get $get) => in_array($get('schedule_pattern'), ['weekly', 'multiple_days'])),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('مدة الجلسة (بالدقائق)')
                            ->numeric()
                            ->default(function (Forms\Get $get) {
                                $circleType = $get('circle_type');
                                $circleId = $get('circle_id');

                                if (! $circleType || ! $circleId) {
                                    return 60; // fallback
                                }

                                if ($circleType === 'individual') {
                                    $circle = QuranIndividualCircle::find($circleId);

                                    return $circle?->subscription?->session_duration_minutes
                                        ?? $circle?->subscription?->package?->session_duration_minutes
                                        ?? 45;
                                } else {
                                    $circle = QuranCircle::find($circleId);

                                    return $circle?->session_duration_minutes ?? 60;
                                }
                            })
                            ->minValue(15)
                            ->maxValue(180)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // Auto-update when circle changes
                                $circleType = $get('circle_type');
                                $circleId = $get('circle_id');

                                if ($circleType && $circleId) {
                                    if ($circleType === 'individual') {
                                        $circle = QuranIndividualCircle::find($circleId);
                                        $defaultDuration = $circle?->subscription?->session_duration_minutes
                                            ?? $circle?->subscription?->package?->session_duration_minutes
                                            ?? 45;
                                    } else {
                                        $circle = QuranCircle::find($circleId);
                                        $defaultDuration = $circle?->session_duration_minutes ?? 60;
                                    }

                                    if ($state == 60 || $state == 45) { // Only auto-update if it's still the default
                                        $set('duration_minutes', $defaultDuration);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الجلسة (اختياري)')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الجلسة (اختياري)')
                            ->rows(3)
                            ->maxLength(500),
                    ]),
            ])
            ->action(function (array $data) {
                $service = app(SessionManagementService::class);
                $sessionsCreated = 0;

                try {
                    // Get the circle
                    if ($data['circle_type'] === 'individual') {
                        $circle = QuranIndividualCircle::findOrFail($data['circle_id']);
                    } else {
                        $circle = QuranCircle::findOrFail($data['circle_id']);
                    }

                    $duration = $data['duration_minutes'];
                    $title = $data['title'] ?? null;
                    $description = $data['description'] ?? null;

                    switch ($data['schedule_pattern']) {
                        case 'single':
                            $scheduledAt = Carbon::parse($data['single_datetime']);
                            if ($data['circle_type'] === 'individual') {
                                $service->createIndividualSession($circle, $scheduledAt, $duration, $title, $description);
                            } else {
                                $service->createGroupSession($circle, $scheduledAt, $duration, $title, $description);
                            }
                            $sessionsCreated = 1;
                            break;

                        case 'weekly':
                            $timeSlots = [['day' => $data['weekly_day'], 'time' => $data['weekly_time']]];
                            $sessions = $service->bulkCreateSessions(
                                $circle,
                                $timeSlots,
                                Carbon::parse($data['start_date']),
                                Carbon::parse($data['end_date']),
                                $duration
                            );
                            $sessionsCreated = $sessions->count();
                            break;

                        case 'multiple_days':
                            $sessions = $service->bulkCreateSessions(
                                $circle,
                                $data['multiple_slots'],
                                Carbon::parse($data['start_date']),
                                Carbon::parse($data['end_date']),
                                $duration
                            );
                            $sessionsCreated = $sessions->count();
                            break;
                    }

                    Notification::make()
                        ->title('تم إنشاء الجلسات بنجاح')
                        ->body("تم إنشاء {$sessionsCreated} جلسة")
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('خطأ في إنشاء الجلسات')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function resetCircleSessionsAction(): Action
    {
        return Action::make('reset_sessions')
            ->label('إعادة تعيين الجلسات')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('إعادة تعيين جلسات الحلقة')
            ->modalDescription('هل أنت متأكد من حذف جميع الجلسات المجدولة لهذه الحلقة؟ لن يمكن التراجع عن هذا الإجراء.')
            ->form([
                Forms\Components\Select::make('circle_type')
                    ->label('نوع الحلقة')
                    ->options([
                        'individual' => 'حلقة فردية',
                        'group' => 'حلقة جماعية',
                    ])
                    ->required()
                    ->live(),

                Forms\Components\Select::make('circle_id')
                    ->label('اختر الحلقة')
                    ->options(function (Forms\Get $get) {
                        $type = $get('circle_type');
                        if (! $type) {
                            return [];
                        }

                        if ($type === 'individual') {
                            return QuranIndividualCircle::where('quran_teacher_id', Auth::id())
                                ->whereIn('status', [SubscriptionStatus::PENDING->value, SubscriptionStatus::ACTIVE->value])
                                ->with('student')
                                ->get()
                                ->pluck('student.name', 'id')
                                ->toArray();
                        } else {
                            return QuranCircle::where('quran_teacher_id', Auth::id())
                                ->where('status', SubscriptionStatus::ACTIVE->value)
                                ->pluck('name', 'id')
                                ->toArray();
                        }
                    })
                    ->required()
                    ->searchable(),
            ])
            ->action(function (array $data) {
                $service = app(SessionManagementService::class);

                try {
                    if ($data['circle_type'] === 'individual') {
                        $circle = QuranIndividualCircle::findOrFail($data['circle_id']);
                    } else {
                        $circle = QuranCircle::findOrFail($data['circle_id']);
                    }

                    $deletedCount = $service->resetCircleSessions($circle);

                    Notification::make()
                        ->title('تم حذف الجلسات بنجاح')
                        ->body("تم حذف {$deletedCount} جلسة")
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('خطأ في حذف الجلسات')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function copyScheduleAction(): Action
    {
        return Action::make('copy_schedule')
            ->label('نسخ جدول جلسات')
            ->icon('heroicon-o-document-duplicate')
            ->color('info')
            ->form([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Section::make('نسخ من')
                            ->schema([
                                Forms\Components\Select::make('source_circle_type')
                                    ->label('نوع الحلقة المصدر')
                                    ->options([
                                        'individual' => 'حلقة فردية',
                                        'group' => 'حلقة جماعية',
                                    ])
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('source_circle_id')
                                    ->label('الحلقة المصدر')
                                    ->options(function (Forms\Get $get) {
                                        $type = $get('source_circle_type');
                                        if (! $type) {
                                            return [];
                                        }

                                        if ($type === 'individual') {
                                            return QuranIndividualCircle::where('quran_teacher_id', Auth::id())
                                                ->whereIn('status', [SubscriptionStatus::PENDING->value, SubscriptionStatus::ACTIVE->value])
                                                ->with('student')
                                                ->get()
                                                ->pluck('student.name', 'id')
                                                ->toArray();
                                        } else {
                                            return QuranCircle::where('quran_teacher_id', Auth::id())
                                                ->where('status', SubscriptionStatus::ACTIVE->value)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        }
                                    })
                                    ->required()
                                    ->searchable(),
                            ]),

                        Forms\Components\Section::make('نسخ إلى')
                            ->schema([
                                Forms\Components\Select::make('target_circle_type')
                                    ->label('نوع الحلقة الهدف')
                                    ->options([
                                        'individual' => 'حلقة فردية',
                                        'group' => 'حلقة جماعية',
                                    ])
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('target_circle_id')
                                    ->label('الحلقة الهدف')
                                    ->options(function (Forms\Get $get) {
                                        $type = $get('target_circle_type');
                                        if (! $type) {
                                            return [];
                                        }

                                        if ($type === 'individual') {
                                            return QuranIndividualCircle::where('quran_teacher_id', Auth::id())
                                                ->whereIn('status', [SubscriptionStatus::PENDING->value, SubscriptionStatus::ACTIVE->value])
                                                ->with('student')
                                                ->get()
                                                ->pluck('student.name', 'id')
                                                ->toArray();
                                        } else {
                                            return QuranCircle::where('quran_teacher_id', Auth::id())
                                                ->where('status', SubscriptionStatus::ACTIVE->value)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        }
                                    })
                                    ->required()
                                    ->searchable(),
                            ]),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البداية للنسخ')
                            ->required()
                            ->default(today())
                            ->minDate(today()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ النهاية للنسخ')
                            ->required()
                            ->after('start_date')
                            ->default(today()->addWeeks(4)),
                    ]),
            ])
            ->action(function (array $data) {
                // Implementation for copying schedule patterns
                Notification::make()
                    ->title('قريباً')
                    ->body('هذه الميزة قيد التطوير')
                    ->info()
                    ->send();
            });
    }
}
