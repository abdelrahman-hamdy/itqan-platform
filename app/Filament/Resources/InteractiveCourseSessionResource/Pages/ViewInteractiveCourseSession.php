<?php

namespace App\Filament\Resources\InteractiveCourseSessionResource\Pages;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use App\Enums\SessionStatus;
use App\Filament\Resources\InteractiveCourseSessionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Resources\Pages\ViewRecord;

class ViewInteractiveCourseSession extends ViewRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? 'تفاصيل جلسة الدورة التفاعلية';
    }

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->schema([
                Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->copyable()
                                    ->weight('bold'),
                                TextEntry::make('session_number')
                                    ->label('رقم الجلسة'),
                                TextEntry::make('title')
                                    ->label('العنوان'),
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

                Section::make('الدورة والمعلم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('course.title')
                                    ->label('الدورة'),
                                TextEntry::make('course.assignedTeacher.user.name')
                                    ->label('المعلم'),
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
                                TextEntry::make('attendance_count')
                                    ->label('عدد الحضور'),
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
                            ->visible(fn ($record) => $record->homework_assigned),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->homework_assigned),

                Section::make('معلومات النظام')
                    ->schema([
                        Grid::make(2)
                            ->schema([
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
