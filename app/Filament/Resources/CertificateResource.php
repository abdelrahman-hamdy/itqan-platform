<?php

namespace App\Filament\Resources;

use App\Constants\DefaultAcademy;
use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Filament\Resources\CertificateResource\Pages\EditCertificate;
use App\Filament\Resources\CertificateResource\Pages\ListCertificates;
use App\Filament\Resources\CertificateResource\Pages\ViewCertificate;
use App\Models\Certificate;
use App\Services\CertificateService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CertificateResource extends BaseResource
{
    protected static ?string $model = Certificate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الشهادات';

    protected static ?string $modelLabel = 'شهادة';

    protected static ?string $pluralModelLabel = 'الشهادات';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الشهادات';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الشهادة')
                    ->schema([
                        TextInput::make('certificate_number')
                            ->label('رقم الشهادة')
                            ->disabled(),

                        Select::make('certificate_type')
                            ->label('نوع الشهادة')
                            ->options(CertificateType::class)
                            ->disabled(),

                        Select::make('template_style')
                            ->label('تصميم الشهادة')
                            ->options(CertificateTemplateStyle::class)
                            ->required(),

                        DateTimePicker::make('issued_at')
                            ->label('تاريخ الإصدار')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('معلومات الطالب والمعلم')
                    ->schema([
                        TextInput::make('student_name')
                            ->label('الطالب')
                            ->formatStateUsing(fn ($record) => $record?->student?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('teacher_name')
                            ->label('المعلم')
                            ->formatStateUsing(fn ($record) => $record?->teacher?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('نص الشهادة')
                    ->schema([
                        Textarea::make('certificate_text')
                            ->label('نص الشهادة')
                            ->helperText('هذا هو النص المعروض على الشهادة')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
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
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('template_style')
                    ->label('التصميم')
                    ->badge()
                    ->toggleable(),

                static::getAcademyColumn(),

                TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->default('-')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

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
                    ->dateTime('d/m/Y h:i A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('issuedBy.name')
                    ->label('أصدرها')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                TernaryFilter::make('is_manual')
                    ->label('نوع الإصدار')
                    ->placeholder('الكل')
                    ->trueLabel('يدوية')
                    ->falseLabel('تلقائية'),

                Filter::make('issued_at')
                    ->label('تاريخ الإصدار')
                    ->schema([
                        DatePicker::make('issued_from')
                            ->label('من تاريخ'),
                        DatePicker::make('issued_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
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
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('التفاصيل'),
                    EditAction::make()->label('تعديل'),
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
                    Action::make('revoke')
                        ->label('إلغاء')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('إلغاء الشهادة')
                        ->modalDescription('هل أنت متأكد من إلغاء هذه الشهادة؟ سيتم حذفها نهائياً.')
                        ->modalSubmitActionLabel('إلغاء الشهادة')
                        ->action(function (Certificate $record) {
                            $certificateService = app(CertificateService::class);
                            if ($certificateService->revokeCertificate($record)) {
                                Notification::make()
                                    ->success()
                                    ->title('تم إلغاء الشهادة بنجاح')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('فشل إلغاء الشهادة')
                                    ->send();
                            }
                        })
                        ->visible(fn (Certificate $record) => ! $record->trashed()),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    RestoreBulkAction::make()
                        ->label('استعادة المحدد'),
                    ForceDeleteBulkAction::make()
                        ->label('حذف نهائي'),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Include soft-deleted records for admin management
        return parent::getEloquentQuery()
            ->with(['student', 'academy', 'teacher', 'issuedBy'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'view' => ViewCertificate::route('/{record}'),
            'edit' => EditCertificate::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Certificates are created through the system, not manually
    }
}
