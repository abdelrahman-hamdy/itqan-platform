<?php

namespace App\Filament\Shared\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;
use App\Constants\DefaultAcademy;
use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Filament\Resources\BaseResource;
use App\Models\Certificate;
use Filament\Forms;
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
abstract class BaseCertificateResource extends BaseResource
{
    protected static ?string $model = Certificate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الشهادات الصادرة';

    protected static ?string $modelLabel = 'شهادة';

    protected static ?string $pluralModelLabel = 'الشهادات';

    protected static string | \UnitEnum | null $navigationGroup = 'الشهادات';

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
            ->with(['student', 'academy'])
            ->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhere('issued_by', $user->id);
            });
    }

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
                            ->disabled(),

                        DateTimePicker::make('issued_at')
                            ->label('تاريخ الإصدار')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('معلومات الطالب')
                    ->schema([
                        TextInput::make('student_name')
                            ->label('الطالب')
                            ->formatStateUsing(fn ($record) => $record?->student?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('academy_name')
                            ->label('الأكاديمية')
                            ->formatStateUsing(fn ($record) => $record?->academy?->name ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('نص الشهادة')
                    ->schema([
                        Textarea::make('certificate_text')
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
                static::getAcademyColumn(),

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
                ]),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
