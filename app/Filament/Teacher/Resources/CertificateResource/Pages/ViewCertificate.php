<?php

namespace App\Filament\Teacher\Resources\CertificateResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Teacher\Resources\CertificateResource;
use Filament\Actions\EditAction;

class ViewCertificate extends ViewRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
