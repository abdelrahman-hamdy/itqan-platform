<?php

namespace App\Filament\Teacher\Resources;

use App\Constants\DefaultAcademy;
use App\Filament\Shared\Resources\BaseCertificateResource;
use App\Filament\Teacher\Resources\CertificateResource\Pages\ListCertificates;
use App\Filament\Teacher\Resources\CertificateResource\Pages\ViewCertificate;
use App\Models\Certificate;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

/**
 * Certificate Resource for Teacher (Quran) Panel
 *
 * Extends BaseCertificateResource for shared functionality.
 */
class CertificateResource extends BaseCertificateResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير والتقييمات';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('التفاصيل'),
                    Action::make('view_pdf')
                        ->label('عرض PDF')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->url(fn (Certificate $record): string => route('student.certificate.view', [
                            'subdomain' => $record->academy?->subdomain ?? DefaultAcademy::subdomain(),
                            'certificate' => $record->id,
                        ]))
                        ->openUrlInNewTab(),
                    Action::make('download')
                        ->label('تحميل')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (Certificate $record): string => route('student.certificate.download', [
                            'subdomain' => $record->academy?->subdomain ?? DefaultAcademy::subdomain(),
                            'certificate' => $record->id,
                        ])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'view' => ViewCertificate::route('/{record}'),
        ];
    }
}
