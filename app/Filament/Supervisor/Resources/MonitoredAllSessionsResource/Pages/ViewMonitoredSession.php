<?php

namespace App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Enums\SessionStatus;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * @property QuranSession|AcademicSession|InteractiveCourseSession $record
 */
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
            Action::make('add_note')
                ->label('إضافة ملاحظة')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->schema([
                    Textarea::make('supervisor_notes')
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

            EditAction::make()
                ->label('تعديل')
                ->color('primary')
                ->url(fn () => route('filament.supervisor.resources.monitored-all-sessions.edit', [
                    'record' => $this->record->id,
                    'type' => $type,
                ])),

            DeleteAction::make()
                ->label('حذف')
                ->color('danger')
                ->successRedirectUrl(fn () => MonitoredAllSessionsResource::getUrl('index')),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $type = $this->getSessionType();

        return match ($type) {
            'academic' => $this->getAcademicInfolist($infolist),
            'interactive' => $this->getInteractiveInfolist($infolist),
            default => $this->getQuranInfolist($infolist),
        };
    }

    protected function getQuranInfolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الجلسة')
                    ->schema([
                        TextEntry::make('session_code')
                            ->label('رمز الجلسة'),

                        TextEntry::make('title')
                            ->label('العنوان'),

                        TextEntry::make('session_type')
                            ->label('نوع الجلسة')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'individual' => 'فردية',
                                'group' => 'جماعية',
                                'trial' => 'تجريبية',
                                default => $state,
                            }),

                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof SessionStatus ? $state->label() : (SessionStatus::tryFrom($state)?->label() ?? $state)),
                    ])->columns(2),

                Section::make('المعلم والحلقة')
                    ->schema([
                        TextEntry::make('quranTeacher.name')
                            ->label('المعلم')
                            ->placeholder('غير محدد'),

                        TextEntry::make('circle.name')
                            ->label('الحلقة')
                            ->placeholder('جلسة فردية'),

                        TextEntry::make('student.name')
                            ->label('الطالب')
                            ->placeholder('جلسة جماعية')
                            ->visible(fn ($record) => in_array($record->session_type, ['individual', 'trial'])),
                    ])->columns(2),

                Section::make('التوقيت')
                    ->schema([
                        TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->dateTime('Y-m-d H:i'),

                        TextEntry::make('duration_minutes')
                            ->label('المدة')
                            ->suffix(' دقيقة'),

                        TextEntry::make('started_at')
                            ->label('وقت البدء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تبدأ'),

                        TextEntry::make('ended_at')
                            ->label('وقت الانتهاء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تنتهِ'),
                    ])->columns(2),

                Section::make('ملاحظات')
                    ->schema([
                        TextEntry::make('session_notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات'),

                        TextEntry::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->placeholder('لا توجد ملاحظات')
                            ->color('warning'),
                    ])->columns(2),
            ]);
    }

    protected function getAcademicInfolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الجلسة')
                    ->schema([
                        TextEntry::make('session_code')
                            ->label('رمز الجلسة'),

                        TextEntry::make('title')
                            ->label('العنوان'),

                        TextEntry::make('session_type')
                            ->label('نوع الجلسة')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'individual' => 'فردية',
                                'group' => 'جماعية',
                                default => $state,
                            }),

                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof SessionStatus ? $state->label() : (SessionStatus::tryFrom($state)?->label() ?? $state)),
                    ])->columns(2),

                Section::make('المعلم والدرس')
                    ->schema([
                        TextEntry::make('academicTeacher.user.name')
                            ->label('المعلم')
                            ->placeholder('غير محدد'),

                        TextEntry::make('academicIndividualLesson.name')
                            ->label('الدرس')
                            ->placeholder('غير محدد'),

                        TextEntry::make('academicIndividualLesson.academicSubject.name')
                            ->label('المادة')
                            ->placeholder('غير محددة'),

                        TextEntry::make('student.name')
                            ->label('الطالب')
                            ->placeholder('غير محدد'),
                    ])->columns(2),

                Section::make('التوقيت')
                    ->schema([
                        TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->dateTime('Y-m-d H:i'),

                        TextEntry::make('duration_minutes')
                            ->label('المدة')
                            ->suffix(' دقيقة'),

                        TextEntry::make('started_at')
                            ->label('وقت البدء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تبدأ'),

                        TextEntry::make('ended_at')
                            ->label('وقت الانتهاء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تنتهِ'),
                    ])->columns(2),

                Section::make('ملاحظات')
                    ->schema([
                        TextEntry::make('session_notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات'),

                        TextEntry::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->placeholder('لا توجد ملاحظات')
                            ->color('warning'),
                    ])->columns(2),
            ]);
    }

    protected function getInteractiveInfolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الجلسة')
                    ->schema([
                        TextEntry::make('session_code')
                            ->label('رمز الجلسة'),

                        TextEntry::make('title')
                            ->label('العنوان'),

                        TextEntry::make('session_number')
                            ->label('رقم الجلسة'),

                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof SessionStatus ? $state->label() : (SessionStatus::tryFrom($state)?->label() ?? $state)),
                    ])->columns(2),

                Section::make('الدورة والمعلم')
                    ->schema([
                        TextEntry::make('course.title')
                            ->label('الدورة'),

                        TextEntry::make('course.assignedTeacher.user.name')
                            ->label('المعلم')
                            ->placeholder('غير محدد'),

                        TextEntry::make('course.subject.name')
                            ->label('المادة')
                            ->placeholder('غير محددة'),

                        TextEntry::make('attendance_count')
                            ->label('عدد الحضور')
                            ->badge()
                            ->color('info'),
                    ])->columns(2),

                Section::make('التوقيت')
                    ->schema([
                        TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->dateTime('Y-m-d H:i'),

                        TextEntry::make('duration_minutes')
                            ->label('المدة')
                            ->suffix(' دقيقة'),

                        TextEntry::make('started_at')
                            ->label('وقت البدء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تبدأ'),

                        TextEntry::make('ended_at')
                            ->label('وقت الانتهاء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم تنتهِ'),
                    ])->columns(2),

                Section::make('ملاحظات')
                    ->schema([
                        TextEntry::make('session_notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات'),

                        TextEntry::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->placeholder('لا توجد ملاحظات')
                            ->color('warning'),
                    ])->columns(2),
            ]);
    }
}
