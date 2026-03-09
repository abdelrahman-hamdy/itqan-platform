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

    public function getHeading(): string
    {
        $count = static::getResource()::getEloquentQuery()->count();

        return 'الشهادات الصادرة ('.$count.')';
    }
}
