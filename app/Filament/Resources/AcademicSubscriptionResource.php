<?php

namespace App\Filament\Resources;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\UserType;
use App\Filament\Concerns\HasCrossAcademyAccess;
use App\Filament\Resources\AcademicSubscriptionResource\Pages;
use App\Filament\Shared\Resources\BaseSubscriptionResource;
use App\Models\AcademicSubscription;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSubscriptionResource extends BaseSubscriptionResource
{
    use HasCrossAcademyAccess;

    protected static ?string $model = AcademicSubscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'الاشتراكات الأكاديمية';
    protected static ?string $modelLabel = 'اشتراك أكاديمي';
    protected static ?string $pluralModelLabel = 'الاشتراكات الأكاديمية';
    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';
    protected static ?int $navigationSort = 2;

    protected static function getBasicInfoFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('معلومات الاشتراك الأساسية')
            ->schema([
                Forms\Components\Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->label('الأكاديمية')
                    ->required()
                    ->disabled()
                    ->default(fn () => auth()->user()->academy_id),

                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(function (string $search) {
                        $academyId = \App\Services\AcademyContextService::getCurrentAcademyId();

                        return \App\Models\User::where('user_type', UserType::STUDENT->value)
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
                        $user = \App\Models\User::with('studentProfile')->find($value);

                        return $user?->studentProfile?->display_name ?? $user?->name ?? 'طالب #'.$value;
                    })
                    ->required(),

                Forms\Components\Select::make('teacher_id')
                    ->label('المعلم')
                    ->required()
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(function (string $search) {
                        $academyId = \App\Services\AcademyContextService::getCurrentAcademyId();

                        return \App\Models\AcademicTeacherProfile::with('user')
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
                        $teacher = \App\Models\AcademicTeacherProfile::with('user')->find($value);

                        return $teacher?->user?->name ?? $teacher?->full_name ?? 'معلم #'.$value;
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $set('subject_id', null);
                        $set('grade_level_id', null);
                    }),

                Forms\Components\Select::make('subject_id')
                    ->label('المادة الدراسية')
                    ->required()
                    ->searchable()
                    ->options(function (Forms\Get $get) {
                        $teacherId = $get('teacher_id');
                        if ($teacherId) {
                            $teacher = \App\Models\AcademicTeacherProfile::find($teacherId);
                            if ($teacher) {
                                return $teacher->subjects->pluck('name', 'id')->toArray();
                            }
                        }

                        return \App\Models\AcademicSubject::pluck('name', 'id')->toArray();
                    })
                    ->getOptionLabelUsing(function ($value, $record) {
                        if ($record?->subject_name) {
                            return $record->subject_name;
                        }
                        $subject = \App\Models\AcademicSubject::find($value);

                        return $subject?->name ?? 'مادة #'.$value;
                    })
                    ->live(),

                Forms\Components\Select::make('grade_level_id')
                    ->label('المرحلة الدراسية')
                    ->required()
                    ->searchable()
                    ->options(function (Forms\Get $get) {
                        $teacherId = $get('teacher_id');
                        if ($teacherId) {
                            $teacher = \App\Models\AcademicTeacherProfile::find($teacherId);
                            if ($teacher) {
                                return $teacher->gradeLevels->pluck('name', 'id')->toArray();
                            }
                        }

                        return \App\Models\AcademicGradeLevel::pluck('name', 'id')->toArray();
                    })
                    ->getOptionLabelUsing(function ($value, $record) {
                        if ($record?->grade_level_name) {
                            return $record->grade_level_name;
                        }
                        $gradeLevel = \App\Models\AcademicGradeLevel::find($value);

                        return $gradeLevel?->name ?? 'مرحلة #'.$value;
                    })
                    ->live(),

                Forms\Components\Select::make('academic_package_id')
                    ->label('الباقة الأكاديمية')
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(function (string $search) {
                        $academyId = \App\Services\AcademyContextService::getCurrentAcademyId();

                        return \App\Models\AcademicPackage::query()
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
                        $package = \App\Models\AcademicPackage::find($value);

                        return $package?->name ?? 'باقة #'.$value;
                    }),
            ])->columns(2);
    }

    protected static function getPricingFormSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('تفاصيل الاشتراك')
            ->schema([
                Forms\Components\TextInput::make('subscription_code')
                    ->label('رمز الاشتراك')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('billing_cycle')
                    ->label('دورة الفوترة')
                    ->options([
                        'monthly' => 'شهرياً',
                        'quarterly' => 'كل 3 شهور',
                        'yearly' => 'سنوياً',
                    ])
                    ->default('monthly')
                    ->required(),

                Forms\Components\TextInput::make('final_monthly_amount')
                    ->label('سعر الاشتراك الشهري')
                    ->numeric()
                    ->suffix(getCurrencySymbol())
                    ->helperText('السعر النهائي بعد الخصم'),
            ])->columns(3);
    }

    protected static function getSchedulingSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('جدولة الجلسات')
            ->schema([
                Forms\Components\TextInput::make('sessions_per_week')
                    ->label('عدد الجلسات أسبوعياً')
                    ->numeric()
                    ->default(2)
                    ->minValue(1)
                    ->maxValue(7)
                    ->helperText('عدد الجلسات المجدولة كل أسبوع'),

                Forms\Components\TextInput::make('sessions_per_month')
                    ->label('عدد الجلسات شهرياً')
                    ->numeric()
                    ->disabled()
                    ->helperText('إجمالي الجلسات المتوقعة في الشهر (محسوب تلقائياً)'),
            ])->columns(2);
    }

    protected static function getDatesPaymentSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('التواريخ والدفع')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البدء')
                            ->default(now())
                            ->required()
                            ->live(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ الانتهاء')
                            ->after('start_date')
                            ->helperText('يمكنك تعديل التاريخ يدوياً أو استخدام زر "تمديد الاشتراك" لإضافة أيام محددة'),

                        Forms\Components\DatePicker::make('next_billing_date')
                            ->label('تاريخ الفوترة التالي'),
                    ]),

                Forms\Components\Select::make('status')
                    ->label('حالة الاشتراك')
                    ->options(SessionSubscriptionStatus::options())
                    ->default(SessionSubscriptionStatus::ACTIVE->value)
                    ->required()
                    ->helperText('ملاحظة: تمديد الاشتراك يقوم تلقائياً بتفعيله'),

                Forms\Components\Select::make('payment_status')
                    ->label('حالة الدفع')
                    ->options(SubscriptionPaymentStatus::options())
                    ->default(SubscriptionPaymentStatus::PENDING->value)
                    ->required(),

                static::getAutoRenewToggle(),
            ])->columns(3);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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

            Tables\Columns\TextColumn::make('teacher.user.name')
                ->label('المعلم')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('subject.name')
                ->label('المادة')
                ->searchable(),

            Tables\Columns\TextColumn::make('gradeLevel.name')
                ->label('المرحلة')
                ->searchable(),

            Tables\Columns\TextColumn::make('final_monthly_amount')
                ->label('المبلغ الشهري')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->sortable(),

            Tables\Columns\TextColumn::make('start_date')
                ->label('تاريخ البدء')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('next_billing_date')
                ->label('الفوترة التالية')
                ->date()
                ->sortable(),
        ];
    }

    protected static function getTypeSpecificFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('subject_id')
                ->label(__('filament.course.subject'))
                ->relationship('subject', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('teacher_id')
                ->label(__('filament.teacher'))
                ->relationship('teacher.user', 'name')
                ->searchable()
                ->preload(),
        ];
    }

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['student', 'teacher.user', 'subject', 'gradeLevel', 'academy']);
    }

    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            static::getCancelPendingAction(),
            static::getPauseAction(),
            static::getResumeAction(),
            static::getExtendSubscriptionAction(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make()->label(__('filament.actions.restore')),
            Tables\Actions\ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                static::getBulkCancelPendingAction(),
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->recordUrl(fn (AcademicSubscription $record): string => Pages\ViewAcademicSubscription::getUrl([$record->id]));
    }

    protected static function getTypeSpecificInfolistSections(): array
    {
        return [
            Infolists\Components\Section::make('معلومات الاشتراك')
                ->schema([
                    Infolists\Components\Grid::make(2)
                        ->schema([
                            Infolists\Components\TextEntry::make('subscription_code')->label('رمز الاشتراك'),
                            Infolists\Components\TextEntry::make('package.name')->label('اسم الباقة'),
                            Infolists\Components\TextEntry::make('student.name')->label('الطالب'),
                            Infolists\Components\TextEntry::make('teacher.user.name')->label('المعلم'),
                            Infolists\Components\TextEntry::make('status')
                                ->label('حالة الاشتراك')
                                ->badge()
                                ->formatStateUsing(function (mixed $state): string {
                                    if ($state instanceof SessionSubscriptionStatus) {
                                        return match ($state) {
                                            SessionSubscriptionStatus::PENDING => 'قيد الانتظار',
                                            SessionSubscriptionStatus::ACTIVE => 'نشط',
                                            SessionSubscriptionStatus::PAUSED => 'متوقف مؤقتاً',
                                            SessionSubscriptionStatus::CANCELLED => 'ملغي',
                                        };
                                    }

                                    return match ($state) {
                                        'pending' => 'قيد الانتظار',
                                        'active' => 'نشط',
                                        'paused' => 'متوقف مؤقتاً',
                                        'cancelled' => 'ملغي',
                                        default => (string) $state,
                                    };
                                })
                                ->color(function (mixed $state): string {
                                    if ($state instanceof SessionSubscriptionStatus) {
                                        return match ($state) {
                                            SessionSubscriptionStatus::ACTIVE => 'success',
                                            SessionSubscriptionStatus::PENDING => 'warning',
                                            SessionSubscriptionStatus::PAUSED => 'info',
                                            SessionSubscriptionStatus::CANCELLED => 'danger',
                                        };
                                    }

                                    return match ($state) {
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'paused' => 'info',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    };
                                }),
                            Infolists\Components\TextEntry::make('payment_status')
                                ->label('حالة الدفع')
                                ->badge()
                                ->formatStateUsing(function (mixed $state): string {
                                    if ($state instanceof SubscriptionPaymentStatus) {
                                        return match ($state) {
                                            SubscriptionPaymentStatus::PENDING => 'في الانتظار',
                                            SubscriptionPaymentStatus::PAID => 'مدفوع',
                                            SubscriptionPaymentStatus::FAILED => 'فشل',
                                        };
                                    }

                                    return match ($state) {
                                        'pending' => 'في الانتظار',
                                        'paid' => 'مدفوع',
                                        'failed' => 'فشل',
                                        default => (string) $state,
                                    };
                                })
                                ->color(function (mixed $state): string {
                                    if ($state instanceof SubscriptionPaymentStatus) {
                                        return match ($state) {
                                            SubscriptionPaymentStatus::PAID => 'success',
                                            SubscriptionPaymentStatus::PENDING => 'warning',
                                            SubscriptionPaymentStatus::FAILED => 'danger',
                                        };
                                    }

                                    return match ($state) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    };
                                }),
                        ]),
                ]),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema(array_merge(
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
            'index' => Pages\ListAcademicSubscriptions::route('/'),
            'create' => Pages\CreateAcademicSubscription::route('/create'),
            'view' => Pages\ViewAcademicSubscription::route('/{record}'),
            'edit' => Pages\EditAcademicSubscription::route('/{record}/edit'),
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
