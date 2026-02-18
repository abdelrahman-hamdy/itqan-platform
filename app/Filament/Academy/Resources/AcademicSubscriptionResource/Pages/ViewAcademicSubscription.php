<?php

namespace App\Filament\Academy\Resources\AcademicSubscriptionResource\Pages;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Filament\Academy\Resources\AcademicSubscriptionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;

class ViewAcademicSubscription extends ViewRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('activate')
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
            Action::make('pause')
                ->label('إيقاف مؤقت')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->schema([
                    Textarea::make('pause_reason')
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
            Action::make('resume')
                ->label('استئناف الاشتراك')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
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
            Action::make('cancel')
                ->label('إلغاء الاشتراك')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('cancellation_reason')
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

                    $this->record->academicSessions()
                        ->whereDate('scheduled_at', '>', now())
                        ->where('status', SessionStatus::SCHEDULED->value)
                        ->update(['status' => SessionStatus::CANCELLED->value]);
                })
                ->visible(fn () => $this->record->status !== SessionSubscriptionStatus::CANCELLED),
        ];
    }
}
