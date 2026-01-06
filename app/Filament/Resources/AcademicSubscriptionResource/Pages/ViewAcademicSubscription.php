<?php

namespace App\Filament\Resources\AcademicSubscriptionResource\Pages;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Filament\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSubscription extends ViewRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'الاشتراك الأكاديمي: '.$this->record->subscription_code;
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
                    'status' => SessionSubscriptionStatus::ACTIVE,
                    'payment_status' => SubscriptionPaymentStatus::PAID,
                    'last_payment_at' => now(),
                ]))
                ->visible(fn () => $this->record->status === SessionSubscriptionStatus::PENDING),
            Actions\Action::make('pause')
                ->label('إيقاف مؤقت')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Textarea::make('pause_reason')
                        ->label('سبب الإيقاف')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => SessionSubscriptionStatus::PAUSED,
                        'paused_at' => now(),
                        'pause_reason' => $data['pause_reason'],
                    ]);
                })
                ->visible(fn () => $this->record->status === SessionSubscriptionStatus::ACTIVE),
            Actions\Action::make('resume')
                ->label('استئناف الاشتراك')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    // Extend end_date by the paused duration
                    $pausedDuration = now()->diffInDays($this->record->paused_at);
                    $newEndDate = $this->record->end_date?->addDays($pausedDuration);

                    $this->record->update([
                        'status' => SessionSubscriptionStatus::ACTIVE,
                        'end_date' => $newEndDate,
                        'paused_at' => null,
                        'pause_reason' => null,
                    ]);
                })
                ->visible(fn () => $this->record->status === SessionSubscriptionStatus::PAUSED),
            Actions\Action::make('cancel')
                ->label('إلغاء الاشتراك')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('cancellation_reason')
                        ->label('سبب الإلغاء')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => SessionSubscriptionStatus::CANCELLED,
                        'cancelled_at' => now(),
                        'cancellation_reason' => $data['cancellation_reason'],
                        'auto_renewal' => false,
                    ]);

                    // Cancel any upcoming sessions
                    $this->record->academicSessions()
                        ->whereDate('scheduled_at', '>', now())
                        ->where('status', SessionStatus::SCHEDULED->value)
                        ->update(['status' => SessionStatus::CANCELLED->value]);
                })
                ->visible(fn () => $this->record->status !== SessionSubscriptionStatus::CANCELLED),
        ];
    }
}
