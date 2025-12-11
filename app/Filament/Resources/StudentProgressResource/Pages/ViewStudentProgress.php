<?php

namespace App\Filament\Resources\StudentProgressResource\Pages;

use App\Filament\Resources\StudentProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewStudentProgress extends ViewRecord
{
    protected static string $resource = StudentProgressResource::class;

    public function getTitle(): string
    {
        $userName = $this->record->user?->name ?? 'طالب';
        $courseName = $this->record->recordedCourse?->title ?? 'دورة';
        return "تقدم: {$userName} - {$courseName}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('markComplete')
                ->label('تحديد كمكتمل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'is_completed' => true,
                        'completed_at' => now(),
                        'progress_percentage' => 100,
                    ]);
                })
                ->visible(fn () => !$this->record->is_completed),
            Actions\Action::make('resetProgress')
                ->label('إعادة تعيين التقدم')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('إعادة تعيين التقدم')
                ->modalDescription('سيتم إعادة تعيين جميع بيانات التقدم لهذه الدورة. هل أنت متأكد؟')
                ->action(function () {
                    $this->record->update([
                        'is_completed' => false,
                        'completed_at' => null,
                        'progress_percentage' => 0,
                        'watch_time_seconds' => 0,
                        'current_position_seconds' => 0,
                        'quiz_score' => null,
                        'quiz_attempts' => 0,
                    ]);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('معلومات أساسية')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('الطالب'),
                        Components\TextEntry::make('recordedCourse.title')
                            ->label('الدورة'),
                        Components\TextEntry::make('lesson.title')
                            ->label('الدرس الحالي')
                            ->default('-'),
                        Components\TextEntry::make('progress_type')
                            ->label('نوع التقدم'),
                    ])->columns(4),

                Components\Section::make('إحصائيات التقدم')
                    ->schema([
                        Components\TextEntry::make('progress_percentage')
                            ->label('نسبة الإكمال')
                            ->suffix('%')
                            ->badge()
                            ->color(fn ($state): string => match (true) {
                                $state >= 100 => 'success',
                                $state >= 50 => 'warning',
                                default => 'danger',
                            }),
                        Components\TextEntry::make('watch_time_seconds')
                            ->label('وقت المشاهدة')
                            ->formatStateUsing(function ($state) {
                                $hours = floor($state / 3600);
                                $minutes = floor(($state % 3600) / 60);
                                $seconds = $state % 60;
                                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                            }),
                        Components\TextEntry::make('total_time_seconds')
                            ->label('إجمالي وقت المحتوى')
                            ->formatStateUsing(function ($state) {
                                $hours = floor($state / 3600);
                                $minutes = floor(($state % 3600) / 60);
                                $seconds = $state % 60;
                                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                            }),
                        Components\IconColumn::make('is_completed')
                            ->label('مكتمل')
                            ->boolean(),
                    ])->columns(4),

                Components\Section::make('الاختبارات')
                    ->schema([
                        Components\TextEntry::make('quiz_score')
                            ->label('درجة الاختبار')
                            ->default('لم يتم')
                            ->badge()
                            ->color(fn ($state): string => match (true) {
                                $state === null => 'gray',
                                $state >= 80 => 'success',
                                $state >= 60 => 'warning',
                                default => 'danger',
                            }),
                        Components\TextEntry::make('quiz_attempts')
                            ->label('محاولات الاختبار'),
                    ])->columns(2),

                Components\Section::make('التواريخ')
                    ->schema([
                        Components\TextEntry::make('completed_at')
                            ->label('تاريخ الإكمال')
                            ->dateTime()
                            ->default('-'),
                        Components\TextEntry::make('last_accessed_at')
                            ->label('آخر دخول')
                            ->dateTime()
                            ->default('-'),
                        Components\TextEntry::make('bookmarked_at')
                            ->label('تاريخ الإشارة المرجعية')
                            ->dateTime()
                            ->default('-'),
                        Components\TextEntry::make('created_at')
                            ->label('تاريخ البدء')
                            ->dateTime(),
                    ])->columns(4),

                Components\Section::make('التقييم والملاحظات')
                    ->schema([
                        Components\TextEntry::make('rating')
                            ->label('التقييم')
                            ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) : 'لا يوجد تقييم'),
                        Components\TextEntry::make('review_text')
                            ->label('نص المراجعة')
                            ->default('لا توجد مراجعة')
                            ->columnSpanFull(),
                        Components\TextEntry::make('notes')
                            ->label('ملاحظات')
                            ->default('لا توجد ملاحظات'),
                    ])->columns(2),
            ]);
    }
}
