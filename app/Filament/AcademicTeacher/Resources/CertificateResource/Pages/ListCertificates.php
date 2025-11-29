<?php

namespace App\Filament\AcademicTeacher\Resources\CertificateResource\Pages;

use App\Filament\AcademicTeacher\Resources\CertificateResource;
use Filament\Resources\Pages\ListRecords;

class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
