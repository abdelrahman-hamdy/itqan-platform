<?php

namespace App\Filament\Teacher\Resources\CertificateResource\Pages;

use App\Filament\Teacher\Resources\CertificateResource;
use Filament\Resources\Pages\ListRecords;

class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
