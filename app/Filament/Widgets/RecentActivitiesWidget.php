<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\UserType;
use App\Models\Academy;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivitiesWidget extends BaseWidget
{
    protected static ?string $heading = 'النشاطات الأخيرة';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscoverable = false;

    public static function canView(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        $query = User::query()
            ->with(['academy'])
            ->whereIn('user_type', [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::STUDENT->value, UserType::ADMIN->value, UserType::PARENT->value, UserType::SUPERVISOR->value]);

        // If super admin is NOT in global view mode and has an academy selected, scope to that academy
        if (AcademyContextService::isSuperAdmin() && ! AcademyContextService::isGlobalViewMode()) {
            $currentAcademy = AcademyContextService::getCurrentAcademy();
            if ($currentAcademy) {
                $query->where('academy_id', $currentAcademy->id);
            }
        }
        // If regular user, scope to their academy
        elseif (! AcademyContextService::isSuperAdmin()) {
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
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user_type')
                    ->badge()
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

                TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),

                TextColumn::make('status')
                    ->badge()
                    ->label('الحالة')
                    ->colors([
                        'success' => SessionSubscriptionStatus::ACTIVE->value,
                        'warning' => 'pending',
                        'danger' => 'inactive',
                        'secondary' => 'suspended',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SessionSubscriptionStatus::ACTIVE->value => 'نشط',
                        SessionSubscriptionStatus::PENDING->value => 'في الانتظار',
                        'inactive' => 'غير نشط',
                        'suspended' => 'معلق',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view')
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
