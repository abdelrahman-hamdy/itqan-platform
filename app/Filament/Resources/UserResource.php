<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Rules\PasswordRules;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Enums\UserType;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends BaseResource
{
    use TenantAwareFileUpload;

    /**
     * Disable creation - users should be created through profile resources
     */
    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'جميع المستخدمين';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمين';

    protected static ?int $navigationSort = 0;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['academy'])
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('last_name')
                                    ->label('الاسم الأخير')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                        FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory(static::getTenantDirectoryLazy('avatars/users'))
                            ->maxSize(2048),
                    ]),

                Section::make('معلومات الحساب')
                    ->schema([
                        Select::make('user_type')
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
                        Grid::make(2)
                            ->schema([
                                Toggle::make('active_status')
                                    ->label('حساب نشط')
                                    ->helperText('يجب أن يكون هذا الخيار مفعلاً للسماح للمستخدم بتسجيل الدخول')
                                    ->default(true),
                                Select::make('academy_id')
                                    ->label('الأكاديمية')
                                    ->relationship('academy', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(6)
                            ->maxLength(255)
                            ->rules([PasswordRules::rule()])
                            ->helperText(PasswordRules::description()),
                    ]),

                Section::make('معلومات إضافية')
                    ->schema([
                        Textarea::make('bio')
                            ->label('نبذة شخصية')
                            ->maxLength(1000)
                            ->rows(3),
                        DateTimePicker::make('last_login_at')
                            ->label('آخر تسجيل دخول')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($record->name ?? 'N/A').'&background=4169E1&color=fff'),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::Bold),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('user_type')
                    ->badge()
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
                IconColumn::make('active_status')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                static::getAcademyColumn(),                TextColumn::make('last_login_at')
                    ->label(__('filament.last_login_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('filament.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_type')
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
                TernaryFilter::make('active_status')
                    ->label('حساب نشط'),
                TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->deferFilters(false)
            ->recordActions([
                // Redirect to profile resource based on user type
                Action::make('view_profile')
                    ->label('عرض الملف')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record) => static::getProfileUrl($record, 'view'))
                    ->visible(fn (User $record) => static::hasProfile($record)),
                Action::make('edit_profile')
                    ->label('تعديل الملف')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (User $record) => static::getProfileUrl($record, 'edit'))
                    ->visible(fn (User $record) => static::hasProfile($record)),
                Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['active_status' => true]))
                    ->visible(fn (User $record) => ! in_array($record->user_type, [UserType::STUDENT->value, UserType::PARENT->value, UserType::SUPER_ADMIN->value]) && ! $record->active_status),
                Action::make('deactivate')
                    ->label('إيقاف')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['active_status' => false]))
                    ->visible(fn (User $record) => ! in_array($record->user_type, [UserType::STUDENT->value, UserType::PARENT->value, UserType::SUPER_ADMIN->value]) && $record->active_status),
                DeleteAction::make(),
                RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('تفعيل المحددين')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->filter(fn ($record) => ! in_array($record->user_type, [UserType::STUDENT->value, UserType::PARENT->value, UserType::SUPER_ADMIN->value]))->each(fn ($record) => $record->update(['active_status' => true]))),
                    BulkAction::make('deactivate')
                        ->label('إيقاف المحددين')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->filter(fn ($record) => ! in_array($record->user_type, [UserType::STUDENT->value, UserType::PARENT->value, UserType::SUPER_ADMIN->value]))->each(fn ($record) => $record->update(['active_status' => false]))),
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
            'index' => ListUsers::route('/'),
        ];
    }

    /**
     * Check if the user has a profile that can be viewed/edited
     */
    protected static function hasProfile(User $record): bool
    {
        return match ($record->user_type) {
            'quran_teacher' => $record->quranTeacherProfile !== null,
            'academic_teacher' => $record->academicTeacherProfile !== null,
            'student' => $record->studentProfile !== null,
            'parent' => $record->parentProfile !== null,
            'supervisor' => $record->supervisorProfile !== null,
            default => false, // admin/super_admin have no profile
        };
    }

    /**
     * Get the URL to the profile resource based on user type
     */
    protected static function getProfileUrl(User $record, string $action): ?string
    {
        return match ($record->user_type) {
            'quran_teacher' => $record->quranTeacherProfile
                ? QuranTeacherProfileResource::getUrl($action, ['record' => $record->quranTeacherProfile])
                : null,
            'academic_teacher' => $record->academicTeacherProfile
                ? AcademicTeacherProfileResource::getUrl($action, ['record' => $record->academicTeacherProfile])
                : null,
            'student' => $record->studentProfile
                ? StudentProfileResource::getUrl($action, ['record' => $record->studentProfile])
                : null,
            'parent' => $record->parentProfile
                ? ParentProfileResource::getUrl($action, ['record' => $record->parentProfile])
                : null,
            'supervisor' => $record->supervisorProfile
                ? SupervisorProfileResource::getUrl($action, ['record' => $record->supervisorProfile])
                : null,
            default => null,
        };
    }
}
