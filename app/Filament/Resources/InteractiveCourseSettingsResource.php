<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveCourseSettingsResource\Pages;
use App\Models\InteractiveCourseSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InteractiveCourseSettingsResource extends Resource
{
    protected static ?string $model = InteractiveCourseSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'إعدادات الدورات التفاعلية';
    protected static ?string $navigationGroup = 'القسم الأكاديمي';
    protected static ?string $modelLabel = 'إعدادات الدورات التفاعلية';
    protected static ?string $pluralModelLabel = 'إعدادات الدورات التفاعلية';

    public static function getEloquentQuery(): Builder
    {
        $academyId = auth()->user()->academy_id ?? 1;
        
        // Ensure settings exist for the academy
        InteractiveCourseSettings::getForAcademy($academyId);
        
        return parent::getEloquentQuery()->forAcademy($academyId);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('الإعدادات المالية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('default_teacher_payment_type')
                                    ->label('نوع دفع المعلم الافتراضي')
                                    ->options([
                                        'fixed' => 'مبلغ ثابت',
                                        'per_student' => 'لكل طالب',
                                        'per_session' => 'لكل جلسة',
                                    ])
                                    ->default('fixed')
                                    ->required(),

                                Forms\Components\TextInput::make('min_teacher_payment')
                                    ->label('الحد الأدنى لدفع المعلم (ريال)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(100)
                                    ->prefix('SAR'),

                                Forms\Components\TextInput::make('max_discount_percentage')
                                    ->label('أقصى نسبة خصم (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(20)
                                    ->suffix('%'),
                            ]),
                    ]),

                Forms\Components\Section::make('الإعدادات الأكاديمية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('min_course_duration_weeks')
                                    ->label('أقل مدة للدورة (أسابيع)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(52)
                                    ->default(4)
                                    ->suffix('أسبوع'),

                                Forms\Components\TextInput::make('max_students_per_course')
                                    ->label('أقصى عدد طلاب لكل دورة')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->default(30)
                                    ->suffix('طالب'),

                                Forms\Components\TextInput::make('require_attendance_minimum')
                                    ->label('الحد الأدنى للحضور المطلوب (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(75)
                                    ->suffix('%')
                                    ->helperText('الحد الأدنى لنسبة الحضور المطلوبة لاجتياز الدورة'),
                            ]),

                        Forms\Components\Toggle::make('auto_create_sessions')
                            ->label('إنشاء الجلسات تلقائياً')
                            ->helperText('إنشاء جدول الجلسات تلقائياً عند إنشاء الدورة')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('الإعدادات التقنية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('auto_create_google_meet')
                                    ->label('إنشاء روابط Google Meet تلقائياً')
                                    ->helperText('إنشاء رابط Google Meet لكل جلسة تلقائياً')
                                    ->default(true),

                                Forms\Components\Toggle::make('send_reminder_notifications')
                                    ->label('إرسال تذكيرات الجلسات')
                                    ->helperText('إرسال إشعارات تذكير للطلاب والمعلمين قبل الجلسات')
                                    ->default(true),
                            ]),

                        Forms\Components\Toggle::make('certificate_auto_generation')
                            ->label('إنشاء الشهادات تلقائياً')
                            ->helperText('إنشاء شهادات إتمام الدورة تلقائياً عند النجاح')
                            ->default(false),
                    ]),
            ])
            ->statePath('data');
    }

    public static function table(Table $table): Table
    {
        return $table->paginated(false);
    }

    // Disable default CRUD operations since this is settings management
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInteractiveCourseSettings::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return null; // No badge needed for settings
    }
}
