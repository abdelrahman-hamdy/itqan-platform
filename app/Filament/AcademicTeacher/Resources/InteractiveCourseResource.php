<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseResource\Pages;
use App\Models\InteractiveCourse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InteractiveCourseResource extends Resource
{
    protected static ?string $model = InteractiveCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationGroup = 'الدورات التفاعلية';

    protected static ?string $modelLabel = 'دورة تفاعلية';

    protected static ?string $pluralModelLabel = 'الدورات التفاعلية';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        // Use the same form schema as admin
        return \App\Filament\Resources\InteractiveCourseResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $teacherProfile = $user->academicTeacherProfile;

                return $query->where('assigned_teacher_id', $teacherProfile?->id ?? 0);
            })
            ->columns([
                Tables\Columns\TextColumn::make('course_code')
                    ->label('رمز الدورة')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الدورة')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(function (InteractiveCourse $record): ?string {
                        return $record->title;
                    }),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('المادة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('المستوى')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_sessions')
                    ->label('عدد الجلسات')
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrolled_students_count')
                    ->label('الطلاب المسجلين')
                    ->counts('enrolledStudents')
                    ->suffix(fn (InteractiveCourse $record): string => " / {$record->max_students}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_per_student')
                    ->label('السعر')
                    ->prefix('ر.س ')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'primary' => 'published',
                        'success' => 'active',
                        'warning' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('المادة')
                    ->relationship('subject', 'name'),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد دورات تفاعلية')
            ->emptyStateDescription('لم يتم إنشاء أي دورات تفاعلية بعد.')
            ->emptyStateIcon('heroicon-o-presentation-chart-bar');
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
            'index' => Pages\ListInteractiveCourses::route('/'),
            'create' => Pages\CreateInteractiveCourse::route('/create'),
            'view' => Pages\ViewInteractiveCourse::route('/{record}'),
            'edit' => Pages\EditInteractiveCourse::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && $user->isAcademicTeacher();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        return parent::getEloquentQuery()
            ->where('assigned_teacher_id', $teacherProfile?->id ?? 0);
    }
}
