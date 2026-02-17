<?php

namespace App\Filament\Supervisor\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Models\User;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use App\Filament\Supervisor\Resources\MonitoredCertificatesResource\Pages\ListMonitoredCertificates;
use App\Filament\Supervisor\Resources\MonitoredCertificatesResource\Pages\ViewMonitoredCertificate;
use App\Constants\DefaultAcademy;
use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Filament\Supervisor\Resources\MonitoredCertificatesResource\Pages;
use App\Models\Certificate;
use Filament\Forms;
use Filament\Infolists;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitored Certificates Resource for Supervisor Panel
 *
 * Read-only view of certificates issued by supervised teachers.
 * Supervisors can view and download certificates but not create/edit them.
 */
class MonitoredCertificatesResource extends BaseSupervisorResource
{
    protected static ?string $model = Certificate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الشهادات';

    protected static ?string $modelLabel = 'شهادة';

    protected static ?string $pluralModelLabel = 'الشهادات';

    protected static string | \UnitEnum | null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 2;

    /**
     * Supervisors cannot create certificates.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Supervisors cannot edit certificates.
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Supervisors cannot delete certificates.
     */
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('certificate_number')
                    ->label('رقم الشهادة')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الشهادة')
                    ->fontFamily('mono')
                    ->size('sm'),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->default('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('certificate_type')
                    ->label('النوع')
                    ->badge()
                    ->sortable(),

                TextColumn::make('template_style')
                    ->label('التصميم')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->default('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('issuedBy.name')
                    ->label('أصدرها')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_manual')
                    ->label('يدوية')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-cog')
                    ->trueColor('purple')
                    ->falseColor('blue')
                    ->toggleable(),

                TextColumn::make('issued_at')
                    ->label('تاريخ الإصدار')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('issued_at', 'desc')
            ->filters([
                SelectFilter::make('certificate_type')
                    ->label('نوع الشهادة')
                    ->options(CertificateType::class)
                    ->multiple(),

                SelectFilter::make('template_style')
                    ->label('التصميم')
                    ->options(CertificateTemplateStyle::class)
                    ->multiple(),

                SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $allTeacherIds = array_merge(
                            static::getAssignedQuranTeacherIds(),
                            static::getAssignedAcademicTeacherIds()
                        );

                        return User::whereIn('id', $allTeacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->searchable()
                    ->preload(),

                Filter::make('is_manual')
                    ->label('يدوية فقط')
                    ->query(fn (Builder $query): Builder => $query->where('is_manual', true)),

                Filter::make('issued_at')
                    ->schema([
                        DatePicker::make('issued_from')
                            ->label('من تاريخ'),
                        DatePicker::make('issued_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['issued_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issued_at', '>=', $date),
                            )
                            ->when(
                                $data['issued_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issued_at', '<=', $date),
                            );
                    }),
            ])
            ->deferFilters(false)
            ->recordActions([
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

                ViewAction::make()
                    ->label('التفاصيل'),
            ])
            ->toolbarActions([
                // No bulk actions for supervisors
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الشهادة')
                    ->schema([
                        TextEntry::make('certificate_number')
                            ->label('رقم الشهادة')
                            ->copyable(),
                        TextEntry::make('certificate_type')
                            ->label('نوع الشهادة')
                            ->badge(),
                        TextEntry::make('template_style')
                            ->label('تصميم الشهادة')
                            ->badge(),
                        TextEntry::make('issued_at')
                            ->label('تاريخ الإصدار')
                            ->dateTime('d/m/Y h:i A'),
                    ])->columns(2),

                Section::make('معلومات الطالب والمعلم')
                    ->schema([
                        TextEntry::make('student.name')
                            ->label('الطالب'),
                        TextEntry::make('teacher.name')
                            ->label('المعلم')
                            ->placeholder('غير محدد'),
                        TextEntry::make('issuedBy.name')
                            ->label('أصدرها'),
                        TextEntry::make('academy.name')
                            ->label('الأكاديمية'),
                    ])->columns(2),

                Section::make('نص الشهادة')
                    ->schema([
                        TextEntry::make('certificate_text')
                            ->label('نص الشهادة')
                            ->columnSpanFull(),
                    ]),

                Section::make('معلومات إضافية')
                    ->schema([
                        IconEntry::make('is_manual')
                            ->label('شهادة يدوية')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('d/m/Y h:i A'),
                    ])->columns(2),
            ]);
    }

    /**
     * Only show navigation if supervisor has assigned teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAssignedTeachers();
    }

    /**
     * Override query to filter certificates by supervised teachers.
     * Show certificates where teacher_id OR issued_by is an assigned teacher.
     */
    public static function getEloquentQuery(): Builder
    {
        $quranTeacherIds = static::getAssignedQuranTeacherIds();
        $academicTeacherIds = static::getAssignedAcademicTeacherIds();
        $allTeacherIds = array_merge($quranTeacherIds, $academicTeacherIds);

        $query = parent::getEloquentQuery()
            ->with(['student', 'academy', 'teacher', 'issuedBy']);

        if (! empty($allTeacherIds)) {
            $query->where(function (Builder $q) use ($allTeacherIds) {
                $q->whereIn('teacher_id', $allTeacherIds)
                    ->orWhereIn('issued_by', $allTeacherIds);
            });
        } else {
            // No teachers assigned - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoredCertificates::route('/'),
            'view' => ViewMonitoredCertificate::route('/{record}'),
        ];
    }
}
