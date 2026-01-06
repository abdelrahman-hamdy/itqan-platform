<?php

namespace App\Filament\Resources\AcademicSessionResource\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Filament\Resources\AcademicSessionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSession extends ViewRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? 'تفاصيل الجلسة الأكاديمية';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->copyable()
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('title')
                                    ->label('العنوان'),
                                Infolists\Components\TextEntry::make('session_type')
                                    ->label('نوع الجلسة')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'individual' => 'فردية',
                                        'group' => 'جماعية',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'individual' => 'primary',
                                        'group' => 'success',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge()
                                    ->formatStateUsing(function ($state): string {
                                        if ($state instanceof SessionStatus) {
                                            return $state->label();
                                        }
                                        $status = SessionStatus::tryFrom($state);

                                        return $status?->label() ?? (string) $state;
                                    })
                                    ->color(function ($state): string {
                                        if ($state instanceof SessionStatus) {
                                            return $state->color();
                                        }

                                        return SessionStatus::tryFrom($state)?->color() ?? 'gray';
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('المعلم والطالب')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('academicTeacher.user.id')
                                    ->label('المعلم')
                                    ->formatStateUsing(fn ($record) => $record->academicTeacher?->user
                                            ? trim(($record->academicTeacher->user->first_name ?? '').' '.($record->academicTeacher->user->last_name ?? '')) ?: 'معلم #'.$record->academicTeacher->id
                                            : 'معلم #'.($record->academic_teacher_id ?? '-')
                                    ),
                                Infolists\Components\TextEntry::make('student.id')
                                    ->label('الطالب')
                                    ->formatStateUsing(fn ($record) => trim(($record->student?->first_name ?? '').' '.($record->student?->last_name ?? '')) ?: null
                                    )
                                    ->placeholder('غير محدد'),
                                Infolists\Components\TextEntry::make('academicSubscription.subscription_code')
                                    ->label('رمز الاشتراك'),
                            ]),
                    ]),

                Infolists\Components\Section::make('التوقيت')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->dateTime('Y-m-d H:i'),
                                Infolists\Components\TextEntry::make('duration_minutes')
                                    ->label('المدة المقررة')
                                    ->suffix(' دقيقة'),
                                Infolists\Components\TextEntry::make('started_at')
                                    ->label('وقت البدء')
                                    ->dateTime('H:i')
                                    ->placeholder('لم تبدأ'),
                                Infolists\Components\TextEntry::make('ended_at')
                                    ->label('وقت الانتهاء')
                                    ->dateTime('H:i')
                                    ->placeholder('لم تنته'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('actual_duration_minutes')
                                    ->label('المدة الفعلية')
                                    ->suffix(' دقيقة')
                                    ->placeholder('غير متاح'),
                                Infolists\Components\TextEntry::make('attendance_status')
                                    ->label('حالة الحضور')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        AttendanceStatus::ATTENDED->value => 'حاضر',
                                        AttendanceStatus::ABSENT->value => 'غائب',
                                        AttendanceStatus::LATE->value => 'متأخر',
                                        AttendanceStatus::LEFT->value => 'غادر مبكراً',
                                        default => 'غير محدد',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        AttendanceStatus::ATTENDED->value => 'success',
                                        AttendanceStatus::ABSENT->value => 'danger',
                                        AttendanceStatus::LATE->value => 'warning',
                                        AttendanceStatus::LEFT->value => 'info',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('المحتوى')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('وصف الجلسة')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف'),
                        Infolists\Components\TextEntry::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد محتوى'),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('الواجبات')
                    ->schema([
                        Infolists\Components\IconEntry::make('homework_assigned')
                            ->label('يوجد واجب')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('homework_description')
                            ->label('وصف الواجب')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف')
                            ->visible(fn ($record) => $record->homework_assigned ?? false),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->homework_assigned ?? false),

                Infolists\Components\Section::make('ملاحظات')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('session_notes')
                                    ->label('ملاحظات الجلسة')
                                    ->placeholder('لا توجد ملاحظات'),
                                Infolists\Components\TextEntry::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->placeholder('لا توجد ملاحظات'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('معلومات النظام')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('academy.name')
                                    ->label('الأكاديمية'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
