<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicGradeLevelResource\Pages;
use App\Models\AcademicGradeLevel;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AcademyContextService;
use Illuminate\Support\Facades\Auth;

class AcademicGradeLevelResource extends BaseResource
{
    protected static ?string $model = AcademicGradeLevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'الصفوف الدراسية';
    
    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';
    
    protected static ?string $modelLabel = 'صف دراسي';
    
    protected static ?string $pluralModelLabel = 'الصفوف الدراسية';

    protected static ?int $navigationSort = 3;

    /**
     * Check if current user is admin
     */
    private static function isAdmin(): bool
    {
        return Auth::check() && Auth::user()->hasRole(['admin', 'super_admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Apply academy scoping manually since trait is not working
        $academyId = AcademyContextService::getCurrentAcademyId();
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }
        
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الصف الدراسي')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم الصف (عربي)')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('مثل: الصف الأول، الصف الثاني، الصف الثالث'),

                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم الصف (إنجليزي)')
                                    ->maxLength(255)
                                    ->placeholder('Primary, Middle, High School'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الصف (عربي)')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('وصف تفصيلي للصف الدراسي ومتطلباته')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description_en')
                            ->label('وصف الصف (إنجليزي)')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Detailed description of the grade level and requirements')
                            ->columnSpanFull(),
                    ]),



                Forms\Components\Section::make('الإعدادات الإضافية')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('هل هذا الصف متاح للتسجيل؟'),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات إدارية')
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder('ملاحظات إدارية حول الصف')
                            ->columnSpanFull()
                            ->helperText('هذه الملاحظات مرئية للإداريين فقط')
                            ->visible(fn () => self::isAdmin()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الصف')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف (عربي)')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('description_en')
                    ->label('الوصف (إنجليزي)')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('حالة النشاط')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),


            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->before(function (AcademicGradeLevel $record, Tables\Actions\DeleteAction $action) {
                        $dependencies = [];

                        if ($record->students()->count() > 0) {
                            $dependencies[] = 'طلاب (' . $record->students()->count() . ')';
                        }
                        if ($record->interactiveCourses()->count() > 0) {
                            $dependencies[] = 'دورات تفاعلية (' . $record->interactiveCourses()->count() . ')';
                        }
                        if ($record->recordedCourses()->count() > 0) {
                            $dependencies[] = 'دورات مسجلة (' . $record->recordedCourses()->count() . ')';
                        }
                        if ($record->teachers()->count() > 0) {
                            $dependencies[] = 'معلمين (' . $record->teachers()->count() . ')';
                        }

                        if (!empty($dependencies)) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('لا يمكن حذف الصف الدراسي')
                                ->body('يوجد سجلات مرتبطة: ' . implode('، ', $dependencies))
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->before(function ($records, Tables\Actions\DeleteBulkAction $action) {
                            $blockedRecords = [];

                            foreach ($records as $record) {
                                $hasDependencies = $record->students()->count() > 0
                                    || $record->interactiveCourses()->count() > 0
                                    || $record->recordedCourses()->count() > 0
                                    || $record->teachers()->count() > 0;

                                if ($hasDependencies) {
                                    $blockedRecords[] = $record->name;
                                }
                            }

                            if (!empty($blockedRecords)) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('لا يمكن حذف بعض الصفوف')
                                    ->body('الصفوف التالية لديها سجلات مرتبطة: ' . implode('، ', $blockedRecords))
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicGradeLevels::route('/'),
            'create' => Pages\CreateAcademicGradeLevel::route('/create'),
            'view' => Pages\ViewAcademicGradeLevel::route('/{record}'),
            'edit' => Pages\EditAcademicGradeLevel::route('/{record}/edit'),
        ];
    }
}
