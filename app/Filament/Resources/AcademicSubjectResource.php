<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\AcademicSubjectResource\Pages\ListAcademicSubjects;
use App\Filament\Resources\AcademicSubjectResource\Pages\CreateAcademicSubject;
use App\Filament\Resources\AcademicSubjectResource\Pages\ViewAcademicSubject;
use App\Filament\Resources\AcademicSubjectResource\Pages\EditAcademicSubject;
use App\Filament\Resources\AcademicSubjectResource\Pages;
use App\Models\AcademicSubject;
use Filament\Forms;
use Filament\Infolists\Components;
use Filament\Tables;
use Filament\Tables\Table;

class AcademicSubjectResource extends BaseResource
{
    protected static ?string $model = AcademicSubject::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'المواد الأكاديمية';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'مادة أكاديمية';

    protected static ?string $pluralModelLabel = 'المواد الأكاديمية';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المادة')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم المادة (عربي)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('name_en')
                            ->label('اسم المادة (إنجليزي)')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('وصف المادة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('ملاحظات إدارية خاصة بهذه المادة...')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
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

                TextColumn::make('name')
                    ->label('اسم المادة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->toggleable()
                    ->wrap(),

                IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('teachers_count')
                    ->label('عدد المعلمين')
                    ->counts('teachers')
                    ->badge()
                    ->color('info'),

                TextColumn::make('interactive_courses_count')
                    ->label('الدورات التفاعلية')
                    ->counts('interactiveCourses')
                    ->badge()
                    ->color('success'),

                TextColumn::make('recorded_courses_count')
                    ->label('الدورات المسجلة')
                    ->counts('recordedCourses')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشطة')
                    ->falseLabel('غير نشطة'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف')
                    ->before(function (AcademicSubject $record, DeleteAction $action) {
                        $dependencies = [];

                        if ($record->teachers()->count() > 0) {
                            $dependencies[] = 'معلمين ('.$record->teachers()->count().')';
                        }
                        if ($record->academicIndividualLessons()->count() > 0) {
                            $dependencies[] = 'دروس فردية ('.$record->academicIndividualLessons()->count().')';
                        }
                        if ($record->interactiveCourses()->count() > 0) {
                            $dependencies[] = 'دورات تفاعلية ('.$record->interactiveCourses()->count().')';
                        }
                        if ($record->recordedCourses()->count() > 0) {
                            $dependencies[] = 'دورات مسجلة ('.$record->recordedCourses()->count().')';
                        }

                        if (! empty($dependencies)) {
                            Notification::make()
                                ->danger()
                                ->title('لا يمكن حذف المادة الأكاديمية')
                                ->body('يوجد سجلات مرتبطة: '.implode('، ', $dependencies))
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->before(function ($records, DeleteBulkAction $action) {
                            $blockedRecords = [];

                            foreach ($records as $record) {
                                $hasDependencies = $record->teachers()->count() > 0
                                    || $record->academicIndividualLessons()->count() > 0
                                    || $record->interactiveCourses()->count() > 0
                                    || $record->recordedCourses()->count() > 0;

                                if ($hasDependencies) {
                                    $blockedRecords[] = $record->name;
                                }
                            }

                            if (! empty($blockedRecords)) {
                                Notification::make()
                                    ->danger()
                                    ->title('لا يمكن حذف بعض المواد')
                                    ->body('المواد التالية لديها سجلات مرتبطة: '.implode('، ', $blockedRecords))
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),

                    BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => true]))),

                    BulkAction::make('deactivate')
                        ->label('إلغاء تفعيل المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => false]))),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('اسم المادة (عربي)')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('name_en')
                                    ->label('اسم المادة (إنجليزي)')
                                    ->placeholder('غير محدد'),
                            ]),

                        TextEntry::make('description')
                            ->label('وصف المادة')
                            ->placeholder('لا يوجد وصف')
                            ->columnSpanFull(),

                        TextEntry::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                    ]),

                Section::make('الحالة والإحصائيات')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('is_active')
                                    ->label('الحالة')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشطة' : 'غير نشطة'),

                                TextEntry::make('academy.name')
                                    ->label('الأكاديمية')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i'),
                            ]),
                    ]),

                Section::make('الإحصائيات')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('teachers_count')
                                    ->label('عدد المعلمين')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('grade_levels_count')
                                    ->label('المراحل الدراسية')
                                    ->badge()
                                    ->color('warning'),

                                TextEntry::make('interactive_courses_count')
                                    ->label('الدورات التفاعلية')
                                    ->state(fn ($record) => $record->interactiveCourses()->count())
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('recorded_courses_count')
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
            'index' => ListAcademicSubjects::route('/'),
            'create' => CreateAcademicSubject::route('/create'),
            'view' => ViewAcademicSubject::route('/{record}'),
            'edit' => EditAcademicSubject::route('/{record}/edit'),
        ];
    }
}
