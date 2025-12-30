<?php

namespace App\Filament\Widgets;

use App\Models\BusinessServiceRequest;
use App\Services\AcademyContextService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\BusinessRequestStatus;

class RecentBusinessRequestsWidget extends BaseWidget
{
    protected static ?string $heading = 'طلبات الخدمات الأخيرة';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BusinessServiceRequest::query()
                    ->with('serviceCategory')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client_phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('serviceCategory.name')
                    ->label('نوع الخدمة')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('project_budget')
                    ->label('الميزانية')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'reviewed',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                        'reviewed' => 'تم المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        SessionStatus::COMPLETED->value => 'مكتمل',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn (BusinessServiceRequest $record): string => \App\Filament\Resources\BusinessServiceRequestResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('mark_reviewed')
                    ->label('مراجعة')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (BusinessServiceRequest $record): bool => $record->status === BusinessRequestStatus::PENDING)
                    ->action(fn (BusinessServiceRequest $record) => $record->update(['status' => BusinessRequestStatus::REVIEWED->value]))
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد المراجعة')
                    ->modalDescription('هل تريد تحديث حالة الطلب إلى "تم المراجعة"؟'),
            ])
            ->emptyStateHeading('لا توجد طلبات خدمات')
            ->emptyStateDescription('سيتم عرض طلبات الخدمات الجديدة هنا')
            ->emptyStateIcon('heroicon-o-document-text')
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->striped();
    }

    protected function getTableHeading(): string
    {
        $pendingCount = BusinessServiceRequest::where('status', BusinessRequestStatus::PENDING->value)->count();

        if ($pendingCount > 0) {
            return "طلبات الخدمات الأخيرة ({$pendingCount} في الانتظار)";
        }

        return 'طلبات الخدمات الأخيرة';
    }

    public static function canView(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }
}
