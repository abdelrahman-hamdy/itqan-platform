<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\RelationManagers;
use App\Models\AcademicSessionReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSessionReportResource extends Resource
{
    protected static ?string $model = AcademicSessionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'تقارير الجلسات الأكاديمية';

    protected static ?string $modelLabel = 'تقرير جلسة أكاديمية';

    protected static ?string $pluralModelLabel = 'تقارير الجلسات الأكاديمية';

    protected static ?string $navigationGroup = 'التقارير والتقييمات';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Forms\Components\Select::make('session_id')
                            ->relationship('session', 'title', fn (Builder $query) =>
                                $query->whereHas('academicTeacher', fn ($q) => $q->where('user_id', auth()->id()))
                            )
                            ->label('الجلسة')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('student_id')
                            ->label('الطالب')
                            ->options(fn () => \App\Models\User::query()
                                ->where('user_type', 'student')
                                ->whereNotNull('name')
                                ->pluck('name', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->disabled(fn (?AcademicSessionReport $record) => $record !== null),
                    ])->columns(2),

                Forms\Components\Section::make('تقييم الواجب')
                    ->schema([
                        Forms\Components\TextInput::make('homework_degree')
                            ->label('درجة الواجب (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5)
                            ->helperText('تقييم جودة وإنجاز الواجب المنزلي'),
                    ]),

                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('أضف ملاحظات حول أداء الطالب...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('تعديل الحضور (إذا لزم الأمر)')
                    ->schema([
                        Forms\Components\Select::make('attendance_status')
                            ->label('تعديل حالة الحضور')
                            ->options([
                                'attended' => 'حاضر',
                                'late' => 'متأخر',
                                'leaved' => 'غادر مبكراً',
                                'absent' => 'غائب',
                            ])
                            ->helperText('قم بالتغيير فقط إذا كان حساب الحضور التلقائي غير صحيح'),
                        Forms\Components\Toggle::make('manually_evaluated')
                            ->label('تحديد كتقييم يدوي')
                            ->helperText('حدد هذا إذا كنت تقوم بتعديل الحضور التلقائي'),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('سبب التعديل')
                            ->placeholder('اشرح سبب تعديل الحضور التلقائي...')
                            ->visible(fn (Forms\Get $get) => $get('manually_evaluated'))
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->collapsed()
                    ->description('افتح هذا القسم فقط إذا كنت بحاجة إلى تصحيح الحضور يدوياً'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.title')
                    ->label('الجلسة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('homework_degree')
                    ->label('درجة الواجب')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 8 => 'success',
                        (float) $state >= 6 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . '/10' : 'لم يقيم'),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('الحضور')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'attended' => 'حاضر',
                        'late' => 'متأخر',
                        'leaved' => 'غادر مبكراً',
                        'absent' => 'غائب',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'attended' => 'success',
                        'late' => 'warning',
                        'leaved' => 'info',
                        'absent' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('actual_attendance_minutes')
                    ->label('مدة الحضور')
                    ->formatStateUsing(fn (string $state): string => $state . ' دقيقة')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('manually_evaluated')
                    ->label('تعديل يدوي')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('تاريخ التقييم')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options([
                        'attended' => 'حاضر',
                        'late' => 'متأخر',
                        'leaved' => 'غادر مبكراً',
                        'absent' => 'غائب',
                    ]),
                Tables\Filters\Filter::make('has_homework_grade')
                    ->label('تم تقييم الواجب')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_degree')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->whereHas('session.academicTeacher', fn ($q) => $q->where('user_id', auth()->id()))
            );
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
            'index' => Pages\ListAcademicSessionReports::route('/'),
            'create' => Pages\CreateAcademicSessionReport::route('/create'),
            'view' => Pages\ViewAcademicSessionReport::route('/{record}'),
            'edit' => Pages\EditAcademicSessionReport::route('/{record}/edit'),
        ];
    }
}
