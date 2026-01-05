<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\UserAccountStatus;
use Filament\Support\Enums\FontWeight;

class UserResource extends Resource
{
    use TenantAwareFileUpload;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'جميع المستخدمين';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمين';

    protected static ?int $navigationSort = 0;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // For super admin, show all users
        if (AcademyContextService::isSuperAdmin()) {
            return $query;
        }

        // For academy admin, show only users from their academy
        $academyId = AcademyContextService::getCurrentAcademyId();
        if ($academyId) {
            return $query->where('academy_id', $academyId);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('الاسم الأخير')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory(static::getTenantDirectoryLazy('avatars/users'))
                            ->maxSize(2048),
                    ]),

                Forms\Components\Section::make('معلومات الحساب')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_type')
                                    ->label('نوع المستخدم')
                                    ->options([
                                        'student' => 'طالب',
                                        'parent' => 'ولي أمر',
                                        'quran_teacher' => 'معلم قرآن',
                                        'academic_teacher' => 'معلم أكاديمي',
                                        'supervisor' => 'مشرف',
                                        'admin' => 'مدير',
                                        'super_admin' => 'مدير عام',
                                    ])
                                    ->required()
                                    ->searchable(),
                                Forms\Components\Select::make('status')
                                    ->label('حالة الحساب')
                                    ->options(UserAccountStatus::options())
                                    ->required()
                                    ->default(UserAccountStatus::ACTIVE->value),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('active_status')
                                    ->label('حساب نشط')
                                    ->helperText('يجب أن يكون هذا الخيار مفعلاً للسماح للمستخدم بتسجيل الدخول')
                                    ->default(true),
                                Forms\Components\Select::make('academy_id')
                                    ->label('الأكاديمية')
                                    ->relationship('academy', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ]),

                Forms\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->label('نبذة شخصية')
                            ->maxLength(1000)
                            ->rows(3),
                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label('آخر تسجيل دخول')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? 'N/A') . '&background=4169E1&color=fff'),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\BadgeColumn::make('user_type')
                    ->label('نوع المستخدم')
                    ->colors([
                        'primary' => 'student',
                        'success' => 'parent',
                        'warning' => 'quran_teacher',
                        'info' => 'academic_teacher',
                        'danger' => 'supervisor',
                        'secondary' => 'admin',
                        'gray' => 'super_admin',
                    ])
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'student' => 'طالب',
                            'parent' => 'ولي أمر',
                            'quran_teacher' => 'معلم قرآن',
                            'academic_teacher' => 'معلم أكاديمي',
                            'supervisor' => 'مشرف',
                            'admin' => 'مدير',
                            'super_admin' => 'مدير عام',
                            default => $state,
                        };
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('حالة الحساب')
                    ->badge()
                    ->formatStateUsing(fn ($state) => UserAccountStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn ($state) => UserAccountStatus::tryFrom($state)?->color() ?? 'gray'),
                Tables\Columns\IconColumn::make('active_status')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => AcademyContextService::isSuperAdmin()),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label(__('filament.last_login_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'student' => 'طالب',
                        'parent' => 'ولي أمر',
                        'quran_teacher' => 'معلم قرآن',
                        'academic_teacher' => 'معلم أكاديمي',
                        'supervisor' => 'مشرف',
                        'admin' => 'مدير',
                        'super_admin' => 'مدير عام',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة الحساب')
                    ->options(UserAccountStatus::options()),
                Tables\Filters\TernaryFilter::make('active_status')
                    ->label('حساب نشط'),
                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['active_status' => true, 'status' => UserAccountStatus::ACTIVE->value]))
                    ->visible(fn (User $record) => !$record->active_status || $record->status !== UserAccountStatus::ACTIVE->value),
                Tables\Actions\Action::make('deactivate')
                    ->label('إيقاف')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['active_status' => false]))
                    ->visible(fn (User $record) => $record->active_status),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحددين')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['active_status' => true, 'status' => UserAccountStatus::ACTIVE->value]))),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('إيقاف المحددين')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['active_status' => false]))),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
} 