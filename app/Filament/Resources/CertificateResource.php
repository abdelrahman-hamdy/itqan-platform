<?php

namespace App\Filament\Resources;

use App\Constants\DefaultAcademy;
use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use App\Services\CertificateService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CertificateResource extends BaseResource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الشهادات';

    protected static ?string $modelLabel = 'شهادة';

    protected static ?string $pluralModelLabel = 'الشهادات';

    protected static ?string $navigationGroup = 'إدارة الشهادات';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الشهادة')
                    ->schema([
                        Forms\Components\TextInput::make('certificate_number')
                            ->label('رقم الشهادة')
                            ->disabled(),

                        Forms\Components\Select::make('certificate_type')
                            ->label('نوع الشهادة')
                            ->options(CertificateType::class)
                            ->disabled(),

                        Forms\Components\Select::make('template_style')
                            ->label('تصميم الشهادة')
                            ->options(CertificateTemplateStyle::class)
                            ->required(),

                        Forms\Components\DateTimePicker::make('issued_at')
                            ->label('تاريخ الإصدار')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('معلومات الطالب والمعلم')
                    ->schema([
                        Forms\Components\TextInput::make('student_name')
                            ->label('الطالب')
                            ->formatStateUsing(fn ($record) => $record?->student?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('teacher_name')
                            ->label('المعلم')
                            ->formatStateUsing(fn ($record) => $record?->teacher?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('نص الشهادة')
                    ->schema([
                        Forms\Components\Textarea::make('certificate_text')
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
                Tables\Columns\TextColumn::make('certificate_number')
                    ->label('رقم الشهادة')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الشهادة')
                    ->fontFamily('mono')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->default('-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('certificate_type')
                    ->label('النوع')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('template_style')
                    ->label('التصميم')
                    ->badge(),

                static::getAcademyColumn(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->default('-')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_manual')
                    ->label('يدوية')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-cog')
                    ->trueColor('purple')
                    ->falseColor('blue')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label('تاريخ الإصدار')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('issuedBy.name')
                    ->label('أصدرها')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('issued_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('certificate_type')
                    ->label('نوع الشهادة')
                    ->options(CertificateType::class)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('template_style')
                    ->label('التصميم')
                    ->options(CertificateTemplateStyle::class)
                    ->multiple(),

                Tables\Filters\Filter::make('is_manual')
                    ->label('يدوية فقط')
                    ->query(fn (Builder $query): Builder => $query->where('is_manual', true)),

                Tables\Filters\Filter::make('issued_at')
                    ->form([
                        Forms\Components\DatePicker::make('issued_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('issued_until')
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

                Tables\Filters\TrashedFilter::make()
                    ->label('المحذوفة'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_pdf')
                    ->label('عرض PDF')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Certificate $record): string => route('student.certificate.view', [
                        'subdomain' => $record->academy?->subdomain ?? DefaultAcademy::subdomain(),
                        'certificate' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('download')
                    ->label('تحميل')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Certificate $record): string => route('student.certificate.download', [
                        'subdomain' => $record->academy?->subdomain ?? DefaultAcademy::subdomain(),
                        'certificate' => $record->id,
                    ])),

                Tables\Actions\ViewAction::make()
                    ->label('التفاصيل'),

                Tables\Actions\EditAction::make()
                    ->label('تعديل'),

                Tables\Actions\Action::make('revoke')
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('استعادة المحدد'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('حذف نهائي'),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
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
            'index' => Pages\ListCertificates::route('/'),
            'view' => Pages\ViewCertificate::route('/{record}'),
            'edit' => Pages\EditCertificate::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Certificates are created through the system, not manually
    }
}
