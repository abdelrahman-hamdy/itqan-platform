<?php

namespace App\Filament\Teacher\Resources\QuranCircleResource\Pages;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use App\Enums\WeekDays;
use App\Filament\Teacher\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Infolists;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewQuranCircle extends ViewRecord
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return $this->getRecord()->name ?? 'حلقة قرآن جماعية';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'حلقاتي الجماعية',
            '' => $this->getBreadcrumb(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('معلومات أساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('اسم الحلقة'),
                                TextEntry::make('circle_code')
                                    ->label('رمز الحلقة'),
                                TextEntry::make('age_group')
                                    ->label('الفئة العمرية')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'children' => 'أطفال',
                                        'youth' => 'شباب',
                                        'adults' => 'بالغون',
                                        'all_ages' => 'كل الفئات',
                                        default => $state ?? 'غير محدد',
                                    }),
                                TextEntry::make('gender_type')
                                    ->label('النوع')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'male' => 'رجال',
                                        'female' => 'نساء',
                                        'mixed' => 'مختلط',
                                        default => $state ?? 'غير محدد',
                                    }),
                                TextEntry::make('specialization')
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
                                TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشطة' : 'غير نشطة'),
                            ]),
                    ]),

                Section::make('إعدادات الحلقة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('max_students')
                                    ->label('الحد الأقصى للطلاب'),
                                TextEntry::make('current_students')
                                    ->label('عدد الطلاب الحالي')
                                    ->formatStateUsing(fn ($record) => $record->students()->count()),
                            ]),

                        TextEntry::make('schedule_days')
                            ->label('أيام الانعقاد')
                            ->formatStateUsing(function ($state) {
                                if (! is_array($state) || empty($state)) {
                                    return 'غير محدد';
                                }

                                return WeekDays::getDisplayNames($state);
                            }),

                        TextEntry::make('schedule_time')
                            ->label('الساعة')
                            ->placeholder('غير محدد'),
                    ]),
            ]);
    }
}
