<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\CertificateResource\Pages\ListCertificates;
use App\Filament\Teacher\Resources\CertificateResource\Pages\ViewCertificate;
use App\Filament\Shared\Resources\BaseCertificateResource;
use App\Filament\Teacher\Resources\CertificateResource\Pages;

/**
 * Certificate Resource for Teacher (Quran) Panel
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
