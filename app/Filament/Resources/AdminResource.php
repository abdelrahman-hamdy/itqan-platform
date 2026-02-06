<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\AdminResource\Pages;
use App\Models\Academy;
use App\Models\User;
use App\Services\AcademyAdminSyncService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdminResource extends BaseResource
{
    use TenantAwareFileUpload;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

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
            ->where('user_type', 'admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('admin_code')
                                    ->label('رمز المدير')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('يتم إنشاء هذا الرمز تلقائياً')
                                    ->visible(fn (string $operation): bool => $operation !== 'create'),
                                Forms\Components\TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('اسم العائلة')
                                    ->required()
                                    ->maxLength(255),
                                static::getPhoneInput()
                                    ->required(),
                            ]),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory(static::getTenantDirectoryLazy('avatars/admins'))
                            ->maxSize(2048),
                    ]),
                Forms\Components\Section::make('معلومات الحساب')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->minLength(6)
                                    ->maxLength(255)
                                    ->rules([\App\Rules\PasswordRules::rule()])
                                    ->helperText(fn (string $context): ?string => $context === 'edit' ? 'اترك الحقل فارغاً للإبقاء على كلمة المرور الحالية' : \App\Rules\PasswordRules::description()),
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('تأكيد كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->required(fn (string $context, $get): bool => $context === 'create' || filled($get('password')))
                                    ->same('password')
                                    ->maxLength(255),
                                Forms\Components\Hidden::make('user_type')
                                    ->default('admin')
                                    ->dehydrated(),
                                Forms\Components\Toggle::make('active_status')
                                    ->label('الحساب مفعل')
                                    ->helperText('عطل هذا الخيار لإيقاف وصول المدير للوحة التحكم')
                                    ->default(true),
                            ]),
                        Forms\Components\Select::make('academy_id')
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
                        Forms\Components\Textarea::make('notes')
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
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم الكامل')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية المُدارة')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (is_null($record->academy_id)) {
                            return 'غير معين لأكاديمية';
                        }

                        return $record->academy?->name ?? 'غير محدد';
                    })
                    ->color(function ($record) {
                        return is_null($record->academy_id) ? 'gray' : 'success';
                    }),
                Tables\Columns\IconColumn::make('active_status')
                    ->label('الحالة')
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool) $record->active_status)
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخر تسجيل دخول')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'admin' => 'مدير',
                    ]),
                Tables\Filters\TernaryFilter::make('active_status')
                    ->label('الحالة')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()
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
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'view' => Pages\ViewAdmin::route('/{record}'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
