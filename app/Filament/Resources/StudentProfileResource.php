<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentProfileResource\Pages;
use App\Models\StudentProfile;
use App\Models\AcademicGradeLevel;
use App\Traits\ScopedToAcademyViaRelationship;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Country;

class StudentProfileResource extends BaseResource
{
    use ScopedToAcademyViaRelationship;

    protected static ?string $model = StudentProfile::class;
    
    protected static ?string $tenantOwnershipRelationshipName = 'gradeLevel';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'الطلاب';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'gradeLevel.academy'; // StudentProfile -> GradeLevel -> Academy
    }

    // Note: getEloquentQuery() is now handled by ScopedToAcademyViaRelationship trait



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
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
                                    ->maxLength(255)
                                    ->helperText('سيستخدم الطالب هذا البريد للدخول إلى المنصة'),
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
                            ->directory('avatars/students')
                            ->maxSize(2048),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('birth_date')
                                    ->label('تاريخ الميلاد'),
                                Forms\Components\Select::make('nationality')
                                    ->label('الجنسية')
                                    ->options(Country::toArray())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->enum(Country::class),
                                Forms\Components\Select::make('gender')
                                    ->label('الجنس')
                                    ->options([
                                        'male' => 'ذكر',
                                        'female' => 'أنثى',
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('المعلومات الأكاديمية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('grade_level_id')
                                    ->label('المرحلة الدراسية')
                                    ->relationship('gradeLevel', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\DatePicker::make('enrollment_date')
                                    ->label('تاريخ التسجيل')
                                    ->default(now()),
                            ]),
                    ]),

                Forms\Components\Section::make('معلومات الاتصال والطوارئ')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('العنوان')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('emergency_contact')
                            ->label('رقم الطوارئ')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('parent_id')
                            ->label('معرف ولي الأمر')
                            ->numeric()
                            ->helperText('سيتم ربطه لاحقاً بملف ولي الأمر'),
                    ]),

                Forms\Components\Section::make('ملاحظات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('student_code')
                    ->label('رمز الطالب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('user_id')
                    ->label('مربوط بحساب')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('المرحلة الدراسية')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nationality')
                    ->label('الجنسية')
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) {
                            return '';
                        }

                        try {
                            return \App\Enums\Country::from($state)->getLabel();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('gradeLevel.academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()),
                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('تاريخ التسجيل')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grade_level_id')
                    ->label('المرحلة الدراسية')
                    ->relationship('gradeLevel', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('nationality')
                    ->label('الجنسية')
                    ->options(\App\Enums\Country::toArray())
                    ->preload(),
                Tables\Filters\SelectFilter::make('academic_status')
                    ->label('الحالة الأكاديمية')
                    ->options([
                        'enrolled' => 'مسجل',
                        'graduated' => 'متخرج',
                        'suspended' => 'موقوف',
                        'withdrawn' => 'منسحب',
                    ]),
                Tables\Filters\TernaryFilter::make('user_id')
                    ->label('مربوط بحساب')
                    ->nullable()
                    ->trueLabel('مربوط')
                    ->falseLabel('غير مربوط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentProfiles::route('/'),
            'create' => Pages\CreateStudentProfile::route('/create'),
            'edit' => Pages\EditStudentProfile::route('/{record}/edit'),
        ];
    }
}
