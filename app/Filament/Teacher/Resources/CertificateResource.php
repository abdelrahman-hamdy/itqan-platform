<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Shared\Resources\BaseCertificateResource;
use App\Filament\Teacher\Resources\CertificateResource\Pages;

/**
 * Certificate Resource for Teacher (Quran) Panel
 *
 * Extends BaseCertificateResource for shared functionality.
 */
class CertificateResource extends BaseCertificateResource
{
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
            'view' => Pages\ViewCertificate::route('/{record}'),
        ];
    }
}
