<?php

namespace App\Filament\Shared\Resources;

use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Models\Certificate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Base Certificate Resource
 *
 * Shared implementation for Certificate resources across Teacher and AcademicTeacher panels.
 * Extend this class in panel-specific resources to avoid code duplication.
 */
abstract class BaseCertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الشهادات الصادرة';

    protected static ?string $modelLabel = 'شهادة';

    protected static ?string $pluralModelLabel = 'الشهادات';

    protected static ?string $navigationGroup = 'الشهادات';

    protected static ?int $navigationSort = 1;

    /**
     * Get the base query filtered by teacher.
     * Subclasses can override to add additional filtering.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Show only certificates issued by this teacher
        return parent::getEloquentQuery()
            ->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                  ->orWhere('issued_by', $user->id);
            });
    }

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
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('issued_at')
                            ->label('تاريخ الإصدار')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('معلومات الطالب')
                    ->schema([
                        Forms\Components\TextInput::make('student_name')
                            ->label('الطالب')
                            ->formatStateUsing(fn ($record) => $record?->student?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('academy_name')
                            ->label('الأكاديمية')
                            ->formatStateUsing(fn ($record) => $record?->academy?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('نص الشهادة')
                    ->schema([
                        Forms\Components\Textarea::make('certificate_text')
                            ->label('نص الشهادة')
                            ->disabled()
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

                Tables\Columns\IconColumn::make('is_manual')
                    ->label('يدوية')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-cog')
                    ->trueColor('purple')
                    ->falseColor('blue'),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label('تاريخ الإصدار')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

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
            ])
            ->actions([
                Tables\Actions\Action::make('view_pdf')
                    ->label('عرض PDF')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Certificate $record): string => route('student.certificate.view', [
                        'subdomain' => $record->academy?->subdomain ?? 'itqan-academy',
                        'certificate' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('download')
                    ->label('تحميل')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Certificate $record): string => route('student.certificate.download', [
                        'subdomain' => $record->academy?->subdomain ?? 'itqan-academy',
                        'certificate' => $record->id,
                    ])),

                Tables\Actions\ViewAction::make()
                    ->label('التفاصيل'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
