<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranTrialRequestResource\Pages;
use App\Models\QuranTrialRequest;
use App\Models\QuranTeacherProfile;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Services\AcademyContextService;
use App\Traits\ScopedToAcademy;

class QuranTrialRequestResource extends BaseResource
{
    use ScopedToAcademy;
    
    protected static ?string $model = QuranTrialRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $modelLabel = 'طلب جلسة تجريبية';

    protected static ?string $pluralModelLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الطلب')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('request_code')
                                    ->label('رقم الطلب')
                                    ->disabled()
                                    ->dehydrated(false),

                                Select::make('status')
                                    ->label('حالة الطلب')
                                    ->options(QuranTrialRequest::STATUSES)
                                    ->required()
                                    ->native(false),
                            ])
                    ]),

                Section::make('معلومات الطالب')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('student_id')
                                    ->label('الطالب')
                                    ->relationship('student', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('teacher_id')
                                    ->label('المعلم')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();
                                        return QuranTeacherProfile::where('academy_id', $academyId)
                                            ->where('is_active', true)
                                            ->where('approval_status', 'approved')
                                            ->get()
                                            ->pluck('display_name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('student_name')
                                    ->label('اسم الطالب')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('student_age')
                                    ->label('عمر الطالب')
                                    ->numeric()
                                    ->minValue(5)
                                    ->maxValue(100),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20),

                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('تفاصيل التعلم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('current_level')
                                    ->label('المستوى الحالي')
                                    ->options(QuranTrialRequest::LEVELS)
                                    ->required()
                                    ->native(false),

                                Select::make('preferred_time')
                                    ->label('الوقت المفضل')
                                    ->options(QuranTrialRequest::TIMES)
                                    ->native(false),
                            ]),

                        Textarea::make('notes')
                            ->label('ملاحظات الطالب')
                            ->rows(3),
                    ]),

                Section::make('استجابة المعلم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('scheduled_at')
                                    ->label('موعد الجلسة المجدولة')
                                    ->native(false),

                                DateTimePicker::make('responded_at')
                                    ->label('تاريخ الرد')
                                    ->native(false)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('meeting_link')
                                    ->label('رابط الاجتماع')
                                    ->url()
                                    ->maxLength(255),

                                TextInput::make('meeting_password')
                                    ->label('كلمة مرور الاجتماع')
                                    ->maxLength(50),
                            ]),

                        Textarea::make('teacher_response')
                            ->label('رد المعلم')
                            ->rows(3),
                    ]),

                Section::make('تقييم الجلسة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('rating')
                                    ->label('التقييم')
                                    ->options([
                                        1 => '1 - ضعيف',
                                        2 => '2 - مقبول', 
                                        3 => '3 - جيد',
                                        4 => '4 - جيد جداً',
                                        5 => '5 - ممتاز'
                                    ])
                                    ->native(false),

                                DateTimePicker::make('completed_at')
                                    ->label('تاريخ اكتمال الجلسة')
                                    ->native(false)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Textarea::make('feedback')
                            ->label('ملاحظات الجلسة')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_code')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('teacher.full_name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student_name')
                    ->label('اسم الطالب')
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::STATUSES[$state] ?? $state)
                    ->colors([
                        'warning' => 'pending',
                        'success' => ['approved', 'scheduled', 'completed'],
                        'danger' => ['rejected', 'cancelled', 'no_show'],
                    ]),

                TextColumn::make('current_level')
                    ->label('المستوى')
                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::LEVELS[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                TextColumn::make('scheduled_at')
                    ->label('موعد الجلسة')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        return str_repeat('⭐', $state) . " ({$state}/5)";
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(QuranTrialRequest::STATUSES),

                SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $academyId = AcademyContextService::getCurrentAcademyId();
                        return QuranTeacherProfile::where('academy_id', $academyId)
                            ->where('is_active', true)
                            ->get()
                            ->pluck('display_name', 'id');
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('current_level')
                    ->label('المستوى')
                    ->options(QuranTrialRequest::LEVELS),

                Filter::make('scheduled_date')
                    ->form([
                        DatePicker::make('scheduled_from')
                            ->label('من تاريخ'),
                        DatePicker::make('scheduled_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '>=', $date),
                            )
                            ->when(
                                $data['scheduled_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('approve')
                        ->label('موافقة')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (QuranTrialRequest $record) => $record->isPending())
                        ->form([
                            Textarea::make('teacher_response')
                                ->label('رد المعلم')
                                ->placeholder('رسالة للطالب...')
                                ->rows(3),
                        ])
                        ->action(function (QuranTrialRequest $record, array $data) {
                            $record->approve($data['teacher_response'] ?? null);
                        })
                        ->successNotificationTitle('تم قبول الطلب بنجاح'),

                    Tables\Actions\Action::make('reject')
                        ->label('رفض')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (QuranTrialRequest $record) => $record->isPending())
                        ->form([
                            Textarea::make('teacher_response')
                                ->label('سبب الرفض')
                                ->placeholder('سبب رفض الطلب...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (QuranTrialRequest $record, array $data) {
                            $record->reject($data['teacher_response']);
                        })
                        ->successNotificationTitle('تم رفض الطلب'),

                    Tables\Actions\Action::make('schedule')
                        ->label('جدولة')
                        ->icon('heroicon-o-calendar')
                        ->color('warning')
                        ->visible(fn (QuranTrialRequest $record) => $record->canBeScheduled())
                        ->form([
                            DateTimePicker::make('scheduled_at')
                                ->label('موعد الجلسة')
                                ->required()
                                ->native(false)
                                ->minDate(now()),
                            
                            TextInput::make('meeting_link')
                                ->label('رابط الاجتماع')
                                ->url()
                                ->placeholder('https://meet.google.com/xxx-xxx-xxx'),
                            
                            TextInput::make('meeting_password')
                                ->label('كلمة مرور الاجتماع (اختياري)')
                                ->placeholder('كلمة المرور'),
                        ])
                        ->action(function (QuranTrialRequest $record, array $data) {
                            $record->schedule(
                                $data['scheduled_at'],
                                $data['meeting_link'] ?? null,
                                $data['meeting_password'] ?? null
                            );
                        })
                        ->successNotificationTitle('تم جدولة الجلسة بنجاح'),

                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الطلب')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('request_code')
                                    ->label('رقم الطلب')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('الحالة')
                                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::STATUSES[$state] ?? $state)
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'approved', 'scheduled', 'completed' => 'success',
                                        'rejected', 'cancelled', 'no_show' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('تاريخ الطلب')
                                    ->dateTime(),

                                Infolists\Components\TextEntry::make('responded_at')
                                    ->label('تاريخ الرد')
                                    ->dateTime(),
                            ])
                    ]),

                Infolists\Components\Section::make('معلومات الطالب والمعلم')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب المسجل'),

                                Infolists\Components\TextEntry::make('teacher.full_name')
                                    ->label('المعلم'),

                                Infolists\Components\TextEntry::make('student_name')
                                    ->label('اسم الطالب في الطلب'),

                                Infolists\Components\TextEntry::make('student_age')
                                    ->label('عمر الطالب')
                                    ->suffix(' سنة'),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('رقم الهاتف'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('البريد الإلكتروني'),
                            ])
                    ]),

                Infolists\Components\Section::make('تفاصيل التعلم')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('current_level')
                                    ->label('المستوى الحالي')
                                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::LEVELS[$state] ?? $state),

                                Infolists\Components\TextEntry::make('preferred_time')
                                    ->label('الوقت المفضل')
                                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::TIMES[$state] ?? $state),
                            ]),

                        Infolists\Components\TextEntry::make('learning_goals')
                            ->label('أهداف التعلم')
                            ->listWithLineBreaks()
                            ->formatStateUsing(function ($state) {
                                if (!is_array($state)) return '-';
                                $goals = [
                                    'reading' => 'تعلم القراءة الصحيحة',
                                    'tajweed' => 'تعلم أحكام التجويد',
                                    'memorization' => 'حفظ القرآن الكريم',
                                    'improvement' => 'تحسين الأداء والإتقان'
                                ];
                                return collect($state)->map(fn ($goal) => $goals[$goal] ?? $goal)->toArray();
                            }),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('ملاحظات الطالب')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('تفاصيل الجلسة')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->dateTime(),

                                Infolists\Components\TextEntry::make('meeting_link')
                                    ->label('رابط الاجتماع')
                                    ->url(fn ($record) => $record->meeting_link)
                                    ->openUrlInNewTab(),

                                Infolists\Components\TextEntry::make('meeting_password')
                                    ->label('كلمة مرور الاجتماع'),

                                Infolists\Components\TextEntry::make('rating')
                                    ->label('التقييم')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return '-';
                                        return str_repeat('⭐', $state) . " ({$state}/5)";
                                    }),
                            ]),

                        Infolists\Components\TextEntry::make('teacher_response')
                            ->label('رد المعلم')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('feedback')
                            ->label('ملاحظات الجلسة')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranTrialRequests::route('/'),
            'create' => Pages\CreateQuranTrialRequest::route('/create'),
            'view' => Pages\ViewQuranTrialRequest::route('/{record}'),
            'edit' => Pages\EditQuranTrialRequest::route('/{record}/edit'),
        ];
    }

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }
}