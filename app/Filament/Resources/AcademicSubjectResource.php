<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicSubjectResource\Pages;
use App\Models\AcademicSubject;
use App\Models\Academy;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use App\Services\AcademyContextService;

class AcademicSubjectResource extends BaseResource
{

    protected static ?string $model = AcademicSubject::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'المواد الأكاديمية';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'مادة أكاديمية';

    protected static ?string $pluralModelLabel = 'المواد الأكاديمية';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المادة')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المادة (عربي)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('name_en')
                            ->label('اسم المادة (إنجليزي)')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف المادة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('ملاحظات إدارية خاصة بهذه المادة...')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشطة')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المادة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('teachers_count')
                    ->label('عدد المعلمين')
                    ->counts('teachers')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('interactive_courses_count')
                    ->label('الدورات التفاعلية')
                    ->counts('interactiveCourses')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('recorded_courses_count')
                    ->label('الدورات المسجلة')
                    ->counts('recordedCourses')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشطة')
                    ->falseLabel('غير نشطة'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => true]))),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('إلغاء تفعيل المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => false]))),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('name')
                                    ->label('اسم المادة (عربي)')
                                    ->size('lg')
                                    ->weight('bold'),

                                Components\TextEntry::make('name_en')
                                    ->label('اسم المادة (إنجليزي)')
                                    ->placeholder('غير محدد'),
                            ]),

                        Components\TextEntry::make('description')
                            ->label('وصف المادة')
                            ->placeholder('لا يوجد وصف')
                            ->columnSpanFull(),

                        Components\TextEntry::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('الحالة والإحصائيات')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('is_active')
                                    ->label('الحالة')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشطة' : 'غير نشطة'),

                                Components\TextEntry::make('academy.name')
                                    ->label('الأكاديمية')
                                    ->badge()
                                    ->color('primary'),

                                Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i'),
                            ]),
                    ]),

                Components\Section::make('الإحصائيات')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('teachers_count')
                                    ->label('عدد المعلمين')
                                    ->badge()
                                    ->color('info'),

                                Components\TextEntry::make('grade_levels_count')
                                    ->label('المراحل الدراسية')
                                    ->badge()
                                    ->color('warning'),

                                Components\TextEntry::make('interactive_courses_count')
                                    ->label('الدورات التفاعلية')
                                    ->state(fn ($record) => $record->interactiveCourses()->count())
                                    ->badge()
                                    ->color('success'),

                                Components\TextEntry::make('recorded_courses_count')
                                    ->label('الدورات المسجلة')
                                    ->state(fn ($record) => $record->recordedCourses()->count())
                                    ->badge()
                                    ->color('primary'),
                            ]),
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
            'index' => Pages\ListAcademicSubjects::route('/'),
            'create' => Pages\CreateAcademicSubject::route('/create'),
            'view' => Pages\ViewAcademicSubject::route('/{record}'),
            'edit' => Pages\EditAcademicSubject::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $academyId = AcademyContextService::getCurrentAcademyId();
        return $academyId ? static::getModel()::byAcademy($academyId)->count() : '0';
    }
}
