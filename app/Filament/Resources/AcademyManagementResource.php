<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Filament\Resources\AcademyManagementResource\Pages\ListAcademyManagements;
use App\Filament\Resources\AcademyManagementResource\Pages\CreateAcademyManagement;
use App\Filament\Resources\AcademyManagementResource\Pages\EditAcademyManagement;
use App\Filament\Resources\AcademyManagementResource\Pages\ViewAcademyManagement;
use Illuminate\Support\HtmlString;
use ValueError;
use App\Enums\GradientPalette;
use App\Enums\TailwindColor;
use App\Filament\Resources\AcademyManagementResource\Pages;
use App\Models\Academy;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\RecordedCourse;
use App\Services\AcademyAdminSyncService;
use App\Services\AcademyContextService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademyManagementResource extends BaseResource
{
    protected static ?string $model = Academy::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة النظام';

    protected static ?string $navigationLabel = 'إدارة الأكاديميات';

    protected static ?string $modelLabel = 'أكاديمية';

    protected static ?string $pluralModelLabel = 'الأكاديميات';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم الأكاديمية')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('أدخل اسم الأكاديمية'),

                                TextInput::make('name_en')
                                    ->label('Academy Name (English)')
                                    ->maxLength(255)
                                    ->placeholder('Enter academy name in English'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('subdomain')
                                    ->label('النطاق الفرعي')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('academy-name')
                                    ->helperText('سيكون الرابط: https://academy-name.'.config('app.domain', 'itqanway.com')),

                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('contact@academy.com'),
                            ]),

                        Textarea::make('description')
                            ->label('وصف الأكاديمية')
                            ->rows(4)
                            ->placeholder('أدخل وصف مختصر للأكاديمية'),

                        static::getPhoneInput()
                            ->placeholder('+966 50 123 4567'),
                    ])
                    ->collapsible(),

                Section::make('إدارة الأكاديمية')
                    ->schema([
                        Select::make('admin_id')
                            ->label('مدير الأكاديمية')
                            ->options(function (?Academy $record) {
                                // Show only unassigned admins OR current academy's admin
                                return app(AcademyAdminSyncService::class)
                                    ->getAvailableAdmins($record)
                                    ->mapWithKeys(fn ($user) => [$user->id => $user->name.' ('.$user->email.')']);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('اختر المدير المسؤول عن هذه الأكاديمية'),
                    ])
                    ->collapsible(),

                Section::make('الهوية البصرية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('logo')
                                    ->label('شعار الأكاديمية')
                                    ->image()
                                    ->imagePreviewHeight('150')
                                    ->disk('public')
                                    ->directory('academy-logos')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('الحجم المثالي: 200×200 بكسل')
                                    ->maxSize(2048),

                                FileUpload::make('favicon')
                                    ->label('أيقونة المتصفح (Favicon)')
                                    ->image()
                                    ->imagePreviewHeight('150')
                                    ->disk('public')
                                    ->directory('academy-favicons')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('الحجم المثالي: 32×32 بكسل')
                                    ->maxSize(2048),
                            ]),

                        Radio::make('brand_color')
                            ->label('اللون الأساسي')
                            ->options(static::getColorOptions())
                            ->descriptions(static::getColorDescriptions())
                            ->default(TailwindColor::SKY->value)
                            ->helperText('اختر لون الواجهة الأساسي للأكاديمية')
                            ->required()
                            ->enum(TailwindColor::class)
                            ->inline()
                            ->inlineLabel(false)
                            ->columnSpanFull()
                            ->extraAttributes([
                                'class' => 'color-radio-group',
                            ]),

                        Radio::make('gradient_palette')
                            ->label('لوحة التدرجات اللونية')
                            ->options(static::getGradientPaletteOptions())
                            ->descriptions(static::getGradientPaletteDescriptions())
                            ->default(GradientPalette::OCEAN_BREEZE->value)
                            ->helperText('اختر لوحة التدرجات المستخدمة في الصفحات العامة')
                            ->required()
                            ->enum(GradientPalette::class)
                            ->inline()
                            ->inlineLabel(false)
                            ->columnSpanFull()
                            ->extraAttributes([
                                'class' => 'gradient-radio-group',
                            ]),

                    ])
                    ->collapsible(),

                Section::make('الإعدادات')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('مفعلة')
                                    ->default(true)
                                    ->helperText('تفعيل/إلغاء تفعيل الأكاديمية بشكل كامل'),

                                Toggle::make('allow_registration')
                                    ->label('السماح بالتسجيل')
                                    ->default(true)
                                    ->helperText('السماح للمستخدمين بالتسجيل الجديد'),

                                Toggle::make('maintenance_mode')
                                    ->label('وضع الصيانة')
                                    ->default(false)
                                    ->helperText('إيقاف الوصول مؤقتاً للصيانة')
                                    ->live()
                                    ->afterStateUpdated(fn ($state, $set) => $state ?: $set('academic_settings.maintenance_message', null)),
                            ]),

                        Textarea::make('academic_settings.maintenance_message')
                            ->label('رسالة الصيانة')
                            ->placeholder('رسالة مخصصة تظهر للمستخدمين أثناء الصيانة (اختياري)')
                            ->rows(3)
                            ->helperText('اترك هذا الحقل فارغاً لاستخدام الرسالة الافتراضية')
                            ->visible(fn ($get) => $get('maintenance_mode'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('subdomain')
                    ->label('النطاق الفرعي')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('تم نسخ النطاق الفرعي')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('is_active')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(function (bool $state, $record): string {
                        if (! $state) {
                            return 'غير نشطة';
                        }

                        if ($record->maintenance_mode) {
                            return 'تحت الصيانة';
                        }

                        return 'نشطة';
                    }),

                TextColumn::make('admin.name')
                    ->label('مدير الأكاديمية')
                    ->default('غير محدد')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-user-circle'),

                TextColumn::make('users_count')
                    ->label('المستخدمين')
                    ->counts('users')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('recorded_courses_count')
                    ->label('الدورات المسجلة')
                    ->getStateUsing(function (Academy $record) {
                        return RecordedCourse::where('academy_id', $record->id)->count();
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('interactive_courses_count')
                    ->label('الدورات التفاعلية')
                    ->getStateUsing(function (Academy $record) {
                        return InteractiveCourse::where('academy_id', $record->id)->count();
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('quran_circles_count')
                    ->label('حلقات القرآن')
                    ->getStateUsing(function (Academy $record) {
                        return QuranCircle::where('academy_id', $record->id)->count();
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()->label(__('filament.filters.trashed')),

                TernaryFilter::make('is_active')
                    ->label('مفعلة')
                    ->placeholder('الكل')
                    ->trueLabel('مفعلة')
                    ->falseLabel('غير مفعلة'),

                TernaryFilter::make('maintenance_mode')
                    ->label('وضع الصيانة')
                    ->placeholder('الكل')
                    ->trueLabel('تحت الصيانة')
                    ->falseLabel('غير في الصيانة'),
            ])
            ->recordActions([
                Action::make('select_academy')
                    ->label('اختيار هذه الأكاديمية')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->color('primary')
                    ->action(function (Academy $record) {
                        // Set academy context using our service
                        AcademyContextService::setAcademyContext($record->id);
                    })
                    ->successRedirectUrl(fn () => request()->url())
                    ->requiresConfirmation()
                    ->modalHeading('اختيار الأكاديمية')
                    ->modalDescription(fn (Academy $record) => "هل تريد اختيار أكاديمية '{$record->name}' للإدارة؟ سيتم تحديث جميع الصفحات لعرض بيانات هذه الأكاديمية.")
                    ->modalSubmitActionLabel('اختيار الأكاديمية'),

                Action::make('view_academy')
                    ->label('زيارة الأكاديمية')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Academy $record): string => "https://{$record->subdomain}.".config('app.domain', 'itqanway.com'))
                    ->openUrlInNewTab(),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make()->label(__('filament.actions.restore')),
                ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make()->label(__('filament.actions.restore_selected')),
                    ForceDeleteBulkAction::make()->label(__('filament.actions.force_delete_selected')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcademyManagements::route('/'),
            'create' => CreateAcademyManagement::route('/create'),
            'edit' => EditAcademyManagement::route('/{record}/edit'),
            'view' => ViewAcademyManagement::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Only super admin can access academy management
        return AcademyContextService::isSuperAdmin();
    }

    /**
     * Override to prevent trying to load 'academy' relationship on Academy model
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return ''; // Academy model doesn't have a relationship to itself
    }

    /**
     * Override to prevent issues with academy relationship
     */
    public static function getEloquentQuery(): Builder
    {
        // Don't try to eager load academy relationship for Academy model
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Get color options with labels for Radio field
     */
    protected static function getColorOptions(): array
    {
        return TailwindColor::toArray();
    }

    /**
     * Get descriptions for color options showing color swatches
     */
    protected static function getColorDescriptions(): array
    {
        $descriptions = [];

        foreach (TailwindColor::cases() as $color) {
            $hex = $color->getHexValue(500);
            // Create HTML with color swatch
            $descriptions[$color->value] = new HtmlString(
                '<div class="flex items-center gap-2 mt-1">
                    <div class="w-6 h-6 rounded-md border-2 border-gray-200 shadow-sm" style="background-color: '.$hex.'"></div>
                    <span class="text-xs text-gray-600">'.$hex.'</span>
                </div>'
            );
        }

        return $descriptions;
    }

    /**
     * Get gradient palette options - use non-breaking space for visual-only selection
     */
    protected static function getGradientPaletteOptions(): array
    {
        $options = [];
        foreach (GradientPalette::cases() as $palette) {
            $options[$palette->value] = ' '; // Non-breaking space to ensure rendering
        }

        return $options;
    }

    /**
     * Get descriptions for gradient palette options showing gradient swatches
     */
    protected static function getGradientPaletteDescriptions(): array
    {
        $descriptions = [];

        foreach (GradientPalette::cases() as $palette) {
            $colors = $palette->getColors();
            $fromColor = $colors['from'];
            $toColor = $colors['to'];

            // Parse color names to get proper Tailwind classes
            [$fromColorName, $fromShade] = explode('-', $fromColor);
            [$toColorName, $toShade] = explode('-', $toColor);

            // Get hex values for inline styles (more reliable than Tailwind classes)
            try {
                $fromHex = TailwindColor::from($fromColorName)->getHexValue((int) $fromShade);
                $toHex = TailwindColor::from($toColorName)->getHexValue((int) $toShade);
            } catch (ValueError $e) {
                $fromHex = '#3B82F6';
                $toHex = '#6366F1';
            }

            // Create HTML with gradient swatch using inline styles for reliability
            $descriptions[$palette->value] = new HtmlString(
                '<div style="padding: 8px 0; min-height: 60px; display: flex; align-items: center; justify-content: center;">
                    <div style="width: 140px; height: 50px; border-radius: 8px; border: 3px solid #d1d5db; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); background: linear-gradient(to right, '.$fromHex.', '.$toHex.');">
                    </div>
                </div>'
            );
        }

        return $descriptions;
    }
}
