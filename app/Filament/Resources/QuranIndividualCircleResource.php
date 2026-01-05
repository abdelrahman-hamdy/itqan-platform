<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranIndividualCircleResource\Pages;
use App\Models\QuranIndividualCircle;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranIndividualCircleResource extends BaseResource
{
    protected static ?string $model = QuranIndividualCircle::class;

    /**
     * Tenant ownership relationship for Filament multi-tenancy.
     */
    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'الحلقات الفردية';

    protected static ?string $modelLabel = 'حلقة فردية';

    protected static ?string $pluralModelLabel = 'الحلقات الفردية';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الحلقة الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('circle_code')
                                    ->label('رمز الحلقة')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('يتم إنشاؤه تلقائياً'),

                                TextInput::make('name')
                                    ->label('اسم الحلقة')
                                    ->maxLength(255)
                                    ->helperText('يتم إنشاؤه تلقائياً إذا تُرك فارغاً'),

                                Select::make('quran_teacher_id')
                                    ->label('معلم القرآن')
                                    ->options(function () {
                                        try {
                                            $academyId = AcademyContextService::getCurrentAcademyId();

                                            $teachers = \App\Models\QuranTeacherProfile::when($academyId, function ($query) use ($academyId) {
                                                return $query->where('academy_id', $academyId);
                                            })
                                                ->where('is_active', true)
                                                ->get();

                                            if ($teachers->isEmpty()) {
                                                return ['0' => 'لا توجد معلمين نشطين'];
                                            }

                                            return $teachers->mapWithKeys(function ($teacher) {
                                                $userId = $teacher->user_id;
                                                $fullName = $teacher->display_name ?? $teacher->full_name ?? 'معلم غير محدد';
                                                return [$userId => $fullName];
                                            })->toArray();
                                        } catch (\Exception $e) {
                                            \Log::error('Error loading teachers: ' . $e->getMessage());
                                            return ['0' => 'خطأ في تحميل المعلمين'];
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('student_id')
                                    ->label('الطالب')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();

                                        return \App\Models\User::where('user_type', 'student')
                                            ->when($academyId, fn ($q) => $q->where('academy_id', $academyId))
                                            ->with('studentProfile')
                                            ->limit(100) // Optimize: limit for better performance
                                            ->get()
                                            ->mapWithKeys(function ($user) {
                                                $displayName = $user->studentProfile?->display_name ?? $user->name;
                                                return [$user->id => $displayName];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('specialization')
                                    ->label('التخصص')
                                    ->options(QuranIndividualCircle::SPECIALIZATIONS)
                                    ->default('memorization')
                                    ->required(),

                                Select::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->options(QuranIndividualCircle::MEMORIZATION_LEVELS)
                                    ->default('beginner')
                                    ->required(),
                            ]),

                        Textarea::make('description')
                            ->label('وصف الحلقة')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        TagsInput::make('learning_objectives')
                            ->label('أهداف التعلم')
                            ->placeholder('أضف هدفاً تعليمياً')
                            ->helperText('أهداف محددة لهذه الحلقة الفردية')
                            ->reorderable()
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('الحلقة نشطة')
                            ->default(true)
                            ->helperText('يتم تعطيلها تلقائياً عند إيقاف الاشتراك')
                            ->columnSpanFull(),
                    ]),

                Section::make('تتبع التقدم')
                    ->description('يتم حسابها تلقائياً من واجبات الجلسات')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_memorized_pages')
                                    ->label('إجمالي الصفحات المحفوظة')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->helperText('يتم تحديثه من واجبات الحفظ الجديد'),

                                TextInput::make('total_reviewed_pages')
                                    ->label('إجمالي الصفحات المراجعة')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->helperText('يتم تحديثه من واجبات المراجعة'),

                                TextInput::make('total_reviewed_surahs')
                                    ->label('إجمالي السور المراجعة')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->helperText('يتم تحديثه من واجبات المراجعة الشاملة'),
                            ]),
                    ]),

                Section::make('ملاحظات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('ملاحظات داخلية للإدارة'),

                                Textarea::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->rows(3)
                                    ->maxLength(2000)
                                    ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                            ]),
                    ]),
            ]);
    }

    /**
     * Eager load relationships to prevent N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'academy',
                'quranTeacher',
                'student',
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('circle_code')
                    ->label('رمز الحلقة')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold),

                static::getAcademyColumn(),

                TextColumn::make('name')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->name),

                TextColumn::make('quranTeacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('specialization')
                    ->label('التخصص')
                    ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::SPECIALIZATIONS[$state] ?? $state)
                    ->colors([
                        'success' => 'memorization',
                        'info' => 'recitation',
                        'warning' => 'interpretation',
                        'danger' => 'tajweed',
                        'primary' => 'complete',
                    ]),

                BadgeColumn::make('memorization_level')
                    ->label('المستوى')
                    ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::MEMORIZATION_LEVELS[$state] ?? $state)
                    ->color('gray'),

                TextColumn::make('sessions_completed')
                    ->label('الجلسات')
                    ->formatStateUsing(fn ($record): string => "{$record->sessions_completed} / {$record->total_sessions}")
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_memorized_pages')
                    ->label('صفحات الحفظ')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_reviewed_pages')
                    ->label('صفحات المراجعة')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('last_session_at')
                    ->label('آخر جلسة')
                    ->dateTime('Y-m-d')
                    ->placeholder('لم تبدأ')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('تاريخ الحذف')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('نشطة')
                    ->falseLabel('غير نشطة')
                    ->placeholder('الكل'),

                SelectFilter::make('specialization')
                    ->label('التخصص')
                    ->options(QuranIndividualCircle::SPECIALIZATIONS),

                SelectFilter::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->options(QuranIndividualCircle::MEMORIZATION_LEVELS),

                SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->relationship('quranTeacher', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('has_progress')
                    ->label('لها تقدم')
                    ->query(fn (Builder $query): Builder => $query->where('total_memorized_pages', '>', 0)
                        ->orWhere('total_reviewed_pages', '>', 0)),

                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label('المحذوفات'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn (QuranIndividualCircle $record) => $record->is_active ? 'إيقاف' : 'تفعيل')
                        ->icon(fn (QuranIndividualCircle $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                        ->color(fn (QuranIndividualCircle $record) => $record->is_active ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (QuranIndividualCircle $record) => $record->is_active ? 'إيقاف الحلقة' : 'تفعيل الحلقة')
                        ->modalDescription(fn (QuranIndividualCircle $record) => $record->is_active
                            ? 'هل أنت متأكد من إيقاف هذه الحلقة الفردية؟'
                            : 'هل أنت متأكد من تفعيل هذه الحلقة الفردية؟'
                        )
                        ->action(fn (QuranIndividualCircle $record) => $record->update([
                            'is_active' => !$record->is_active,
                        ])),
                    Tables\Actions\Action::make('view_sessions')
                        ->label('الجلسات')
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (QuranIndividualCircle $record): string => QuranSessionResource::getUrl('index', [
                            'tableFilters[individual_circle_id][value]' => $record->id,
                        ])),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                    Tables\Actions\RestoreAction::make()
                        ->label('استعادة'),
                    Tables\Actions\ForceDeleteAction::make()
                        ->label('حذف نهائي'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('استعادة المحدد'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('حذف نهائي للمحدد'),
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
            'index' => Pages\ListQuranIndividualCircles::route('/'),
            'create' => Pages\CreateQuranIndividualCircle::route('/create'),
            'view' => Pages\ViewQuranIndividualCircle::route('/{record}'),
            'edit' => Pages\EditQuranIndividualCircle::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getEloquentQuery()->where('is_active', false);
        return $query->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }
}
