<?php

namespace App\Filament\Shared\Resources\Profiles;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use App\Rules\PasswordRules;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use App\Enums\EducationalQualification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Schemas\Components\Component;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use App\Enums\Gender;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Models\QuranTeacherProfile;
use App\Services\AcademyContextService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseQuranTeacherProfileResource extends Resource
{
    use TenantAwareFileUpload;

    protected static ?string $model = QuranTeacherProfile::class;
    protected static ?string $tenantOwnershipRelationshipName = 'academy';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'معلمو القرآن';
    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المستخدمين';
    protected static ?string $modelLabel = 'معلم قرآن';
    protected static ?string $pluralModelLabel = 'معلمو القرآن';

    abstract protected static function scopeEloquentQuery(Builder $query): Builder;
    abstract protected static function getTableActions(): array;
    abstract protected static function getTableBulkActions(): array;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('المعلومات الأساسية')->schema([
                Grid::make(2)->schema([
                    TextInput::make('email')->label('البريد الإلكتروني')->email()->required()->unique(ignoreRecord: true),
                    TextInput::make('teacher_code')->label('رمز المعلم')->disabled()->dehydrated(false)->visible(fn (string $operation) => $operation !== 'create'),
                    TextInput::make('first_name')->label('الاسم الأول')->required()->maxLength(255),
                    TextInput::make('last_name')->label('اسم العائلة')->required()->maxLength(255),
                    static::getPhoneInput()->required(),
                    Select::make('gender')->label('الجنس')->options(Gender::options())->default(Gender::MALE->value)->required(),
                ]),
                FileUpload::make('avatar')->label('الصورة الشخصية')->image()->imageEditor()->circleCropper()
                    ->directory(static::getTenantDirectoryLazy('avatars/quran_teachers'))->maxSize(2048),
            ]),
            Section::make('معلومات الحساب')->schema([
                Grid::make(2)->schema([
                    TextInput::make('password')->label('كلمة المرور')->password()->revealable()
                        ->dehydrated(fn ($state) => filled($state))->required(fn (string $context) => $context === 'create')
                        ->minLength(6)->rules([PasswordRules::rule()]),
                    TextInput::make('password_confirmation')->label('تأكيد كلمة المرور')->password()->revealable()
                        ->dehydrated(false)->required(fn (string $context, $get) => $context === 'create' || filled($get('password')))->same('password'),
                ]),
                Toggle::make('user_active_status')->label('الحساب مفعل')->default(true)
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record && $record->user) $component->state($record->user->active_status);
                    })->dehydrated(false),
                Textarea::make('bio')->label('نبذة مختصرة')->rows(3)->maxLength(500),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->recordActions(static::getTableActions())->toolbarActions(static::getTableBulkActions());
    }

    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('educational_qualification')
                ->label('المؤهل العلمي')
                ->options(EducationalQualification::options()),

            SelectFilter::make('gender')
                ->label('الجنس')
                ->options(Gender::options()),

            TernaryFilter::make('offers_trial_sessions')
                ->label('جلسات تجريبية')
                ->placeholder('الكل')
                ->trueLabel('يقدم')
                ->falseLabel('لا يقدم'),

            TernaryFilter::make('active_status')
                ->label('حالة الحساب')
                ->placeholder('الكل')
                ->trueLabel('نشط')
                ->falseLabel('غير نشط')
                ->queries(
                    true: fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('active_status', 1)),
                    false: fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where(fn ($inner) => $inner->where('active_status', 0)->orWhereNull('active_status'))),
                ),
        ];
    }

    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('teacher_code')->label('رمز المعلم')->searchable()->sortable()->weight('bold')->copyable(),
            TextColumn::make('full_name')->label('الاسم الكامل')->searchable(['first_name', 'last_name'])->sortable(),
            TextColumn::make('email')->label('البريد الإلكتروني')->searchable()->sortable()->copyable(),
            TextColumn::make('phone')->label('رقم الهاتف')->searchable()->toggleable(isToggledHiddenByDefault: true),
            IconColumn::make('user.active_status')->label('نشط')->boolean()->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')->trueColor('success')->falseColor('danger'),
            TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['academy', 'user']);
        return static::scopeEloquentQuery($query);
    }

    // ========================================
    // Academy Context Methods
    // ========================================

    protected static function isViewingAllAcademies(): bool
    {
        if (Filament::getTenant() !== null) {
            return false;
        }

        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() === null;
    }

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    protected static function getAcademyColumn(): TextColumn
    {
        $academyPath = static::getAcademyRelationshipPath();

        return TextColumn::make($academyPath.'.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(static::isViewingAllAcademies())
            ->placeholder('غير محدد');
    }

    protected static function getPhoneInput(string $name = 'phone', string $label = 'رقم الهاتف'): Component
    {
        return PhoneInput::make($name)->label($label)->defaultCountry('SA')->initialCountry('sa')
            ->excludeCountries(['il'])->separateDialCode(true)->formatAsYouType(true)->showFlags(true)
            ->strictMode(true)->locale('ar')->i18n(['ps' => 'فلسطين']);
    }
}
