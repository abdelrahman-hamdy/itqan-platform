<?php

namespace App\Filament\Resources;

use App\Enums\UserType;
use App\Filament\Resources\AcademicGradeLevelResource\Pages\CreateAcademicGradeLevel;
use App\Filament\Resources\AcademicGradeLevelResource\Pages\EditAcademicGradeLevel;
use App\Filament\Resources\AcademicGradeLevelResource\Pages\ListAcademicGradeLevels;
use App\Filament\Resources\AcademicGradeLevelResource\Pages\ViewAcademicGradeLevel;
use App\Models\AcademicGradeLevel;
use App\Services\AcademyContextService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AcademicGradeLevelResource extends BaseResource
{
    protected static ?string $model = AcademicGradeLevel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الصفوف الدراسية';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?string $modelLabel = 'صف دراسي';

    protected static ?string $pluralModelLabel = 'الصفوف الدراسية';

    protected static ?int $navigationSort = 3;

    /**
     * Check if current user is admin
     */
    private static function isAdmin(): bool
    {
        return Auth::check() && Auth::user()->hasRole([UserType::ADMIN->value, UserType::SUPER_ADMIN->value]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['academy']);

        // Apply academy scoping manually since trait is not working
        $academyId = AcademyContextService::getCurrentAcademyId();
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الصف الدراسي')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم الصف (عربي)')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('مثل: الصف الأول، الصف الثاني، الصف الثالث'),

                                TextInput::make('name_en')
                                    ->label('اسم الصف (إنجليزي)')
                                    ->maxLength(255)
                                    ->placeholder('Primary, Middle, High School'),
                            ]),

                        Textarea::make('description')
                            ->label('وصف الصف (عربي)')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('وصف تفصيلي للصف الدراسي ومتطلباته')
                            ->columnSpanFull(),

                        Textarea::make('description_en')
                            ->label('وصف الصف (إنجليزي)')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Detailed description of the grade level and requirements')
                            ->columnSpanFull(),
                    ]),

                Section::make('الإعدادات الإضافية')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('هل هذا الصف متاح للتسجيل؟'),

                        Textarea::make('notes')
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
                TextColumn::make('name')
                    ->label('اسم الصف')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('description')
                    ->label('الوصف (عربي)')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('description_en')
                    ->label('الوصف (إنجليزي)')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                static::getAcademyColumn(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('حالة النشاط')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),

            ])
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('عرض'),
                    EditAction::make()->label('تعديل'),
                    DeleteAction::make()
                        ->label('حذف')
                        ->before(function (AcademicGradeLevel $record, DeleteAction $action) {
                            $dependencies = [];

                            // Note: students() pivot table doesn't exist yet - skip check
                            // if ($record->students()->count() > 0) {
                            //     $dependencies[] = 'طلاب ('.$record->students()->count().')';
                            // }
                            if ($record->interactiveCourses()->count() > 0) {
                                $dependencies[] = 'دورات تفاعلية ('.$record->interactiveCourses()->count().')';
                            }
                            if ($record->recordedCourses()->count() > 0) {
                                $dependencies[] = 'دورات مسجلة ('.$record->recordedCourses()->count().')';
                            }
                            if ($record->teachers()->count() > 0) {
                                $dependencies[] = 'معلمين ('.$record->teachers()->count().')';
                            }

                            if (! empty($dependencies)) {
                                Notification::make()
                                    ->danger()
                                    ->title('لا يمكن حذف الصف الدراسي')
                                    ->body('يوجد سجلات مرتبطة: '.implode('، ', $dependencies))
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                        DeleteBulkAction::make()
                            ->label('حذف المحدد')
                            ->before(function ($records, DeleteBulkAction $action) {
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

                                if (! empty($blockedRecords)) {
                                    Notification::make()
                                        ->danger()
                                        ->title('لا يمكن حذف بعض الصفوف')
                                        ->body('الصفوف التالية لديها سجلات مرتبطة: '.implode('، ', $blockedRecords))
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
            'index' => ListAcademicGradeLevels::route('/'),
            'create' => CreateAcademicGradeLevel::route('/create'),
            'view' => ViewAcademicGradeLevel::route('/{record}'),
            'edit' => EditAcademicGradeLevel::route('/{record}/edit'),
        ];
    }
}
