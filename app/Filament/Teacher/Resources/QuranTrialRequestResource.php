<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\QuranTrialRequestResource\Pages;
use App\Models\QuranTrialRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class QuranTrialRequestResource extends Resource
{
    protected static ?string $model = QuranTrialRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $modelLabel = 'طلب جلسة تجريبية';

    protected static ?string $pluralModelLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $navigationGroup = 'طلبات القرآن';

    protected static ?int $navigationSort = 1;

    // Scope to only the current teacher's trial requests
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        return parent::getEloquentQuery()
            ->where('teacher_id', $user->quranTeacherProfile->id)
            ->where('academy_id', $user->academy_id);
    }

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
                                    ->options([
                                        'pending' => 'في الانتظار',
                                        'approved' => 'موافق عليه',
                                        'scheduled' => 'مجدول',
                                        'completed' => 'مكتمل',
                                        'rejected' => 'مرفوض',
                                        'cancelled' => 'ملغي',
                                        'no_show' => 'لم يحضر',
                                    ])
                                    ->required()
                                    ->native(false),
                            ])
                    ]),

                Section::make('معلومات الطالب')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('student_name')
                                    ->label('اسم الطالب')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('student_age')
                                    ->label('عمر الطالب')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix(' سنة'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('current_level')
                                    ->label('المستوى الحالي')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::LEVELS[$state] ?? $state),

                                TextInput::make('preferred_time')
                                    ->label('الوقت المفضل')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::TIMES[$state] ?? $state),
                            ]),

                        Textarea::make('notes')
                            ->label('ملاحظات الطالب')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2),
                    ]),

                Section::make('جدولة الجلسة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->native(false)
                                    ->required(fn ($get) => in_array($get('status'), ['scheduled', 'completed'])),

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
                                    ->maxLength(255)
                                    ->placeholder('https://meet.google.com/xxx-xxx-xxx'),

                                TextInput::make('meeting_password')
                                    ->label('كلمة مرور الاجتماع')
                                    ->maxLength(50),
                            ]),

                        Textarea::make('teacher_response')
                            ->label('ردك على الطلب')
                            ->rows(3)
                            ->placeholder('اكتب ردك أو ملاحظاتك للطالب...'),
                    ]),

                Section::make('تقييم الجلسة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('rating')
                                    ->label('تقييم الطالب')
                                    ->options([
                                        1 => '⭐ ضعيف',
                                        2 => '⭐⭐ مقبول',
                                        3 => '⭐⭐⭐ جيد',
                                        4 => '⭐⭐⭐⭐ ممتاز',
                                        5 => '⭐⭐⭐⭐⭐ استثنائي',
                                    ])
                                    ->native(false),

                                Select::make('converted_to_subscription')
                                    ->label('تحويل لاشتراك')
                                    ->options([
                                        false => 'لا',
                                        true => 'نعم',
                                    ])
                                    ->boolean()
                                    ->native(false),
                            ]),

                        Textarea::make('feedback')
                            ->label('ملاحظات الجلسة')
                            ->rows(3)
                            ->placeholder('اكتب ملاحظاتك حول أداء الطالب والجلسة...'),
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

                TextColumn::make('student_name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->toggleable(),

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

                TextColumn::make('preferred_time')
                    ->label('الوقت المفضل')
                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::TIMES[$state] ?? $state)
                    ->toggleable(),

                TextColumn::make('scheduled_at')
                    ->label('موعد الجلسة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        return str_repeat('⭐', $state) . " ({$state}/5)";
                    })
                    ->toggleable(),

                BadgeColumn::make('converted_to_subscription')
                    ->label('تحويل لاشتراك')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نعم' : 'لا')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ])
                    ->toggleable(),

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

                SelectFilter::make('current_level')
                    ->label('المستوى')
                    ->options(QuranTrialRequest::LEVELS),

                SelectFilter::make('converted_to_subscription')
                    ->label('تحويل لاشتراك')
                    ->options([
                        true => 'نعم',
                        false => 'لا',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('schedule')
                        ->label('جدولة الجلسة')
                        ->icon('heroicon-o-calendar')
                        ->color('primary')
                        ->visible(fn (QuranTrialRequest $record) => in_array($record->status, ['pending', 'approved']))
                        ->url(fn (QuranTrialRequest $record) => route('teacher.schedule.trial.show', [
                            'subdomain' => Auth::user()->academy->subdomain,
                            'trialRequest' => $record->id
                        ])),

                    Tables\Actions\Action::make('meeting_link')
                        ->label('إدارة رابط الاجتماع')
                        ->icon('heroicon-o-video-camera')
                        ->color('info')
                        ->visible(fn (QuranTrialRequest $record) => in_array($record->status, ['scheduled', 'completed']))
                        ->modalHeading('إدارة رابط الاجتماع')
                        ->modalContent(fn (QuranTrialRequest $record) => view('components.meetings.link-manager', [
                            'trialRequestId' => $record->id,
                            'currentLink' => $record->meeting_link,
                            'currentPassword' => $record->meeting_password,
                            'academySubdomain' => Auth::user()->academy->subdomain,
                            'mode' => 'ajax'
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('تعيين كمكتمل')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'completed']);
                            });
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('تم تعيين الطلبات المحددة كمكتملة'),
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

                Infolists\Components\Section::make('معلومات الطالب')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب المسجل'),

                                Infolists\Components\TextEntry::make('student_name')
                                    ->label('اسم الطالب في الطلب'),

                                Infolists\Components\TextEntry::make('student_age')
                                    ->label('عمر الطالب')
                                    ->suffix(' سنة'),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('رقم الهاتف'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('البريد الإلكتروني'),

                                Infolists\Components\TextEntry::make('current_level')
                                    ->label('المستوى الحالي')
                                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::LEVELS[$state] ?? $state),
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
                            ->label('ردك على الطلب')
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
            'view' => Pages\ViewQuranTrialRequest::route('/{record}'),
            'edit' => Pages\EditQuranTrialRequest::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Teachers cannot create trial requests, only respond to them
    }
}