<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\Shared\Resources\BaseCertificateResource;
use App\Filament\AcademicTeacher\Resources\CertificateResource\Pages;

/**
 * Certificate Resource for AcademicTeacher Panel
 *
 * Extends BaseCertificateResource for shared functionality.
 */
class CertificateResource extends BaseCertificateResource
{
    protected static ?string $navigationGroup = 'التقارير والتقييمات';

    protected static ?int $navigationSort = 3;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
            'view' => Pages\ViewCertificate::route('/{record}'),
        ];
    }
}
