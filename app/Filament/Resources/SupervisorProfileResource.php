<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\SupervisorProfileResource\Pages;
use App\Models\SupervisorProfile;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\AcademyContextService;
use App\Models\Academy;

class SupervisorProfileResource extends BaseResource
{
    use TenantAwareFileUpload;

    protected static ?string $model = SupervisorProfile::class;
    
    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'المشرفين';

    protected static ?string $modelLabel = 'مشرف';

    protected static ?string $pluralModelLabel = 'المشرفين';

    protected static ?int $navigationSort = 4;

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // SupervisorProfile -> Academy (direct relationship)
    }

    // Note: getEloquentQuery() is now handled by ScopedToAcademyViaRelationship trait

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        // Academy selection field for super admin when in global view or creating new records
                        Forms\Components\Select::make('academy_id')
                            ->label('الأكاديمية')
                            ->options(Academy::active()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => AcademyContextService::getCurrentAcademy()?->id)
                            ->visible(function () {
                                $user = auth()->user();
                                return $user && $user->isSuperAdmin() && !AcademyContextService::getCurrentAcademy();
                            })
                            ->dehydrated(true) // CRITICAL: Always include in form data even when hidden
                            ->helperText('حدد الأكاديمية التي سينتمي إليها هذا المشرف'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(table: SupervisorProfile::class, ignoreRecord: true)
                                    ->rules([
                                        function (?SupervisorProfile $record) {
                                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                // Check if email exists in users table (excluding the linked user)
                                                $query = \App\Models\User::where('email', $value);
                                                if ($record && $record->user_id) {
                                                    $query->where('id', '!=', $record->user_id);
                                                }
                                                if ($query->exists()) {
                                                    $fail('البريد الإلكتروني مستخدم بالفعل في حساب مستخدم آخر.');
                                                }
                                            };
                                        },
                                    ])
                                    ->maxLength(255)
                                    ->helperText('سيستخدم المشرف هذا البريد للدخول إلى المنصة'),
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

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->helperText('سيتم إنشاء حساب تلقائياً للمشرف باستخدام هذه الكلمة. الحد الأدنى 8 أحرف.')
                                    ->visible(fn ($record) => !$record || !$record->user_id),
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('تأكيد كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->required(fn (string $context, $get): bool => $context === 'create' && filled($get('password')))
                                    ->same('password')
                                    ->maxLength(255)
                                    ->visible(fn ($record) => !$record || !$record->user_id),
                            ]),

                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory(static::getTenantDirectoryLazy('avatars/supervisors'))
                            ->maxSize(2048),
                    ]),
                Forms\Components\Section::make('معلومات العمل')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('supervisor_code')
                                    ->label('رمز المشرف')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('salary')
                                    ->label('الراتب')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->minValue(0),
                            ]),
                    ]),
                Forms\Components\Section::make('معلومات التوظيف')
                    ->schema([
                        Forms\Components\DatePicker::make('hired_date')
                            ->label('تاريخ التعيين')
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
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular(),
                Tables\Columns\TextColumn::make('supervisor_code')
                    ->label('رمز المشرف')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
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
                Tables\Columns\IconColumn::make('is_linked')
                    ->label('مرتبط بحساب')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isLinked()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_linked')
                    ->label('مرتبط بحساب')
                    ->placeholder('جميع المشرفين')
                    ->trueLabel('مرتبط بحساب')
                    ->falseLabel('غير مرتبط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSupervisorProfiles::route('/'),
            'create' => Pages\CreateSupervisorProfile::route('/create'),
            'view' => Pages\ViewSupervisorProfile::route('/{record}'),
            'edit' => Pages\EditSupervisorProfile::route('/{record}/edit'),
        ];
    }
}
