<?php

namespace App\Filament\Resources\QuranCircleResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Models\QuranCircle;
use App\Enums\CircleEnrollmentStatus;
use App\Filament\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property QuranCircle $record
 */
class ViewQuranCircle extends ViewRecord
{
    protected static string $resource = QuranCircleResource::class;

    public function getTitle(): string
    {
        return 'دائرة القرآن: '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('toggle_status')
                ->label(fn () => $this->record->status ? 'إلغاء التفعيل' : 'تفعيل')
                ->icon(fn () => $this->record->status ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                ->color(fn () => $this->record->status ? 'warning' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->status ? 'إلغاء تفعيل الحلقة' : 'تفعيل الحلقة')
                ->modalDescription(fn () => $this->record->status
                    ? 'هل أنت متأكد من إلغاء تفعيل هذه الحلقة؟ لن يتمكن الطلاب من الانضمام إليها.'
                    : 'هل أنت متأكد من تفعيل هذه الحلقة؟ ستصبح متاحة للطلاب للانضمام.'
                )
                ->action(fn () => $this->record->update([
                    'status' => ! $this->record->status,
                    'enrollment_status' => $this->record->status ? CircleEnrollmentStatus::CLOSED : CircleEnrollmentStatus::OPEN,
                ])),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
