<?php

namespace App\Filament\Resources;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\UserType;
use App\Filament\Concerns\HasCrossAcademyAccess;
use App\Filament\Resources\AcademicSubscriptionResource\Pages\CreateAcademicSubscription;
use App\Filament\Resources\AcademicSubscriptionResource\Pages\EditAcademicSubscription;
use App\Filament\Resources\AcademicSubscriptionResource\Pages\ListAcademicSubscriptions;
use App\Filament\Resources\AcademicSubscriptionResource\Pages\ViewAcademicSubscription;
use App\Filament\Shared\Resources\BaseSubscriptionResource;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicPackage;
use App\Models\AcademicSubject;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSubscriptionResource extends BaseSubscriptionResource
{
    use HasCrossAcademyAccess;

    protected static ?string $model = AcademicSubscription::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الاشتراكات الأكاديمية';

    protected static ?string $modelLabel = 'اشتراك أكاديمي';

    protected static ?string $pluralModelLabel = 'الاشتراكات الأكاديمية';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 2;

    protected static function getBasicInfoFormSection(): Section
    {
        return Section::make('معلومات الاشتراك الأساسية')
            ->schema([
                Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->label('الأكاديمية')
                    ->required()
                    ->disabled()
                    ->default(fn () => auth()->user()->academy_id),

                Select::make('student_id')
                    ->label('الطالب')
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(function (string $search) {
                        $academyId = AcademyContextService::getCurrentAcademyId();

                        return User::where('user_type', UserType::STUDENT->value)
                            ->with('studentProfile')
                            ->when($academyId, function ($query) use ($academyId) {
                                $query->whereHas('studentProfile.gradeLevel', function ($q) use ($academyId) {
                                    $q->where('academy_id', $academyId);
                                });
                            })
                            ->where(function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhereHas('studentProfile', function ($sq) use ($search) {
                                        $sq->where('student_code', 'like', "%{$search}%");
                                    });
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->studentProfile?->display_name ?? $user->name];
                            });
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $user = User::with('studentProfile')->find($value);

                        return $user?->studentProfile?->display_name ?? $user?->name ?? 'طالب #'.$value;
                    })
                    ->required(),

                Select::make('teacher_id')
                    ->label('المعلم')
                    ->required()
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(function (string $search) {
                        $academyId = AcademyContextService::getCurrentAcademyId();

                        return AcademicTeacherProfile::with('user')
                            ->when($academyId, function ($query) use ($academyId) {
                                $query->where('academy_id', $academyId);
                            })
                            ->where(function ($q) use ($search) {
                                $q->whereHas('user', function ($uq) use ($search) {
                                    $uq->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($teacher) => [
                                $teacher->id => $teacher->user?->name ?? $teacher->full_name ?? 'معلم #'.$teacher->id,
                            ]);
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $teacher = AcademicTeacherProfile::with('user')->find($value);

                        return $teacher?->user?->name ?? $teacher?->full_name ?? 'معلم #'.$value;
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('subject_id', null);
                        $set('grade_level_id', null);
                    }),

                Select::make('subject_id')
                    ->label('المادة الدراسية')
                    ->required()
                    ->searchable()
                    ->options(function (Get $get) {
                        $teacherId = $get('teacher_id');
                        if ($teacherId) {
                            $teacher = AcademicTeacherProfile::find($teacherId);
                            if ($teacher) {
                                return $teacher->subjects->pluck('name', 'id')->toArray();
                            }
                        }

                        return AcademicSubject::pluck('name', 'id')->toArray();
                    })
                    ->getOptionLabelUsing(function ($value, $record) {
                        if ($record?->subject_name) {
                            return $record->subject_name;
                        }
                        $subject = AcademicSubject::find($value);

                        return $subject?->name ?? 'مادة #'.$value;
                    })
                    ->live(),

                Select::make('grade_level_id')
                    ->label('المرحلة الدراسية')
                    ->required()
                    ->searchable()
                    ->options(function (Get $get) {
                        $teacherId = $get('teacher_id');
                        if ($teacherId) {
                            $teacher = AcademicTeacherProfile::find($teacherId);
                            if ($teacher) {
                                return $teacher->gradeLevels->pluck('name', 'id')->toArray();
                            }
                        }

                        return AcademicGradeLevel::pluck('name', 'id')->toArray();
                    })
                    ->getOptionLabelUsing(function ($value, $record) {
                        if ($record?->grade_level_name) {
                            return $record->grade_level_name;
                        }
                        $gradeLevel = AcademicGradeLevel::find($value);

                        return $gradeLevel?->name ?? 'مرحلة #'.$value;
                    })
                    ->live(),

                Select::make('academic_package_id')
                    ->label('الباقة الأكاديمية')
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(function (string $search) {
                        $academyId = AcademyContextService::getCurrentAcademyId();

                        return AcademicPackage::query()
                            ->when($academyId, function ($query) use ($academyId) {
                                $query->where('academy_id', $academyId);
                            })
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id');
                    })
                    ->getOptionLabelUsing(function ($value, $record) {
                        if ($record?->package_name_ar) {
                            return $record->package_name_ar;
                        }
                        $package = AcademicPackage::find($value);

                        return $package?->name ?? 'باقة #'.$value;
                    }),
            ])->columns(2);
    }

    protected static function getPricingFormSection(): Section
    {
        return Section::make('تفاصيل الاشتراك')
            ->schema([
                TextInput::make('subscription_code')
                    ->label('رمز الاشتراك')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('billing_cycle')
                    ->label('دورة الفوترة')
                    ->options([
                        'monthly' => 'شهرياً',
                        'quarterly' => 'كل 3 شهور',
                        'yearly' => 'سنوياً',
                    ])
                    ->default('monthly')
                    ->required(),

                TextInput::make('final_monthly_amount')
                    ->label('سعر الاشتراك الشهري')
                    ->numeric()
                    ->suffix(getCurrencySymbol())
                    ->helperText('السعر النهائي بعد الخصم'),
            ])->columns(3);
    }

    protected static function getSchedulingSection(): Section
    {
        return Section::make('جدولة الجلسات')
            ->schema([
                TextInput::make('sessions_per_week')
                    ->label('عدد الجلسات أسبوعياً')
                    ->numeric()
                    ->default(2)
                    ->minValue(1)
                    ->maxValue(7)
                    ->helperText('عدد الجلسات المجدولة كل أسبوع'),

                TextInput::make('sessions_per_month')
                    ->label('عدد الجلسات شهرياً')
                    ->numeric()
                    ->disabled()
                    ->helperText('إجمالي الجلسات المتوقعة في الشهر (محسوب تلقائياً)'),
            ])->columns(2);
    }

    protected static function getDatesPaymentSection(): Section
    {
        return Section::make('التواريخ والدفع')
            ->schema([
                Grid::make(3)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('تاريخ البدء')
                            ->default(now())
                            ->required()
                            ->live(),

                        DatePicker::make('end_date')
                            ->label('تاريخ الانتهاء')
                            ->after('start_date')
                            ->helperText('يمكنك تعديل التاريخ يدوياً أو استخدام زر "تمديد الاشتراك" لإضافة أيام محددة'),

                        DatePicker::make('next_billing_date')
                            ->label('تاريخ الفوترة التالي'),
                    ]),

                Select::make('status')
                    ->label('حالة الاشتراك')
                    ->options(SessionSubscriptionStatus::options())
                    ->default(SessionSubscriptionStatus::ACTIVE->value)
                    ->required()
                    ->helperText('ملاحظة: تمديد الاشتراك يقوم تلقائياً بتفعيله'),

                Select::make('payment_status')
                    ->label('حالة الدفع')
                    ->options(SubscriptionPaymentStatus::options())
                    ->default(SubscriptionPaymentStatus::PENDING->value)
                    ->required(),

                static::getAutoRenewToggle(),
            ])->columns(3);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getBasicInfoFormSection(),
                static::getPricingFormSection(),
                static::getSessionSettingsSection(),
                static::getSchedulingSection(),
                static::getDatesPaymentSection(),
                static::getStudentPreferencesSection(),
                static::getNotesSection(),
            ]);
    }

    protected static function getTypeSpecificTableColumns(): array
    {
        return [
            static::getAcademyColumn(),

            TextColumn::make('teacher.user.name')
                ->label('المعلم')
                ->searchable()
                ->sortable(),

            TextColumn::make('subject.name')
                ->label('المادة')
                ->searchable()
                ->toggleable(),

            TextColumn::make('gradeLevel.name')
                ->label('المرحلة')
                ->searchable()
                ->toggleable(),

            TextColumn::make('final_monthly_amount')
                ->label('المبلغ الشهري')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('start_date')
                ->label('تاريخ البدء')
                ->date()
                ->sortable()
                ->toggleable(),

            TextColumn::make('next_billing_date')
                ->label('الفوترة التالية')
                ->date()
                ->sortable()
                ->toggleable(),
        ];
    }

    protected static function getTypeSpecificFilters(): array
    {
        return [
            SelectFilter::make('subject_id')
                ->label(__('filament.course.subject'))
                ->relationship('subject', 'name')
                ->searchable()
                ->preload(),

            SelectFilter::make('teacher_id')
                ->label(__('filament.teacher'))
                ->relationship('teacher.user', 'name')
                ->searchable()
                ->preload(),
        ];
    }

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Include soft-deleted records for admin management
        return $query->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['student', 'teacher.user', 'subject', 'gradeLevel', 'academy']);
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                static::getConfirmPaymentAction(),
                static::getReactivateAction(),
                static::getPauseAction(),
                static::getResumeAction(),
                static::getExtendSubscriptionAction(),
                static::getCancelAction(),
                static::getCancelPendingAction(),
                DeleteAction::make()->label('حذف'),
                RestoreAction::make()->label(__('filament.actions.restore')),
                ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                static::getBulkCancelPendingAction(),
                DeleteBulkAction::make(),
                RestoreBulkAction::make()->label(__('filament.actions.restore_selected')),
                ForceDeleteBulkAction::make()->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordUrl(fn (AcademicSubscription $record): string => ViewAcademicSubscription::getUrl([$record->id]));
    }

    protected static function getTypeSpecificInfolistSections(): array
    {
        return [
            static::getSubscriptionStatusAndDatesSection(),

            Section::make('معلومات الاشتراك')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('subscription_code')->label('رمز الاشتراك'),
                            TextEntry::make('package.name')->label('اسم الباقة'),
                            TextEntry::make('student.name')->label('الطالب'),
                            TextEntry::make('teacher.user.name')->label('المعلم'),
                            TextEntry::make('subject.name')->label('المادة'),
                            TextEntry::make('gradeLevel.name')->label('المستوى الدراسي'),
                        ]),
                ]),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components(array_merge(
                static::getTypeSpecificInfolistSections(),
                [static::getExtensionHistoryInfolistSection()]
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcademicSubscriptions::route('/'),
            'create' => CreateAcademicSubscription::route('/create'),
            'view' => ViewAcademicSubscription::route('/{record}'),
            'edit' => EditAcademicSubscription::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', SessionSubscriptionStatus::PENDING->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', SessionSubscriptionStatus::PENDING->value)->count() > 0 ? 'warning' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('filament.tabs.pending');
    }
}
