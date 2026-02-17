<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\CertificateResource\Pages\ListCertificates;
use App\Filament\AcademicTeacher\Resources\CertificateResource\Pages\ViewCertificate;
use App\Filament\AcademicTeacher\Resources\CertificateResource\Pages;
use App\Filament\Shared\Resources\BaseCertificateResource;

/**
 * Certificate Resource for AcademicTeacher Panel
 *
 * Extends BaseCertificateResource for shared functionality.
 */
class CertificateResource extends BaseCertificateResource
{
    protected static string | \UnitEnum | null $navigationGroup = 'التقارير والتقييمات';

    protected static ?int $navigationSort = 3;

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'view' => ViewCertificate::route('/{record}'),
        ];
    }
}
