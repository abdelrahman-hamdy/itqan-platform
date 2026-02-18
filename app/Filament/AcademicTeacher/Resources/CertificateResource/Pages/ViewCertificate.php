<?php

namespace App\Filament\AcademicTeacher\Resources\CertificateResource\Pages;

use App\Filament\AcademicTeacher\Resources\CertificateResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
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
