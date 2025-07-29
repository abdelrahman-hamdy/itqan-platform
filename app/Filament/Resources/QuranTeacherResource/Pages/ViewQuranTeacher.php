<?php

namespace App\Filament\Resources\QuranTeacherResource\Pages;

use App\Filament\Resources\QuranTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranTeacher extends ViewRecord
{
    protected static string $resource = QuranTeacherResource::class;

    public function getTitle(): string
    {
        return 'معلم القرآن: ' . $this->record->full_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('approve')
                ->label('اعتماد المعلم')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update([
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'status' => 'active'
                ]))
                ->visible(fn () => $this->record->approval_status === 'pending'),
            Actions\Action::make('reject')
                ->label('رفض المعلم')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('سبب الرفض')
                        ->required()
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'approval_status' => 'rejected',
                        'approved_by' => auth()->id(),
                        'status' => 'inactive',
                        'notes' => ($this->record->notes ? $this->record->notes . "\n\n" : '') . 
                                  'سبب الرفض: ' . $data['rejection_reason']
                    ]);
                })
                ->visible(fn () => $this->record->approval_status === 'pending'),
            Actions\Action::make('suspend')
                ->label('إيقاف المعلم')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('suspension_reason')
                        ->label('سبب الإيقاف')
                        ->required()
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'suspended',
                        'notes' => ($this->record->notes ? $this->record->notes . "\n\n" : '') . 
                                  'سبب الإيقاف: ' . $data['suspension_reason']
                    ]);
                })
                ->visible(fn () => $this->record->status === 'active'),
            Actions\Action::make('reactivate')
                ->label('إعادة تفعيل')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => 'active']))
                ->visible(fn () => in_array($this->record->status, ['inactive', 'suspended'])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add statistics widgets here if needed
        ];
    }
} 