<?php

namespace App\Filament\Teacher\Resources\QuranCircleResource\Pages;

use App\Enums\WeekDays;
use App\Filament\Teacher\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranCircle extends ViewRecord
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return $this->getRecord()->name_ar ?? 'حلقة قرآن جماعية';
    }

    public function getBreadcrumbs(): array
    {
        $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';

        $breadcrumbs = [
            route('teacher.profile', ['subdomain' => $subdomain]) => 'ملفي الشخصي',
        ];

        // Add parent breadcrumbs
        $parentBreadcrumbs = parent::getBreadcrumbs();

        // Skip the first item (dashboard) and use our custom profile link instead
        $filteredBreadcrumbs = array_slice($parentBreadcrumbs, 1, null, true);

        return array_merge($breadcrumbs, $filteredBreadcrumbs);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات أساسية')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name_ar')
                                    ->label('اسم الحلقة (عربي)'),
                                Infolists\Components\TextEntry::make('circle_code')
                                    ->label('رمز الحلقة'),
                                Infolists\Components\TextEntry::make('age_group')
                                    ->label('الفئة العمرية')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'children' => 'أطفال',
                                        'teenagers' => 'مراهقون',
                                        'adults' => 'بالغون',
                                        'mixed' => 'مختلطة',
                                        default => $state ?? 'غير محدد',
                                    }),
                                Infolists\Components\TextEntry::make('gender_type')
                                    ->label('النوع')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'male' => 'رجال',
                                        'female' => 'نساء',
                                        'mixed' => 'مختلط',
                                        default => $state ?? 'غير محدد',
                                    }),
                                Infolists\Components\TextEntry::make('specialization')
                                    ->label('التخصص')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'memorization' => 'حفظ',
                                        'recitation' => 'تلاوة',
                                        'interpretation' => 'تفسير',
                                        'arabic_language' => 'لغة عربية',
                                        'complete' => 'شامل',
                                        default => $state ?? 'غير محدد',
                                    }),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشطة' : 'غير نشطة'),
                            ]),
                    ]),

                Infolists\Components\Section::make('إعدادات الحلقة')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('max_students')
                                    ->label('الحد الأقصى للطلاب'),
                                Infolists\Components\TextEntry::make('current_students')
                                    ->label('عدد الطلاب الحالي')
                                    ->formatStateUsing(fn ($record) => $record->students()->count()),
                                Infolists\Components\TextEntry::make('session_duration_minutes')
                                    ->label('مدة الجلسة')
                                    ->suffix(' دقيقة'),
                            ]),

                        Infolists\Components\TextEntry::make('schedule_days')
                            ->label('أيام الانعقاد')
                            ->formatStateUsing(function ($state) {
                                if (! is_array($state) || empty($state)) {
                                    return 'غير محدد';
                                }

                                return WeekDays::getDisplayNames($state);
                            }),

                        Infolists\Components\TextEntry::make('schedule_time')
                            ->label('الساعة')
                            ->placeholder('غير محدد'),
                    ]),
            ]);
    }
}
