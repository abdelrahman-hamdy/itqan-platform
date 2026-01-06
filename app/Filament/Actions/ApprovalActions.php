<?php

namespace App\Filament\Actions;

use App\Enums\ApprovalStatus;
use Filament\Tables\Actions\Action;

/**
 * Factory class for creating common approval-related table actions.
 *
 * This class provides reusable approve, activate, and deactivate actions
 * for teacher profiles and other approvable resources.
 *
 * Usage:
 * ```php
 * ->actions([
 *     ...ApprovalActions::make('معلم'), // For Quran teachers
 *     ...ApprovalActions::make('مدرس'), // For Academic teachers
 *     Tables\Actions\ViewAction::make(),
 *     // ...
 * ])
 * ```
 */
class ApprovalActions
{
    /**
     * Create approval action set with the specified entity label.
     *
     * @param  string  $entityLabel  The label for the entity type (e.g., 'معلم', 'مدرس')
     * @return array<Action>
     */
    public static function make(string $entityLabel = 'عنصر'): array
    {
        return [
            static::approve($entityLabel),
            static::activate($entityLabel),
            static::deactivate(),
        ];
    }

    /**
     * Create the approve action.
     */
    public static function approve(string $entityLabel = 'عنصر'): Action
    {
        return Action::make('approve')
            ->label('موافقة')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn ($record) => $record->approval_status !== ApprovalStatus::APPROVED->value)
            ->requiresConfirmation()
            ->modalHeading("الموافقة على ال{$entityLabel}")
            ->modalDescription("هل أنت متأكد من الموافقة على هذا ال{$entityLabel}؟")
            ->action(function ($record) {
                $record->update([
                    'approval_status' => ApprovalStatus::APPROVED->value,
                    'approved_by' => auth()->user()->id,
                    'approved_at' => now(),
                ]);
            })
            ->successNotificationTitle("تمت الموافقة على ال{$entityLabel} بنجاح");
    }

    /**
     * Create the activate action.
     */
    public static function activate(string $entityLabel = 'عنصر'): Action
    {
        return Action::make('activate')
            ->label('تفعيل')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn ($record) => ! $record->is_active)
            ->requiresConfirmation()
            ->modalHeading("تفعيل ال{$entityLabel}")
            ->modalDescription("هل أنت متأكد من تفعيل هذا ال{$entityLabel}؟ سيتم تفعيل حسابه والموافقة عليه.")
            ->action(function ($record) {
                $record->activate(auth()->user()->id);
            })
            ->successNotificationTitle("تم تفعيل ال{$entityLabel} بنجاح");
    }

    /**
     * Create the deactivate action.
     * Uses localized strings from filament translations.
     */
    public static function deactivate(): Action
    {
        return Action::make('deactivate')
            ->label(__('filament.actions.deactivate'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn ($record) => $record->is_active)
            ->requiresConfirmation()
            ->modalHeading(__('filament.actions.deactivate_confirm_heading'))
            ->modalDescription(__('filament.actions.deactivate_confirm_description'))
            ->action(function ($record) {
                $record->deactivate();
            })
            ->successNotificationTitle(__('filament.messages.deactivated'));
    }
}
