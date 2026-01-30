<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentSettingsResource\Pages;
use App\Models\Academy;
use App\Services\AcademyContextService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentSettingsResource extends BaseResource
{
    protected static ?string $model = Academy::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'إعدادات الدفع';

    protected static ?string $modelLabel = 'إعدادات الدفع';

    protected static ?string $pluralModelLabel = 'إعدادات الدفع';

    protected static ?int $navigationSort = 3;

    /**
     * Get the navigation group dynamically based on the current panel.
     */
    public static function getNavigationGroup(): ?string
    {
        $panel = \Filament\Facades\Filament::getCurrentPanel();

        if ($panel?->getId() === 'admin') {
            return 'إدارة الأكاديميات';
        }

        // For Academy panel, use the settings group
        return __('filament.nav_groups.settings');
    }

    protected static ?string $slug = 'payment-settings';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->user_type === 'super_admin' || $user?->user_type === 'admin';
    }

    public static function canAccess(): bool
    {
        // For Academy panel with tenant routing, always accessible
        if (\Filament\Facades\Filament::getTenant() !== null) {
            return true;
        }

        // For Admin panel, require specific academy selected
        return static::hasSpecificAcademySelected();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user?->user_type === 'super_admin' || $user?->user_type === 'admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function hasSpecificAcademySelected(): bool
    {
        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() !== null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Default Gateway Settings
                Section::make('إعدادات البوابة الافتراضية')
                    ->description('اختر البوابة الافتراضية للدفع والبوابات المفعلة لهذه الأكاديمية')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Select::make('payment_settings.default_gateway')
                            ->label('البوابة الافتراضية')
                            ->options([
                                'paymob' => 'باي موب (Paymob)',
                                'easykash' => 'إيزي كاش (EasyKash)',
                            ])
                            ->placeholder('اختر البوابة الافتراضية')
                            ->helperText('البوابة التي سيتم استخدامها افتراضياً عند إنشاء الدفعات'),

                        CheckboxList::make('payment_settings.enabled_gateways')
                            ->label('البوابات المفعلة')
                            ->options([
                                'paymob' => 'باي موب (Paymob) - مصر، السعودية، الإمارات',
                                'easykash' => 'إيزي كاش (EasyKash) - مصر',
                            ])
                            ->helperText('اختر البوابات المتاحة للطلاب في هذه الأكاديمية. إذا لم يتم اختيار أي بوابة، ستكون جميع البوابات متاحة.')
                            ->columns(1),
                    ])
                    ->collapsible(),

                // Paymob Gateway Settings
                Section::make('إعدادات باي موب (Paymob)')
                    ->description('بيانات الاتصال ببوابة باي موب - تدعم البطاقات، المحافظ الإلكترونية، و Apple Pay')
                    ->icon('heroicon-o-credit-card')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Toggle::make('payment_settings.paymob.use_global')
                            ->label('استخدام البيانات العامة')
                            ->helperText('عند التفعيل، سيتم استخدام بيانات باي موب المحددة في إعدادات المنصة الرئيسية')
                            ->default(true)
                            ->live(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('payment_settings.paymob.api_key')
                                    ->label('مفتاح API')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('أدخل مفتاح API الخاص بحساب باي موب')
                                    ->helperText('مفتاح API من لوحة تحكم باي موب')
                                    ->visible(fn (Get $get) => ! $get('payment_settings.paymob.use_global')),

                                TextInput::make('payment_settings.paymob.secret_key')
                                    ->label('المفتاح السري')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('أدخل Secret Key')
                                    ->helperText('Secret Key للـ Unified Intention API')
                                    ->visible(fn (Get $get) => ! $get('payment_settings.paymob.use_global')),

                                TextInput::make('payment_settings.paymob.public_key')
                                    ->label('المفتاح العام')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('أدخل Public Key')
                                    ->helperText('Public Key للـ Unified Intention API')
                                    ->visible(fn (Get $get) => ! $get('payment_settings.paymob.use_global')),

                                TextInput::make('payment_settings.paymob.hmac_secret')
                                    ->label('مفتاح HMAC')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('أدخل HMAC Secret')
                                    ->helperText('للتحقق من صحة الـ Webhook')
                                    ->visible(fn (Get $get) => ! $get('payment_settings.paymob.use_global')),

                                TextInput::make('payment_settings.paymob.card_integration_id')
                                    ->label('رقم تكامل البطاقات')
                                    ->placeholder('مثال: 5020483')
                                    ->helperText('Integration ID للدفع بالبطاقات')
                                    ->visible(fn (Get $get) => ! $get('payment_settings.paymob.use_global')),

                                TextInput::make('payment_settings.paymob.wallet_integration_id')
                                    ->label('رقم تكامل المحافظ')
                                    ->placeholder('رقم التكامل للمحافظ الإلكترونية')
                                    ->helperText('Integration ID للمحافظ الإلكترونية (اختياري)')
                                    ->visible(fn (Get $get) => ! $get('payment_settings.paymob.use_global')),
                            ]),
                    ]),

                // EasyKash Gateway Settings
                Section::make('إعدادات إيزي كاش (EasyKash)')
                    ->description('بيانات الاتصال ببوابة إيزي كاش')
                    ->icon('heroicon-o-banknotes')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Toggle::make('payment_settings.easykash.use_global')
                            ->label('استخدام البيانات العامة')
                            ->helperText('عند التفعيل، سيتم استخدام بيانات إيزي كاش المحددة في إعدادات المنصة الرئيسية')
                            ->default(true)
                            ->live(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('payment_settings.easykash.api_key')
                                    ->label('مفتاح API')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('أدخل مفتاح API الخاص بحساب إيزي كاش')
                                    ->helperText('مفتاح API من لوحة تحكم إيزي كاش')
                                    ->visible(fn (Get $get) => ! $get('payment_settings.easykash.use_global')),

                                TextInput::make('payment_settings.easykash.secret_key')
                                    ->label('المفتاح السري')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('أدخل المفتاح السري')
                                    ->helperText('Secret Key للتحقق من صحة الـ Webhook')
                                    ->visible(fn (Get $get) => ! $get('payment_settings.easykash.use_global')),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Academy::query()
                    ->when(
                        // For Academy panel with tenant routing
                        \Filament\Facades\Filament::getTenant()?->id,
                        fn ($query, $academyId) => $query->where('id', $academyId),
                        // For Admin panel with session-based context
                        fn ($query) => $query->when(
                            app(AcademyContextService::class)->getCurrentAcademyId(),
                            fn ($q, $academyId) => $q->where('id', $academyId)
                        )
                    )
            )
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الأكاديمية')
                    ->weight('bold'),

                TextColumn::make('payment_settings.default_gateway')
                    ->label('البوابة الافتراضية')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'paymob' => 'باي موب',
                        'easykash' => 'إيزي كاش',
                        default => 'غير محدد',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paymob' => 'info',
                        'easykash' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('payment_settings.enabled_gateways')
                    ->label('البوابات المفعلة')
                    ->formatStateUsing(function ($state): string {
                        if (empty($state)) {
                            return 'الكل';
                        }

                        $gateways = is_array($state) ? $state : [$state];
                        $labels = [];
                        foreach ($gateways as $gateway) {
                            $labels[] = match ($gateway) {
                                'paymob' => 'باي موب',
                                'easykash' => 'إيزي كاش',
                                default => $gateway,
                            };
                        }

                        return implode(', ', $labels);
                    })
                    ->wrap(),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->paginated(false)
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentSettings::route('/'),
            'edit' => Pages\EditPaymentSettings::route('/{record}/edit'),
        ];
    }

    /**
     * Override to prevent trying to load 'academy' relationship on Academy model
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return ''; // Academy model doesn't have a relationship to itself
    }
}
