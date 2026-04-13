<?php

namespace App\Filament\Shared\RelationManagers;

use App\Models\SubscriptionCycle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only relation manager that displays a subscription thread's full
 * cycle history — archived, active, and queued cycles.
 *
 * Shared between QuranSubscriptionResource and AcademicSubscriptionResource
 * (Admin and Academy panels) since the display is identical.
 *
 * No create/edit/delete actions: cycles are managed exclusively by
 * SubscriptionRenewalService, SubscriptionMaintenanceService, and the
 * `subscriptions:advance-cycles` scheduled command.
 */
class SubscriptionCyclesRelationManager extends RelationManager
{
    protected static string $relationship = 'cycles';

    protected static ?string $title = 'دورات الاشتراك';

    protected static ?string $modelLabel = 'دورة';

    protected static ?string $pluralModelLabel = 'الدورات';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cycle_number')
            ->defaultSort('cycle_number', 'desc')
            ->columns([
                TextColumn::make('cycle_number')
                    ->label('الدورة')
                    ->formatStateUsing(fn ($state) => '#'.$state)
                    ->sortable(),

                TextColumn::make('cycle_state')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state, SubscriptionCycle $record): string => match (true) {
                        $state === SubscriptionCycle::STATE_ACTIVE && $record->payment_status === SubscriptionCycle::PAYMENT_PENDING => 'warning',
                        $state === SubscriptionCycle::STATE_ACTIVE => 'success',
                        $state === SubscriptionCycle::STATE_QUEUED => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state, SubscriptionCycle $record): string => match (true) {
                        $state === SubscriptionCycle::STATE_ACTIVE && $record->payment_status === SubscriptionCycle::PAYMENT_PENDING => 'في انتظار الدفع',
                        $state === SubscriptionCycle::STATE_ACTIVE => 'نشطة',
                        $state === SubscriptionCycle::STATE_QUEUED => 'في الانتظار',
                        $state === SubscriptionCycle::STATE_ARCHIVED => 'مؤرشفة',
                        default => $state,
                    }),

                TextColumn::make('starts_at')
                    ->label('تبدأ')
                    ->dateTime('Y-m-d')
                    ->placeholder('—'),

                TextColumn::make('ends_at')
                    ->label('تنتهي')
                    ->dateTime('Y-m-d')
                    ->placeholder('—'),

                TextColumn::make('total_sessions')
                    ->label('الجلسات')
                    ->formatStateUsing(
                        fn ($state, SubscriptionCycle $record) => ($record->sessions_used ?? 0).' / '.$state
                    ),

                TextColumn::make('payment_status')
                    ->label('الدفع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SubscriptionCycle::PAYMENT_PAID => 'success',
                        SubscriptionCycle::PAYMENT_PENDING => 'warning',
                        SubscriptionCycle::PAYMENT_FAILED => 'danger',
                        SubscriptionCycle::PAYMENT_WAIVED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SubscriptionCycle::PAYMENT_PAID => 'مدفوع',
                        SubscriptionCycle::PAYMENT_PENDING => 'في انتظار الدفع',
                        SubscriptionCycle::PAYMENT_FAILED => 'فاشل',
                        SubscriptionCycle::PAYMENT_WAIVED => 'متنازل',
                        default => $state,
                    }),

                TextColumn::make('grace_period_ends_at')
                    ->label('نهاية فترة السماح')
                    ->dateTime('Y-m-d')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : null),

                TextColumn::make('final_price')
                    ->label('السعر')
                    ->money(fn (SubscriptionCycle $record) => $record->currency ?? 'SAR')
                    ->sortable(),

                TextColumn::make('archived_at')
                    ->label('أرشفت')
                    ->dateTime('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
