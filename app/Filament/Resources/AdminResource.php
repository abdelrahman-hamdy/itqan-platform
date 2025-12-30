<?php

namespace App\Filament\Resources;

use App\Enums\SubscriptionStatus;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\AdminResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\AcademyContextService;

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
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('user_type', 'admin');

        // For super admin in admin panel
        if (request()->is('admin/*')) {
            $academyId = AcademyContextService::getCurrentAcademyId();

            if ($academyId) {
                // When academy is selected, show both:
                // 1. Super admins (academy_id = null)
                // 2. Academy-specific admins for the selected academy
                $query->where(function ($q) use ($academyId) {
                    $q->whereNull('academy_id') // Super admins
                      ->orWhere('academy_id', $academyId);
                });
            }
            // If no academy context, show all admins
        } else {
            // For academy panel, only show current academy's admins
            $academyId = AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
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
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('اسم العائلة')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->required()
                                    ->maxLength(255),
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
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create'),
                                Forms\Components\Select::make('user_type')
                                    ->label('نوع المستخدم')
                                    ->options([
                                        'admin' => 'مدير',
                                    ])
                                    ->required()
                                    ->default('admin')
                                    ->disabled() // Always admin for this resource
                                    ->dehydrated(),
                                Forms\Components\Toggle::make('active_status')
                                    ->label('الحساب مفعل')
                                    ->helperText('عطل هذا الخيار لإيقاف وصول المدير للوحة التحكم')
                                    ->default(true),
                            ]),
                        Forms\Components\Hidden::make('academy_id')
                            ->default(fn () => AcademyContextService::getCurrentAcademyId())
                            ->dehydrated()
                            ->required(),
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
                    ->label('الأكاديمية')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (is_null($record->academy_id)) {
                            return 'مدير عام'; // Super Admin
                        }
                        return $record->academy?->name ?? 'غير محدد';
                    })
                    ->color(function ($record) {
                        return is_null($record->academy_id) ? 'warning' : 'info';
                    }),
                Tables\Columns\BadgeColumn::make('user_type')
                    ->label('نوع المستخدم')
                    ->getStateUsing(function ($record) {
                        if (is_null($record->academy_id)) {
                            return 'super_admin';
                        }
                        return 'academy_admin';
                    })
                    ->colors([
                        'warning' => 'super_admin',
                        'success' => 'academy_admin',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'مدير عام',
                        'academy_admin' => 'مدير أكاديمية',
                        default => $state,
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        SubscriptionStatus::ACTIVE->value => 'نشط',
                        'inactive' => 'غير نشط',
                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                    ]),

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
