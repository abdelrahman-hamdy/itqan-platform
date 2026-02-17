<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\CertificateResource\Pages\ListCertificates;
use App\Filament\Academy\Resources\CertificateResource\Pages\ViewCertificate;
use App\Filament\Shared\Resources\BaseCertificateResource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Certificate Resource for Academy Panel
 *
 * Academy admins can view all certificates issued within their academy.
 * Extends BaseCertificateResource, overriding query to scope by academy.
 */
class CertificateResource extends BaseCertificateResource
{
    protected static string | \UnitEnum | null $navigationGroup = 'الشهادات';

    protected static ?int $navigationSort = 1;

    /**
     * Academy admins see all certificates in their academy (not just their own).
     */
    public static function getEloquentQuery(): Builder
    {
        $academyId = auth()->user()->academy_id;

        return static::getModel()::query()
            ->where('academy_id', $academyId)
            ->with(['student', 'academy']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'view' => ViewCertificate::route('/{record}'),
        ];
    }
}
