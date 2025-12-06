<?php

namespace App\Filament\Resources\QuranSubscriptionResource\Pages;

use App\Filament\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranSubscription extends ViewRecord
{
    protected static string $resource = QuranSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'اشتراك القرآن: ' . $this->record->subscription_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('activate')
                ->label('تفعيل الاشتراك')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update([
                    'status' => 'active',
                    'payment_status' => 'paid',
                    'last_payment_at' => now(),
                ]))
                ->visible(fn () => $this->record->status === 'pending'),
            Actions\Action::make('pause')
                ->label('إيقاف مؤقت')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Textarea::make('pause_reason')
                        ->label('سبب الإيقاف')
                        ->required()
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'paused',
                        'paused_at' => now(),
                        'pause_reason' => $data['pause_reason'],
                    ]);
                })
                ->visible(fn () => $this->record->status === 'active'),
            Actions\Action::make('resume')
                ->label('استئناف الاشتراك')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    // Extend expiry date by the paused duration
                    $pausedDuration = now()->diffInDays($this->record->paused_at);
                    $newExpiryDate = $this->record->expires_at->addDays($pausedDuration);
                    
                    $this->record->update([
                        'status' => 'active',
                        'expires_at' => $newExpiryDate,
                        'paused_at' => null,
                        'pause_reason' => null,
                    ]);
                })
                ->visible(fn () => $this->record->status === 'paused'),
            Actions\Action::make('cancel')
                ->label('إلغاء الاشتراك')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('cancellation_reason')
                        ->label('سبب الإلغاء')
                        ->required()
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => $data['cancellation_reason'],
                        'auto_renew' => false,
                    ]);
                    
                    // Cancel any upcoming sessions
                    $this->record->quranSessions()
                        ->where('session_date', '>', now())
                        ->where('status', 'scheduled')
                        ->update(['status' => 'cancelled']);
                })
                ->visible(fn () => !in_array($this->record->status, ['cancelled', 'expired'])),
            Actions\Action::make('renew')
                ->label('تجديد الاشتراك')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    // Create new subscription period
                    $newExpiryDate = match($this->record->billing_cycle) {
                        'weekly' => now()->addWeeks(1),
                        'monthly' => now()->addMonth(),
                        'quarterly' => now()->addMonths(3),
                        'yearly' => now()->addYear(),
                        default => now()->addMonth()
                    };
                    
                    $this->record->update([
                        'status' => 'active',
                        'payment_status' => 'paid',
                        'expires_at' => $newExpiryDate,
                        'last_payment_at' => now(),
                        'sessions_used' => 0,
                        'sessions_remaining' => $this->record->total_sessions,
                        'trial_used' => 0,
                        'is_trial_active' => false,
                    ]);
                })
                ->visible(fn () => in_array($this->record->status, ['expired', 'active'])),
        ];
    }
} 