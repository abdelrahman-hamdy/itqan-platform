<?php

namespace App\Filament\Academy\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Academy\Resources\QuranIndividualCircleResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\CircleTransferService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class ViewQuranIndividualCircle extends ViewRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('transfer_teacher')
                ->label(__('circles.transfer.action_label'))
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning')
                ->form([
                    Select::make('new_teacher_id')
                        ->label(__('circles.transfer.new_teacher_label'))
                        ->placeholder(__('circles.transfer.new_teacher_placeholder'))
                        ->options(function () {
                            $record = $this->getRecord();

                            return QuranTeacherProfile::where('academy_id', $record->academy_id)
                                ->where('user_id', '!=', $record->quran_teacher_id)
                                ->whereHas('user', fn ($q) => $q->where('active_status', true))
                                ->with('user')
                                ->get()
                                ->mapWithKeys(fn ($t) => [
                                    $t->user_id => $t->user?->name ?? '-',
                                ])
                                ->toArray();
                        })
                        ->searchable()
                        ->required(),
                ])
                ->modalHeading(__('circles.transfer.modal_heading'))
                ->modalDescription(__('circles.transfer.modal_description'))
                ->modalSubmitActionLabel(__('circles.transfer.confirm_button'))
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $newTeacher = User::findOrFail($data['new_teacher_id']);

                    try {
                        app(CircleTransferService::class)->transfer(
                            circle: $record,
                            newTeacher: $newTeacher,
                            performedBy: auth()->user(),
                        );

                        Notification::make()
                            ->title(__('circles.transfer.success', ['teacher_name' => $newTeacher->name]))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('circles.transfer.error'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
