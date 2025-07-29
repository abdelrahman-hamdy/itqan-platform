<?php

namespace App\Filament\Resources\AcademyResource\Pages;

use App\Filament\Resources\AcademyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAcademies extends ListRecords
{
    protected static string $resource = AcademyResource::class;
    
    protected static ?string $title = 'الأكاديميات';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة أكاديمية جديدة')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('جميع الأكاديميات')
                ->icon('heroicon-m-building-office-2')
                ->badge($this->getModel()::count()),
                
            'active' => Tab::make('النشطة')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge($this->getModel()::where('status', 'active')->count()),
                
            'suspended' => Tab::make('المعلقة')
                ->icon('heroicon-m-pause-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'suspended'))
                ->badge($this->getModel()::where('status', 'suspended')->count()),
                
            'maintenance' => Tab::make('تحت الصيانة')
                ->icon('heroicon-m-wrench-screwdriver')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'maintenance'))
                ->badge($this->getModel()::where('status', 'maintenance')->count()),
        ];
    }
}
