<?php

namespace App\Filament\Resources\BusinessServiceRequestResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\BusinessServiceRequestResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewBusinessServiceRequest extends ViewRecord
{
    protected static string $resource = BusinessServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل')
                ->icon('heroicon-o-pencil-square')
                ->color('warning'),

            DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('تأكيد الحذف')
                ->modalDescription('هل أنت متأكد من حذف هذا الطلب؟ لا يمكن التراجع عن هذا الإجراء.')
                ->modalSubmitActionLabel('حذف')
                ->modalCancelActionLabel('إلغاء'),
        ];
    }
}
