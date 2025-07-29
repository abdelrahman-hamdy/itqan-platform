<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    
    protected static ?string $title = 'المستخدمون';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة مستخدم جديد')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(fn () => \App\Models\User::count()),
                
            'super_admins' => Tab::make('مديرو النظام')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'super_admin'))
                ->badge(fn () => \App\Models\User::where('role', 'super_admin')->count()),
                
            'academy_admins' => Tab::make('مديرو الأكاديميات')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'academy_admin'))
                ->badge(fn () => \App\Models\User::where('role', 'academy_admin')->count()),
                
            'teachers' => Tab::make('المعلمون')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'teacher'))
                ->badge(fn () => \App\Models\User::where('role', 'teacher')->count()),
                
            'students' => Tab::make('الطلاب')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'student'))
                ->badge(fn () => \App\Models\User::where('role', 'student')->count()),
                
            'parents' => Tab::make('أولياء الأمور')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'parent'))
                ->badge(fn () => \App\Models\User::where('role', 'parent')->count()),
                
            'pending' => Tab::make('في الانتظار')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\User::where('status', 'pending')->count())
                ->badgeColor('warning'),
                
            'active' => Tab::make('نشط')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(fn () => \App\Models\User::where('status', 'active')->count())
                ->badgeColor('success'),
        ];
    }
} 