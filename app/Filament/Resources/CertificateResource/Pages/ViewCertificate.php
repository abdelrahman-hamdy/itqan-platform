<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\CertificateResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewCertificate extends ViewRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
