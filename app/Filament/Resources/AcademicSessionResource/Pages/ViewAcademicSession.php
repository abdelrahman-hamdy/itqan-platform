<?php

namespace App\Filament\Resources\AcademicSessionResource\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\AcademicSessionResource;
use App\Filament\Shared\Actions\SessionStatusActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewAcademicSession extends ViewRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            SessionStatusActions::startSession(),
            SessionStatusActions::completeSession(),
            SessionStatusActions::cancelSession(role: 'admin'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? 'تفاصيل الجلسة الأكاديمية';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->copyable()
                                    ->weight('bold'),
                                TextEntry::make('title')
                                    ->label('العنوان'),
                                TextEntry::make('session_type')
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
                                TextEntry::make('status')
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

                Section::make('المعلم والطالب')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('academicTeacher.user.id')
                                    ->label('المعلم')
                                    ->formatStateUsing(fn ($record) => $record->academicTeacher?->user
                                            ? trim(($record->academicTeacher->user->first_name ?? '').' '.($record->academicTeacher->user->last_name ?? '')) ?: 'معلم #'.$record->academicTeacher->id
                                            : 'معلم #'.($record->academic_teacher_id ?? '-')
                                    ),
                                TextEntry::make('student.id')
                                    ->label('الطالب')
                                    ->formatStateUsing(fn ($record) => trim(($record->student?->first_name ?? '').' '.($record->student?->last_name ?? '')) ?: null
                                    )
                                    ->placeholder('غير محدد'),
                                TextEntry::make('academicSubscription.subscription_code')
                                    ->label('رمز الاشتراك'),
                            ]),
                    ]),

                Section::make('التوقيت')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->dateTime('Y-m-d H:i'),
                                TextEntry::make('duration_minutes')
                                    ->label('المدة المقررة')
                                    ->suffix(' دقيقة'),
                                TextEntry::make('started_at')
                                    ->label('وقت البدء')
                                    ->dateTime('H:i')
                                    ->placeholder('لم تبدأ'),
                                TextEntry::make('ended_at')
                                    ->label('وقت الانتهاء')
                                    ->dateTime('H:i')
                                    ->placeholder('لم تنته'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('actual_duration_minutes')
                                    ->label('المدة الفعلية')
                                    ->suffix(' دقيقة')
                                    ->placeholder('غير متاح'),
                                TextEntry::make('attendance_status')
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

                Section::make('المحتوى')
                    ->schema([
                        TextEntry::make('description')
                            ->label('وصف الجلسة')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف'),
                        TextEntry::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد محتوى'),
                    ])
                    ->collapsible(),

                Section::make('الواجبات')
                    ->schema([
                        IconEntry::make('homework_assigned')
                            ->label('يوجد واجب')
                            ->boolean(),
                        TextEntry::make('homework_description')
                            ->label('وصف الواجب')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف')
                            ->visible(fn ($record) => $record->homework_assigned ?? false),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->homework_assigned ?? false),

                Section::make('ملاحظات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('session_notes')
                                    ->label('ملاحظات الجلسة')
                                    ->placeholder('لا توجد ملاحظات'),
                                TextEntry::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->placeholder('لا توجد ملاحظات'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('معلومات النظام')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('academy.name')
                                    ->label('الأكاديمية'),
                                TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
