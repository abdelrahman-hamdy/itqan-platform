<?php

namespace App\Filament\Resources\QuranCircleResource\Pages;

use App\Filament\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranCircle extends ViewRecord
{
    protected static string $resource = QuranCircleResource::class;

    public function getTitle(): string
    {
        return 'دائرة القرآن: ' . $this->record->name_ar;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('publish')
                ->label('نشر للتسجيل')
                ->icon('heroicon-o-megaphone')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update([
                    'status' => 'pending',
                    'enrollment_status' => 'open',
                ]))
                ->visible(fn () => $this->record->status === 'planning'),
            Actions\Action::make('start')
                ->label('بدء الدائرة')
                ->icon('heroicon-o-play-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update([
                    'status' => 'ongoing',
                    'enrollment_status' => 'closed',
                    'actual_start_date' => now(),
                ]))
                ->visible(fn () => $this->record->status === 'pending' && $this->record->enrolled_students >= 3),
            Actions\Action::make('complete')
                ->label('إنهاء الدائرة')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update([
                    'status' => 'completed',
                    'actual_end_date' => now(),
                ]))
                ->visible(fn () => $this->record->status === 'ongoing'),
            Actions\Action::make('cancel')
                ->label('إلغاء الدائرة')
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
                        'notes' => ($this->record->notes ? $this->record->notes . "\n\n" : '') . 
                                  'سبب الإلغاء: ' . $data['cancellation_reason']
                    ]);
                })
                ->visible(fn () => in_array($this->record->status, ['planning', 'pending', 'ongoing'])),
        ];
    }
} 