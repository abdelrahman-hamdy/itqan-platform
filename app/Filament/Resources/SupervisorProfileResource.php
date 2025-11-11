<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupervisorProfileResource\Pages;
use App\Models\SupervisorProfile;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\ScopedToAcademyViaRelationship;
use App\Services\AcademyContextService;

class SupervisorProfileResource extends BaseResource
{
    use ScopedToAcademyViaRelationship;

    protected static ?string $model = SupervisorProfile::class;
    
    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'المشرفين';

    protected static ?string $modelLabel = 'مشرف';

    protected static ?string $pluralModelLabel = 'المشرفين';

    protected static ?int $navigationSort = 4;

    protected static function getAcademyRelationshipPath(): string
    {
        return 'user.academy'; // SupervisorProfile -> User -> Academy
    }

    // Note: getEloquentQuery() is now handled by ScopedToAcademyViaRelationship trait

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
                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('avatars/supervisors')
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
                                Forms\Components\Select::make('department')
                                    ->label('القسم')
                                    ->options([
                                        'quran' => 'قسم القرآن',
                                        'academic' => 'القسم الأكاديمي',
                                        'recorded_courses' => 'الدورات المسجلة',
                                        'general' => 'عام',
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('supervision_level')
                                    ->label('مستوى الإشراف')
                                    ->options([
                                        'junior' => 'مشرف مبتدئ',
                                        'senior' => 'مشرف متقدم',
                                        'lead' => 'مشرف رئيسي',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('salary')
                                    ->label('الراتب')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->minValue(0),
                            ]),
                        Forms\Components\KeyValue::make('monitoring_permissions')
                            ->label('صلاحيات المراقبة')
                            ->keyLabel('الصلاحية')
                            ->valueLabel('القيمة')
                            ->default([
                                'view_sessions' => true,
                                'view_reports' => true,
                                'view_chat' => true,
                                'view_assignments' => true,
                            ]),
                        Forms\Components\Select::make('reports_access_level')
                            ->label('مستوى الوصول للتقارير')
                            ->options([
                                'basic' => 'أساسي',
                                'detailed' => 'مفصل',
                                'full' => 'كامل',
                            ])
                            ->required(),
                    ]),
                Forms\Components\Section::make('معلومات التوظيف')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('hired_date')
                                    ->label('تاريخ التعيين')
                                    ->required(),
                                Forms\Components\DatePicker::make('contract_end_date')
                                    ->label('تاريخ انتهاء العقد'),
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
                Tables\Columns\BadgeColumn::make('department')
                    ->label('القسم')
                    ->colors([
                        'primary' => 'quran',
                        'success' => 'academic',
                        'warning' => 'recorded_courses',
                        'info' => 'general',
                    ]),
                Tables\Columns\BadgeColumn::make('supervision_level')
                    ->label('مستوى الإشراف')
                    ->colors([
                        'info' => 'junior',
                        'warning' => 'senior',
                        'success' => 'lead',
                    ]),
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
                Tables\Filters\SelectFilter::make('department')
                    ->label('القسم')
                    ->options([
                        'quran' => 'قسم القرآن',
                        'academic' => 'القسم الأكاديمي',
                        'recorded_courses' => 'الدورات المسجلة',
                        'general' => 'عام',
                    ]),
                Tables\Filters\SelectFilter::make('supervision_level')
                    ->label('مستوى الإشراف')
                    ->options([
                        'junior' => 'مشرف مبتدئ',
                        'senior' => 'مشرف متقدم',
                        'lead' => 'مشرف رئيسي',
                    ]),
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
