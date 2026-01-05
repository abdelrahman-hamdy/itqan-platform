<?php

namespace App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;

use App\Enums\SessionStatus;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewMonitoredSession extends ViewRecord
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
        $type = $this->getSessionType();

        return [
            Actions\Action::make('add_note')
                ->label('إضافة ملاحظة')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->form([
                    Forms\Components\Textarea::make('supervisor_notes')
                        ->label('ملاحظات المشرف')
                        ->rows(4)
                        ->default(fn () => $this->record->supervisor_notes),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'supervisor_notes' => $data['supervisor_notes'],
                    ]);
                    $this->refreshFormData(['supervisor_notes']);
                }),

            Actions\EditAction::make()
                ->label('تعديل')
                ->color('primary')
                ->url(fn () => route('filament.supervisor.resources.monitored-all-sessions.edit', [
                    'record' => $this->record->id,
                    'type' => $type,
                ])),

            Actions\DeleteAction::make()
                ->label('حذف')
                ->color('danger')
                ->successRedirectUrl(fn () => MonitoredAllSessionsResource::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $type = $this->getSessionType();

        return match ($type) {
            'academic' => $this->getAcademicInfolist($infolist),
            'interactive' => $this->getInteractiveInfolist($infolist),
            default => $this->getQuranInfolist($infolist),
        };
    }

    protected function getQuranInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Infolists\Components\TextEntry::make('session_code')
                            ->label('رمز الجلسة'),

                        Infolists\Components\TextEntry::make('title')
                            ->label('العنوان'),

                        Infolists\Components\TextEntry::make('session_type')
                            ->label('نوع الجلسة')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'individual' => 'فردية',
                                'group' => 'جماعية',
                                'trial' => 'تجريبية',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof SessionStatus ? $state->label() : (SessionStatus::tryFrom($state)?->label() ?? $state)),
                    ])->columns(2),

                Infolists\Components\Section::make('المعلم والحلقة')
                    ->schema([
                        Infolists\Components\TextEntry::make('quranTeacher.name')
                            ->label('المعلم')
                            ->placeholder('غير محدد'),

                        Infolists\Components\TextEntry::make('circle.name_ar')
                            ->label('الحلقة')
                            ->placeholder('جلسة فردية'),

                        Infolists\Components\TextEntry::make('student.name')
                            ->label('الطالب')
                            ->placeholder('جلسة جماعية')
                            ->visible(fn ($record) => in_array($record->session_type, ['individual', 'trial'])),
                    ])->columns(2),

                Infolists\Components\Section::make('التوقيت')
                    ->schema([
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->dateTime('Y-m-d H:i'),

                        Infolists\Components\TextEntry::make('duration_minutes')
                            ->label('المدة')
                            ->suffix(' دقيقة'),

                        Infolists\Components\TextEntry::make('started_at')
                            ->label('وقت البدء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تبدأ'),

                        Infolists\Components\TextEntry::make('ended_at')
                            ->label('وقت الانتهاء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تنتهِ'),
                    ])->columns(2),

                Infolists\Components\Section::make('ملاحظات')
                    ->schema([
                        Infolists\Components\TextEntry::make('session_notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات'),

                        Infolists\Components\TextEntry::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->placeholder('لا توجد ملاحظات')
                            ->color('warning'),
                    ])->columns(2),
            ]);
    }

    protected function getAcademicInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Infolists\Components\TextEntry::make('session_code')
                            ->label('رمز الجلسة'),

                        Infolists\Components\TextEntry::make('title')
                            ->label('العنوان'),

                        Infolists\Components\TextEntry::make('session_type')
                            ->label('نوع الجلسة')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'individual' => 'فردية',
                                'group' => 'جماعية',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof SessionStatus ? $state->label() : (SessionStatus::tryFrom($state)?->label() ?? $state)),
                    ])->columns(2),

                Infolists\Components\Section::make('المعلم والدرس')
                    ->schema([
                        Infolists\Components\TextEntry::make('academicTeacher.user.name')
                            ->label('المعلم')
                            ->placeholder('غير محدد'),

                        Infolists\Components\TextEntry::make('academicIndividualLesson.name')
                            ->label('الدرس')
                            ->placeholder('غير محدد'),

                        Infolists\Components\TextEntry::make('academicIndividualLesson.academicSubject.name')
                            ->label('المادة')
                            ->placeholder('غير محددة'),

                        Infolists\Components\TextEntry::make('student.name')
                            ->label('الطالب')
                            ->placeholder('غير محدد'),
                    ])->columns(2),

                Infolists\Components\Section::make('التوقيت')
                    ->schema([
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->dateTime('Y-m-d H:i'),

                        Infolists\Components\TextEntry::make('duration_minutes')
                            ->label('المدة')
                            ->suffix(' دقيقة'),

                        Infolists\Components\TextEntry::make('started_at')
                            ->label('وقت البدء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تبدأ'),

                        Infolists\Components\TextEntry::make('ended_at')
                            ->label('وقت الانتهاء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تنتهِ'),
                    ])->columns(2),

                Infolists\Components\Section::make('ملاحظات')
                    ->schema([
                        Infolists\Components\TextEntry::make('session_notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات'),

                        Infolists\Components\TextEntry::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->placeholder('لا توجد ملاحظات')
                            ->color('warning'),
                    ])->columns(2),
            ]);
    }

    protected function getInteractiveInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Infolists\Components\TextEntry::make('session_code')
                            ->label('رمز الجلسة'),

                        Infolists\Components\TextEntry::make('title')
                            ->label('العنوان'),

                        Infolists\Components\TextEntry::make('session_number')
                            ->label('رقم الجلسة'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof SessionStatus ? $state->label() : (SessionStatus::tryFrom($state)?->label() ?? $state)),
                    ])->columns(2),

                Infolists\Components\Section::make('الدورة والمعلم')
                    ->schema([
                        Infolists\Components\TextEntry::make('course.title')
                            ->label('الدورة'),

                        Infolists\Components\TextEntry::make('course.assignedTeacher.user.name')
                            ->label('المعلم')
                            ->placeholder('غير محدد'),

                        Infolists\Components\TextEntry::make('course.subject.name')
                            ->label('المادة')
                            ->placeholder('غير محددة'),

                        Infolists\Components\TextEntry::make('attendance_count')
                            ->label('عدد الحضور')
                            ->badge()
                            ->color('info'),
                    ])->columns(2),

                Infolists\Components\Section::make('التوقيت')
                    ->schema([
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->dateTime('Y-m-d H:i'),

                        Infolists\Components\TextEntry::make('duration_minutes')
                            ->label('المدة')
                            ->suffix(' دقيقة'),

                        Infolists\Components\TextEntry::make('started_at')
                            ->label('وقت البدء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تبدأ'),

                        Infolists\Components\TextEntry::make('ended_at')
                            ->label('وقت الانتهاء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تنتهِ'),
                    ])->columns(2),

                Infolists\Components\Section::make('ملاحظات')
                    ->schema([
                        Infolists\Components\TextEntry::make('session_notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات'),

                        Infolists\Components\TextEntry::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->placeholder('لا توجد ملاحظات')
                            ->color('warning'),
                    ])->columns(2),
            ]);
    }
}
