<?php

namespace App\Filament\Widgets;

use App\Models\Academy;
use App\Models\User;
use App\Services\AcademyContextService;
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
        $query = User::query()
            ->with(['academy'])
            ->whereIn('user_type', ['quran_teacher', 'academic_teacher', 'student', 'admin', 'parent', 'supervisor']);

        // If super admin is NOT in global view mode and has an academy selected, scope to that academy
        if (AcademyContextService::isSuperAdmin() && !AcademyContextService::isGlobalViewMode()) {
            $currentAcademy = AcademyContextService::getCurrentAcademy();
            if ($currentAcademy) {
                $query->where('academy_id', $currentAcademy->id);
            }
        }
        // If regular user, scope to their academy
        elseif (!AcademyContextService::isSuperAdmin()) {
            $currentAcademy = AcademyContextService::getCurrentAcademy();
            if ($currentAcademy) {
                $query->where('academy_id', $currentAcademy->id);
            }
        }
        // If super admin in global view mode, show all users (no academy filter)

        return $table
            ->query(
                $query->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('user_type')
                    ->label('نوع المستخدم')
                    ->colors([
                        'primary' => 'quran_teacher',
                        'info' => 'academic_teacher',
                        'success' => 'student',
                        'warning' => 'admin',
                        'danger' => 'parent',
                        'secondary' => 'supervisor',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'quran_teacher' => 'معلم قرآن',
                        'academic_teacher' => 'معلم أكاديمي',
                        'student' => 'طالب',
                        'admin' => 'مدير',
                        'parent' => 'ولي أمر',
                        'supervisor' => 'مشرف',
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
        if (AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()) {
            return 'آخر 10 مستخدمين مسجلين (جميع الأكاديميات)';
        } elseif (AcademyContextService::isSuperAdmin()) {
            $currentAcademy = AcademyContextService::getCurrentAcademy();
            return $currentAcademy ? "آخر 10 مستخدمين مسجلين ({$currentAcademy->name})" : 'آخر 10 مستخدمين مسجلين';
        } else {
            return 'آخر 10 مستخدمين مسجلين';
        }
    }
} 