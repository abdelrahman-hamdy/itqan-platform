<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\Pages;

use App\Filament\Academy\Resources\RecordedCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Grid;

class ViewRecordedCourse extends ViewRecord
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل الدورة'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('course_code')
                                    ->label('رمز الدورة'),
                                
                                TextEntry::make('title')
                                    ->label('عنوان الدورة'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('instructor.full_name')
                                    ->label('المدرب'),
                                
                                TextEntry::make('subject.name')
                                    ->label('المادة الدراسية'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('gradeLevel.name')
                                    ->label('المستوى الدراسي'),
                                
                                TextEntry::make('level')
                                    ->label('مستوى الدورة')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'beginner' => 'success',
                                        'intermediate' => 'warning',
                                        'advanced' => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'beginner' => 'مبتدئ',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                    }),
                            ]),

                        TextEntry::make('description')
                            ->label('وصف الدورة')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('الوسائط')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                ImageEntry::make('thumbnail_url')
                                    ->label('صورة مصغرة')
                                    ->circular()
                                    ->size(200),

                                TextEntry::make('trailer_video_url')
                                    ->label('فيديو تعريفي')
                                    ->url(fn ($record) => $record->trailer_video_url)
                                    ->openUrlInNewTab(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('التسعير')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                IconEntry::make('is_free')
                                    ->label('دورة مجانية')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle'),

                                TextEntry::make('price')
                                    ->label('السعر')
                                    ->money('USD')
                                    ->visible(fn ($record) => !$record->is_free),
                            ]),

                        TextEntry::make('currency')
                            ->label('العملة'),
                    ])
                    ->collapsible(),

                Section::make('محتوى الدورة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_sections')
                                    ->label('عدد الأقسام'),

                                TextEntry::make('total_lessons')
                                    ->label('عدد الدروس'),

                                TextEntry::make('duration_hours')
                                    ->label('المدة بالساعات'),
                            ]),

                        KeyValueEntry::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->columnSpanFull(),

                        KeyValueEntry::make('learning_outcomes')
                            ->label('نتائج التعلم')
                            ->columnSpanFull(),

                        KeyValueEntry::make('course_materials')
                            ->label('المواد التعليمية')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('الإحصائيات')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_enrollments')
                                    ->label('إجمالي التسجيلات')
                                    ->numeric(),

                                TextEntry::make('avg_rating')
                                    ->label('متوسط التقييم')
                                    ->numeric(
                                        decimalPlaces: 1,
                                        decimalSeparator: '.',
                                        thousandsSeparator: ',',
                                    ),

                                TextEntry::make('total_reviews')
                                    ->label('عدد التقييمات')
                                    ->numeric(),

                                TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('الإعدادات')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                IconEntry::make('is_published')
                                    ->label('منشور')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle'),

                                IconEntry::make('is_featured')
                                    ->label('مميزة')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-star')
                                    ->falseIcon('heroicon-o-star'),

                                IconEntry::make('completion_certificate')
                                    ->label('شهادة إتمام')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle'),
                            ]),

                        TextEntry::make('tags')
                            ->label('العلامات')
                            ->listWithLineBreaks()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('الملاحظات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->placeholder('لا توجد ملاحظات'),

                                TextEntry::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->placeholder('لا توجد ملاحظات'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
} 