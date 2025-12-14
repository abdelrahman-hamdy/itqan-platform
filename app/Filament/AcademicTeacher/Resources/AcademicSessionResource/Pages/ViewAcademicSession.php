<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;

use App\Enums\SessionStatus;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource;
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
                Infolists\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('title')
                                    ->label('العنوان'),
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب'),
                            ]),
                    ]),

                Infolists\Components\Section::make('التوقيت والحالة')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->dateTime('Y-m-d H:i'),
                                Infolists\Components\TextEntry::make('duration_minutes')
                                    ->label('المدة')
                                    ->suffix(' دقيقة'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('حالة الجلسة')
                                    ->badge()
                                    ->formatStateUsing(function ($state): string {
                                        if ($state instanceof SessionStatus) {
                                            return $state->label();
                                        }
                                        $status = SessionStatus::tryFrom($state);
                                        return $status?->label() ?? (string) $state;
                                    })
                                    ->color(fn ($state): string => SessionStatus::tryFrom($state)?->color() ?? 'gray'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('attendance_status')
                                    ->label('حالة الحضور')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'attended' => 'حاضر',
                                        'absent' => 'غائب',
                                        'late' => 'متأخر',
                                        'leaved' => 'غادر مبكراً',
                                        default => 'غير محدد',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'attended' => 'success',
                                        'absent' => 'danger',
                                        'late' => 'warning',
                                        'leaved' => 'info',
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
                    ->collapsed(),

                Infolists\Components\Section::make('الواجب')
                    ->schema([
                        Infolists\Components\IconEntry::make('homework_assigned')
                            ->label('يوجد واجب')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('homework_description')
                            ->label('وصف الواجب')
                            ->placeholder('لا يوجد')
                            ->visible(fn ($record) => $record->homework_assigned),
                    ])
                    ->collapsed(),
            ]);
    }
}
