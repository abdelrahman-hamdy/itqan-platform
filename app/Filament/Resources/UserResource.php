<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
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
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name ?? 'N/A').'&background=4169E1&color=fff'),
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
                Tables\Filters\TernaryFilter::make('active_status')
                    ->label('حساب نشط'),
                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                // Redirect to profile resource based on user type
                Tables\Actions\Action::make('view_profile')
                    ->label('عرض الملف')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record) => static::getProfileUrl($record, 'view'))
                    ->visible(fn (User $record) => static::hasProfile($record)),
                Tables\Actions\Action::make('edit_profile')
                    ->label('تعديل الملف')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (User $record) => static::getProfileUrl($record, 'edit'))
                    ->visible(fn (User $record) => static::hasProfile($record)),
                Tables\Actions\Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['active_status' => true]))
                    ->visible(fn (User $record) => ! in_array($record->user_type, ['student', 'parent', 'super_admin']) && ! $record->active_status),
                Tables\Actions\Action::make('deactivate')
                    ->label('إيقاف')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['active_status' => false]))
                    ->visible(fn (User $record) => ! in_array($record->user_type, ['student', 'parent', 'super_admin']) && $record->active_status),
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
                        ->action(fn ($records) => $records->filter(fn ($record) => ! in_array($record->user_type, ['student', 'parent', 'super_admin']))->each(fn ($record) => $record->update(['active_status' => true]))),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('إيقاف المحددين')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->filter(fn ($record) => ! in_array($record->user_type, ['student', 'parent', 'super_admin']))->each(fn ($record) => $record->update(['active_status' => false]))),
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
                ? \App\Filament\Resources\QuranTeacherProfileResource::getUrl($action, ['record' => $record->quranTeacherProfile])
                : null,
            'academic_teacher' => $record->academicTeacherProfile
                ? \App\Filament\Resources\AcademicTeacherProfileResource::getUrl($action, ['record' => $record->academicTeacherProfile])
                : null,
            'student' => $record->studentProfile
                ? \App\Filament\Resources\StudentProfileResource::getUrl($action, ['record' => $record->studentProfile])
                : null,
            'parent' => $record->parentProfile
                ? \App\Filament\Resources\ParentProfileResource::getUrl($action, ['record' => $record->parentProfile])
                : null,
            'supervisor' => $record->supervisorProfile
                ? \App\Filament\Resources\SupervisorProfileResource::getUrl($action, ['record' => $record->supervisorProfile])
                : null,
            default => null,
        };
    }
}
