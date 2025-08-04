<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuperAdminQuranTeacherResource\Pages;
use App\Models\QuranTeacherProfile;
use App\Models\Academy;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\SelectFilter;

class SuperAdminQuranTeacherResource extends Resource
{
    protected static ?string $model = QuranTeacherProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'معلمو القرآن (عالمي)';

    protected static ?string $modelLabel = 'معلم قرآن';

    protected static ?string $pluralModelLabel = 'معلمو القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 1;

    // NO ACADEMY SCOPING - Show all teachers across all academies

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('academy_id')
                                    ->label('الأكاديمية')
                                    ->options(Academy::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Select::make('approval_status')
                                    ->label('حالة الموافقة')
                                    ->options([
                                        'pending' => 'في الانتظار',
                                        'approved' => 'معتمد',
                                        'rejected' => 'مرفوض',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('last_name')
                                    ->label('الاسم الأخير')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->maxLength(20),
                            ]),

                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                    ]),

                Section::make('المؤهلات والخبرة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('educational_qualification')
                                    ->label('المؤهل التعليمي')
                                    ->options([
                                        'bachelor' => 'بكالوريوس',
                                        'master' => 'ماجستير',
                                        'phd' => 'دكتوراه',
                                        'ijazah' => 'إجازة في القرآن',
                                        'diploma' => 'دبلوم',
                                        'other' => 'أخرى',
                                    ])
                                    ->native(false),

                                TextInput::make('years_of_experience')
                                    ->label('سنوات الخبرة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(50),
                            ]),

                        Textarea::make('bio_arabic')
                            ->label('السيرة الذاتية (عربي)')
                            ->rows(3),

                        Textarea::make('bio_english')
                            ->label('السيرة الذاتية (إنجليزي)')
                            ->rows(3),
                    ]),

                Section::make('إعدادات التدريس')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('hourly_rate_individual')
                                    ->label('سعر الساعة (فردي)')
                                    ->numeric()
                                    ->prefix('ر.س'),

                                TextInput::make('hourly_rate_group')
                                    ->label('سعر الساعة (جماعي)')
                                    ->numeric()
                                    ->prefix('ر.س'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=4169E1&color=fff'),

                TextColumn::make('teacher_code')
                    ->label('رمز المعلم')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('full_name')
                    ->label('اسم المعلم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),

                BadgeColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),

                BadgeColumn::make('is_active')
                    ->label('نشط')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشط' : 'غير نشط')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ]),

                TextColumn::make('total_students')
                    ->label('عدد الطلاب')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_sessions')
                    ->label('عدد الجلسات')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        return str_repeat('⭐', round($state)) . " ({$state}/5)";
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->options(Academy::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('approval_status')
                    ->label('حالة الموافقة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),

                SelectFilter::make('educational_qualification')
                    ->label('المؤهل التعليمي')
                    ->options([
                        'bachelor' => 'بكالوريوس',
                        'master' => 'ماجستير',
                        'phd' => 'دكتوراه',
                        'ijazah' => 'إجازة في القرآن',
                        'diploma' => 'دبلوم',
                        'other' => 'أخرى',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('approve')
                        ->label('اعتماد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (QuranTeacherProfile $record) => $record->approval_status === 'pending')
                        ->action(function (QuranTeacherProfile $record) {
                            $record->approve();
                        })
                        ->successNotificationTitle('تم اعتماد المعلم بنجاح'),

                    Tables\Actions\Action::make('reject')
                        ->label('رفض')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (QuranTeacherProfile $record) => $record->approval_status === 'pending')
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label('سبب الرفض')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (QuranTeacherProfile $record, array $data) {
                            $record->reject($data['rejection_reason']);
                        })
                        ->successNotificationTitle('تم رفض طلب المعلم'),

                    Tables\Actions\Action::make('suspend')
                        ->label('إيقاف')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->visible(fn (QuranTeacherProfile $record) => $record->is_active)
                        ->form([
                            Textarea::make('suspension_reason')
                                ->label('سبب الإيقاف')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (QuranTeacherProfile $record, array $data) {
                            $record->suspend($data['suspension_reason']);
                        })
                        ->successNotificationTitle('تم إيقاف المعلم'),

                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('اعتماد المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->approve();
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('تم اعتماد المعلمين المحددين'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('teacher_code')
                                    ->label('رمز المعلم')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('academy.name')
                                    ->label('الأكاديمية')
                                    ->badge()
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('full_name')
                                    ->label('اسم المعلم')
                                    ->weight(FontWeight::Bold),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('رقم الهاتف'),

                                Infolists\Components\TextEntry::make('approval_status')
                                    ->label('حالة الموافقة')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'في الانتظار',
                                        'approved' => 'معتمد',
                                        'rejected' => 'مرفوض',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                    ]),

                Infolists\Components\Section::make('الإحصائيات')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_students')
                                    ->label('إجمالي الطلاب'),

                                Infolists\Components\TextEntry::make('total_sessions')
                                    ->label('إجمالي الجلسات'),

                                Infolists\Components\TextEntry::make('total_reviews')
                                    ->label('عدد التقييمات'),

                                Infolists\Components\TextEntry::make('rating')
                                    ->label('متوسط التقييم')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return 'لا يوجد تقييم';
                                        return str_repeat('⭐', round($state)) . " ({$state}/5)";
                                    }),
                            ])
                    ]),

                Infolists\Components\Section::make('السيرة الذاتية')
                    ->schema([
                        Infolists\Components\TextEntry::make('bio_arabic')
                            ->label('السيرة الذاتية (عربي)')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('bio_english')
                            ->label('السيرة الذاتية (إنجليزي)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuperAdminQuranTeachers::route('/'),
            'view' => Pages\ViewSuperAdminQuranTeacher::route('/{record}'),
            'edit' => Pages\EditSuperAdminQuranTeacher::route('/{record}/edit'),
        ];
    }
}