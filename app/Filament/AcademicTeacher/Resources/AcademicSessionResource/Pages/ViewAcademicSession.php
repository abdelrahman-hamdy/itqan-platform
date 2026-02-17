<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource;
use Filament\Actions;
use Filament\Infolists;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewAcademicSession extends ViewRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? 'تفاصيل الجلسة الأكاديمية';
    }

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->schema([
                Section::make('معلومات الجلسة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->copyable(),
                                TextEntry::make('title')
                                    ->label('العنوان'),
                                TextEntry::make('student.name')
                                    ->label('الطالب'),
                            ]),
                    ]),

                Section::make('التوقيت والحالة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->dateTime('Y-m-d H:i'),
                                TextEntry::make('duration_minutes')
                                    ->label('المدة')
                                    ->suffix(' دقيقة'),
                                TextEntry::make('status')
                                    ->label('حالة الجلسة')
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
                        Grid::make(2)
                            ->schema([
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
                    ->collapsed(),

                Section::make('الواجب')
                    ->schema([
                        IconEntry::make('homework_assigned')
                            ->label('يوجد واجب')
                            ->boolean(),
                        TextEntry::make('homework_description')
                            ->label('وصف الواجب')
                            ->placeholder('لا يوجد')
                            ->visible(fn ($record) => $record->homework_assigned),
                    ])
                    ->collapsed(),
            ]);
    }
}
