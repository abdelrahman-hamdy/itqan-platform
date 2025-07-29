<?php

namespace App\Filament\Resources\GradeLevelResource\Pages;

use App\Filament\Resources\GradeLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewGradeLevel extends ViewRecord
{
    protected static string $resource = GradeLevelResource::class;

    public function getTitle(): string
    {
        return 'عرض المرحلة الدراسية: ' . $this->record->name;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل')
                ->icon('heroicon-o-pencil'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات أساسية')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('اسم المرحلة (عربي)')
                                    ->size('lg')
                                    ->weight('bold'),
                                    
                                Infolists\Components\TextEntry::make('name_en')
                                    ->label('اسم المرحلة (إنجليزي)')
                                    ->placeholder('غير محدد'),
                            ]),
                            
                        Infolists\Components\TextEntry::make('description')
                            ->label('الوصف')
                            ->placeholder('لا يوجد وصف')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('تفاصيل المرحلة')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('level')
                                    ->label('ترتيب المرحلة')
                                    ->badge()
                                    ->color('info'),
                                    
                                Infolists\Components\TextEntry::make('min_age')
                                    ->label('الحد الأدنى للعمر')
                                    ->suffix(' سنة')
                                    ->badge()
                                    ->color('warning'),
                                    
                                Infolists\Components\TextEntry::make('max_age')
                                    ->label('الحد الأقصى للعمر')
                                    ->suffix(' سنة')
                                    ->badge()
                                    ->color('warning'),
                                    
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('الحالة')
                                    ->boolean()
                                    ->trueColor('success')
                                    ->falseColor('danger')
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle'),
                            ]),
                    ]),

                Infolists\Components\Section::make('معلومات النظام')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i:s'),
                                    
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
                                    ->dateTime('Y-m-d H:i:s'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
} 