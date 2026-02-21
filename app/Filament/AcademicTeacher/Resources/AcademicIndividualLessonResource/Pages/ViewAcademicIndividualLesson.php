<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages;

use App\Enums\SessionSubscriptionStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource;
use Filament\Actions;
use Filament\Infolists;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewAcademicIndividualLesson extends ViewRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->name ?? 'تفاصيل الدرس الفردي';
    }

    public function infolist(Schema $schema): Schema
    {
        $isSuspended = $this->isSubscriptionSuspended();

        return $schema
            ->schema([
                Section::make('تنبيه - الاشتراك معلق')
                    ->schema([
                        Placeholder::make('subscription_warning')
                            ->label('')
                            ->content('تم تعليق الاشتراك المرتبط بهذا الدرس بسبب عدم الدفع. يرجى تجديد الاشتراك للاستمرار في الخدمة.')
                            ->extraAttributes(['class' => 'text-danger-600 dark:text-danger-400 font-bold text-lg']),
                    ])
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('danger')
                    ->visible($isSuspended),

                Section::make('معلومات الدرس')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('lesson_code')
                                    ->label('رمز الدرس')
                                    ->copyable(),
                                TextEntry::make('name')
                                    ->label('اسم الدرس'),
                                TextEntry::make('student.name')
                                    ->label('الطالب'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('academicSubject.name')
                                    ->label('المادة')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('academicGradeLevel.name')
                                    ->label('المستوى')
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ])
                    ->hidden($isSuspended),

                Section::make('تقدم الجلسات')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_sessions')
                                    ->label('إجمالي الجلسات'),
                                TextEntry::make('sessions_completed')
                                    ->label('الجلسات المكتملة'),
                                TextEntry::make('sessions_remaining')
                                    ->label('الجلسات المتبقية')
                                    ->getStateUsing(fn ($record) => $record->total_sessions - $record->sessions_completed),
                                TextEntry::make('progress_percentage')
                                    ->label('نسبة الإنجاز')
                                    ->suffix('%')
                                    ->badge()
                                    ->color(fn ($state): string => match (true) {
                                        (float) $state >= 80 => 'success',
                                        (float) $state >= 50 => 'warning',
                                        default => 'danger',
                                    }),
                            ]),
                    ])
                    ->hidden($isSuspended),

                Section::make('الوصف والملاحظات')
                    ->schema([
                        TextEntry::make('description')
                            ->label('وصف الدرس')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف'),
                        TextEntry::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull()
                            ->placeholder('لا توجد ملاحظات'),
                    ])
                    ->collapsed()
                    ->hidden($isSuspended),
            ]);
    }

    protected function isSubscriptionSuspended(): bool
    {
        $record = $this->getRecord();
        $subscription = $record->academicSubscription;

        if (! $subscription) {
            return false;
        }

        return $subscription->status === SessionSubscriptionStatus::SUSPENDED;
    }
}
