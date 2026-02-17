<?php

namespace App\Filament\Resources\StudentProgressResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Illuminate\Support\HtmlString;
use App\Models\CourseSubscription;
use App\Filament\Resources\StudentProgressResource;
use Filament\Actions;
use Filament\Infolists\Components;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

/**
 * @property CourseSubscription $record
 */
class ViewStudentProgress extends ViewRecord
{
    protected static string $resource = StudentProgressResource::class;

    public function getTitle(): string
    {
        $studentName = $this->record->student?->name ?? 'طالب';
        $courseName = $this->record->recordedCourse?->title ?? 'دورة';

        return "تقدم: {$studentName} - {$courseName}";
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),

            Action::make('markComplete')
                ->label('تحديد كمكتمل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تحديد كمكتمل')
                ->modalDescription('سيتم تحديد هذه الدورة كمكتملة بنسبة 100%. هل أنت متأكد؟')
                ->action(fn () => $this->record->markAsCompleted())
                ->visible(fn () => ! $this->record->isCompleted()),

            Action::make('issueCertificate')
                ->label('إصدار شهادة')
                ->icon('heroicon-o-academic-cap')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('إصدار شهادة')
                ->modalDescription('سيتم إصدار شهادة إتمام الدورة لهذا الطالب. هل أنت متأكد؟')
                ->action(fn () => $this->record->issueCertificateForCourse())
                ->visible(fn () => $this->record->can_earn_certificate),

            Action::make('recalculateProgress')
                ->label('إعادة حساب التقدم')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('إعادة حساب التقدم')
                ->modalDescription('سيتم إعادة حساب نسبة التقدم من سجلات الدروس. هل أنت متأكد؟')
                ->action(fn () => $this->record->updateRecordedCourseProgress()),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Section 1: Basic Info
                Section::make('معلومات أساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('student.name')
                                    ->label('الطالب')
                                    ->icon('heroicon-o-user')
                                    ->weight('bold'),

                                TextEntry::make('recordedCourse.title')
                                    ->label('الدورة')
                                    ->icon('heroicon-o-academic-cap'),
                            ]),

                        TextEntry::make('access_status')
                            ->label('حالة الوصول')
                            ->badge()
                            ->color('info'),
                    ]),

                // Section 2: Progress Statistics
                Section::make('إحصائيات التقدم')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('progress_percentage')
                                    ->label('نسبة الإكمال')
                                    ->suffix('%')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state): string => match (true) {
                                        $state >= 100 => 'success',
                                        $state >= 50 => 'warning',
                                        $state > 0 => 'info',
                                        default => 'gray',
                                    }),

                                TextEntry::make('lessons_count')
                                    ->label('الدروس المكتملة')
                                    ->getStateUsing(fn ($record) => "{$record->completed_lessons} / {$record->total_lessons}")
                                    ->icon('heroicon-o-book-open'),

                                IconEntry::make('certificate_issued')
                                    ->label('شهادة صادرة')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-academic-cap')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                            ]),

                        // Progress bar visualization
                        TextEntry::make('progress_percentage')
                            ->label('شريط التقدم')
                            ->formatStateUsing(function ($state) {
                                $percentage = (int) $state;
                                $color = match (true) {
                                    $percentage >= 100 => 'bg-green-500',
                                    $percentage >= 50 => 'bg-yellow-500',
                                    $percentage > 0 => 'bg-blue-500',
                                    default => 'bg-gray-300',
                                };

                                return new HtmlString(
                                    "<div class='w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700'>
                                        <div class='{$color} h-4 rounded-full transition-all duration-500' style='width: {$percentage}%'></div>
                                    </div>
                                    <span class='text-sm text-gray-500 mt-1'>{$percentage}% مكتمل</span>"
                                );
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),

                // Section 3: Dates
                Section::make('التواريخ')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('تاريخ التسجيل')
                                    ->dateTime('Y-m-d H:i')
                                    ->icon('heroicon-o-calendar'),

                                TextEntry::make('last_accessed_at')
                                    ->label('آخر دخول')
                                    ->since()
                                    ->icon('heroicon-o-clock')
                                    ->placeholder('لم يدخل بعد'),

                                TextEntry::make('completion_date')
                                    ->label('تاريخ الإكمال')
                                    ->dateTime('Y-m-d H:i')
                                    ->icon('heroicon-o-check-badge')
                                    ->placeholder('لم يكتمل بعد'),

                                TextEntry::make('ends_at')
                                    ->label('تاريخ انتهاء الوصول')
                                    ->dateTime('Y-m-d')
                                    ->icon('heroicon-o-calendar-days')
                                    ->placeholder('وصول مدى الحياة'),
                            ]),
                    ])
                    ->collapsible(),

                // Section 4: Course Details
                Section::make('تفاصيل الدورة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('recordedCourse.instructor.name')
                                    ->label('المدرب')
                                    ->icon('heroicon-o-user-circle')
                                    ->placeholder('غير محدد'),

                                TextEntry::make('total_duration_formatted')
                                    ->label('مدة الدورة الكلية')
                                    ->icon('heroicon-o-clock'),

                                TextEntry::make('recordedCourse.level')
                                    ->label('مستوى الدورة')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'beginner' => 'مبتدئ',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                        default => $state,
                                    }),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
