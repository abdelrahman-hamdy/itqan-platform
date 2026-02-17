<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use App\Rules\PasswordRules;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Filament\Resources\AdminResource\Pages\ListAdmins;
use App\Filament\Resources\AdminResource\Pages\CreateAdmin;
use App\Filament\Resources\AdminResource\Pages\ViewAdmin;
use App\Filament\Resources\AdminResource\Pages\EditAdmin;
use App\Enums\UserType;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\AdminResource\Pages;
use App\Models\Academy;
use App\Models\User;
use App\Services\AcademyAdminSyncService;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdminResource extends BaseResource
{
    use TenantAwareFileUpload;

    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'المديرون';

    protected static ?string $modelLabel = 'مدير';

    protected static ?string $pluralModelLabel = 'المديرون';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        // Show ALL admins - no academy context filtering
        // Admin management is SuperAdmin-only, so full visibility is needed
        return parent::getEloquentQuery()
            ->with(['academy'])
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('user_type', UserType::ADMIN->value);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('admin_code')
                                    ->label('رمز المدير')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('يتم إنشاء هذا الرمز تلقائياً')
                                    ->visible(fn (string $operation): bool => $operation !== 'create'),
                                TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('last_name')
                                    ->label('اسم العائلة')
                                    ->required()
                                    ->maxLength(255),
                                static::getPhoneInput()
                                    ->required(),
                            ]),
                        FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory(static::getTenantDirectoryLazy('avatars/admins'))
                            ->maxSize(2048),
                    ]),
                Section::make('معلومات الحساب')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->minLength(6)
                                    ->maxLength(255)
                                    ->rules([PasswordRules::rule()])
                                    ->helperText(fn (string $context): ?string => $context === 'edit' ? 'اترك الحقل فارغاً للإبقاء على كلمة المرور الحالية' : PasswordRules::description()),
                                TextInput::make('password_confirmation')
                                    ->label('تأكيد كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->required(fn (string $context, $get): bool => $context === 'create' || filled($get('password')))
                                    ->same('password')
                                    ->maxLength(255),
                                Hidden::make('user_type')
                                    ->default('admin')
                                    ->dehydrated(),
                                Toggle::make('active_status')
                                    ->label('الحساب مفعل')
                                    ->helperText('عطل هذا الخيار لإيقاف وصول المدير للوحة التحكم')
                                    ->default(true),
                            ]),
                        Select::make('academy_id')
                            ->label('تعيين لإدارة أكاديمية')
                            ->options(function (?User $record) {
                                // Show only academies without admin OR current admin's academy
                                return app(AcademyAdminSyncService::class)
                                    ->getAvailableAcademies($record)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('اختر الأكاديمية للتعيين')
                            ->required()
                            ->dehydrated(true)
                            ->helperText('حدد الأكاديمية التي سيديرها هذا المدير'),
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular(),
                TextColumn::make('name')
                    ->label('الاسم الكامل')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                static::getAcademyColumn(),
                IconColumn::make('active_status')
                    ->label('الحالة')
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool) $record->active_status)
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('last_login_at')
                    ->label('آخر تسجيل دخول')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'admin' => 'مدير',
                    ]),
                TernaryFilter::make('active_status')
                    ->label('الحالة')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
                TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    ForceDeleteBulkAction::make()
                        ->label(__('filament.actions.force_delete_selected')),
                ]),
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
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'view' => ViewAdmin::route('/{record}'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }
}
