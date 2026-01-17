<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\ParentProfileResource\Pages;
use App\Filament\Resources\ParentProfileResource\RelationManagers;
use App\Models\ParentProfile;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParentProfileResource extends BaseResource
{
    use TenantAwareFileUpload;

    protected static ?string $model = ParentProfile::class;

    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'أولياء الأمور';

    protected static ?string $modelLabel = 'ولي أمر';

    protected static ?string $pluralModelLabel = 'أولياء الأمور';

    protected static ?int $navigationSort = 5;

    protected static function getAcademyRelationshipPath(): string
    {
        return 'user'; // ParentProfile -> User -> academy_id
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->rule(function ($livewire) {
                                        return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                            // Get current academy from tenant context (Filament multi-tenancy)
                                            $academyId = \Filament\Facades\Filament::getTenant()?->id;

                                            // Check if email exists in parent_profiles table for this academy (excluding current record if editing)
                                            $parentProfileQuery = \App\Models\ParentProfile::where('email', $value)
                                                ->where('academy_id', $academyId);

                                            if ($livewire->record ?? null) {
                                                $parentProfileQuery->where('id', '!=', $livewire->record->id);
                                            }

                                            if ($parentProfileQuery->exists()) {
                                                $fail('البريد الإلكتروني مستخدم بالفعل في هذه الأكاديمية.');

                                                return;
                                            }

                                            // Check if email exists in users table for this academy (excluding current record if editing)
                                            // With composite unique constraint (email, academy_id), same email can exist in different academies
                                            $userQuery = \App\Models\User::where('email', $value)
                                                ->where('academy_id', $academyId);

                                            if ($livewire->record ?? null) {
                                                $userQuery->where('id', '!=', $livewire->record->user_id);
                                            }

                                            if ($userQuery->exists()) {
                                                $fail('البريد الإلكتروني مستخدم بالفعل في هذه الأكاديمية.');
                                            }
                                        };
                                    })
                                    ->maxLength(255)
                                    ->helperText('سيستخدم ولي الأمر هذا البريد للدخول إلى المنصة'),
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
                            ->directory(static::getTenantDirectoryLazy('avatars/parents'))
                            ->maxSize(2048),
                    ]),
                Forms\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('parent_code')
                                    ->label('رمز ولي الأمر')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('occupation')
                                    ->label('المهنة')
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Textarea::make('address')
                            ->label('العنوان')
                            ->rows(3)
                            ->maxLength(500),
                    ]),
                Forms\Components\Section::make('معلومات الاتصال')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                static::getPhoneInput('secondary_phone', 'رقم هاتف ثانوي'),
                                Forms\Components\Select::make('preferred_contact_method')
                                    ->label('طريقة الاتصال المفضلة')
                                    ->options([
                                        'phone' => 'هاتف',
                                        'email' => 'بريد إلكتروني',
                                        'sms' => 'رسالة نصية',
                                        'whatsapp' => 'واتساب',
                                    ])
                                    ->default('phone'),
                            ]),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('ملاحظات خاصة بالإدارة فقط')
                            ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'admin', 'supervisor'])),
                    ]),
                Forms\Components\Section::make('حالة الحساب')
                    ->schema([
                        Forms\Components\Toggle::make('user_active_status')
                            ->label('الحساب نشط')
                            ->helperText('عند تعطيل الحساب، لن يتمكن ولي الأمر من تسجيل الدخول')
                            ->default(true)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && $record->user) {
                                    $component->state($record->user->active_status);
                                }
                            })
                            ->dehydrated(false),
                    ])
                    ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'admin', 'supervisor'])),
            ]);
    }

    /**
     * Eager load relationships to prevent N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'academy',
                'user',
                'students',
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->full_name ?? 'N/A').'&background=4169E1&color=fff'),
                Tables\Columns\TextColumn::make('parent_code')
                    ->label('رمز ولي الأمر')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم الكامل')
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
                    ->copyable(),
                Tables\Columns\IconColumn::make('has_students')
                    ->label('مرتبط بطلاب')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->students()->exists()),
                Tables\Columns\IconColumn::make('user.active_status')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_students')
                    ->label('مرتبط بطلاب')
                    ->placeholder('جميع أولياء الأمور')
                    ->trueLabel('لديه طلاب')
                    ->falseLabel('بدون طلاب')
                    ->queries(
                        true: fn (Builder $query) => $query->has('students'),
                        false: fn (Builder $query) => $query->doesntHave('students'),
                    ),
                Tables\Filters\TernaryFilter::make('active_status')
                    ->label('حالة الحساب')
                    ->placeholder('جميع الحسابات')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('active_status', true)),
                        false: fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('active_status', false)),
                    ),

                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn ($record) => $record->user?->active_status ? 'تعطيل' : 'تفعيل')
                    ->icon(fn ($record) => $record->user?->active_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->user?->active_status ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->user?->active_status ? 'تعطيل الحساب' : 'تفعيل الحساب')
                    ->modalDescription(fn ($record) => $record->user?->active_status
                        ? 'هل أنت متأكد من تعطيل حساب ولي الأمر؟ لن يتمكن من تسجيل الدخول.'
                        : 'هل أنت متأكد من تفعيل حساب ولي الأمر؟')
                    ->action(function ($record) {
                        if ($record->user) {
                            $record->user->update(['active_status' => ! $record->user->active_status]);
                        }
                    }),
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
                        ->action(fn ($records) => $records->each(fn ($record) => $record->user?->update(['active_status' => true]))),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('تعطيل المحددين')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($record) => $record->user?->update(['active_status' => false]))),
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
            RelationManagers\StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParentProfiles::route('/'),
            'create' => Pages\CreateParentProfile::route('/create'),
            'view' => Pages\ViewParentProfile::route('/{record}'),
            'edit' => Pages\EditParentProfile::route('/{record}/edit'),
        ];
    }
}
