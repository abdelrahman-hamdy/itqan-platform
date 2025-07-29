<?php

namespace App\Filament\Widgets;

use App\Models\Academy;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentActivitiesWidget extends BaseWidget
{
    protected static ?string $heading = 'النشاطات الأخيرة';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->with(['academy'])
                    ->whereIn('role', ['teacher', 'student', 'academy_admin'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('role')
                    ->label('الدور')
                    ->colors([
                        'primary' => 'teacher',
                        'success' => 'student',
                        'warning' => 'academy_admin',
                        'danger' => 'parent',
                        'secondary' => 'supervisor',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'teacher' => 'معلم',
                        'student' => 'طالب',
                        'academy_admin' => 'مدير أكاديمية',
                        'parent' => 'ولي أمر',
                        'supervisor' => 'مشرف',
                        'super_admin' => 'مدير النظام',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger' => 'inactive',
                        'secondary' => 'suspended',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'pending' => 'في الانتظار',
                        'inactive' => 'غير نشط',
                        'suspended' => 'معلق',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record): string => "/admin/users/{$record->id}")
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('لا توجد نشاطات أخيرة')
            ->emptyStateDescription('سيتم عرض آخر المستخدمين المسجلين هنا')
            ->emptyStateIcon('heroicon-o-clock')
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
    
    protected function getTableHeading(): string
    {
        return 'آخر 10 مستخدمين مسجلين';
    }
} 