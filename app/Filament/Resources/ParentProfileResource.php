<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParentProfileResource\Pages;
use App\Models\ParentProfile;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\ScopedToAcademyViaRelationship;
use App\Services\AcademyContextService;

class ParentProfileResource extends BaseResource
{
    use ScopedToAcademyViaRelationship;

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
                Forms\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
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
                            ->directory('avatars/parents')
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
                                Forms\Components\Select::make('relationship_type')
                                    ->label('نوع العلاقة')
                                    ->options([
                                        'father' => 'أب',
                                        'mother' => 'أم',
                                        'guardian' => 'وصي',
                                        'other' => 'آخر',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('occupation')
                                    ->label('المهنة')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('workplace')
                                    ->label('مكان العمل')
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('national_id')
                                    ->label('رقم الهوية الوطنية')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('passport_number')
                                    ->label('رقم جواز السفر')
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
                                Forms\Components\TextInput::make('secondary_phone')
                                    ->label('رقم هاتف ثانوي')
                                    ->tel()
                                    ->maxLength(255),
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
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('emergency_contact_name')
                                    ->label('اسم جهة الاتصال في الطوارئ')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('emergency_contact_phone')
                                    ->label('رقم هاتف الطوارئ')
                                    ->tel()
                                    ->maxLength(255),
                            ]),
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
                Tables\Columns\TextColumn::make('parent_code')
                    ->label('رمز ولي الأمر')
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
                Tables\Columns\BadgeColumn::make('relationship_type')
                    ->label('نوع العلاقة')
                    ->colors([
                        'primary' => 'father',
                        'success' => 'mother',
                        'warning' => 'guardian',
                        'info' => 'other',
                    ]),
                Tables\Columns\TextColumn::make('occupation')
                    ->label('المهنة')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_linked')
                    ->label('مرتبط بحساب')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isLinked()),
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('relationship_type')
                    ->label('نوع العلاقة')
                    ->options([
                        'father' => 'أب',
                        'mother' => 'أم',
                        'guardian' => 'وصي',
                        'other' => 'آخر',
                    ]),
                Tables\Filters\TernaryFilter::make('is_linked')
                    ->label('مرتبط بحساب')
                    ->placeholder('جميع أولياء الأمور')
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
            'index' => Pages\ListParentProfiles::route('/'),
            'create' => Pages\CreateParentProfile::route('/create'),
            'view' => Pages\ViewParentProfile::route('/{record}'),
            'edit' => Pages\EditParentProfile::route('/{record}/edit'),
        ];
    }
}
