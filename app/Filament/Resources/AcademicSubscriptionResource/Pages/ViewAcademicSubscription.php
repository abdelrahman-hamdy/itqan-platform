<?php

namespace App\Filament\Resources\AcademicSubscriptionResource\Pages;

use App\Filament\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSubscription extends ViewRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'الاشتراك الأكاديمي: ' . $this->record->subscription_code;
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
                    'payment_status' => 'current',
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
                    // Extend end_date by the paused duration
                    $pausedDuration = now()->diffInDays($this->record->paused_at);
                    $newEndDate = $this->record->end_date?->addDays($pausedDuration);

                    $this->record->update([
                        'status' => 'active',
                        'end_date' => $newEndDate,
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
                        'auto_renewal' => false,
                    ]);

                    // Cancel any upcoming sessions
                    $this->record->academicSessions()
                        ->whereDate('scheduled_at', '>', now())
                        ->where('status', 'scheduled')
                        ->update(['status' => 'cancelled']);
                })
                ->visible(fn () => !in_array($this->record->status, ['cancelled', 'expired'])),
        ];
    }
}
