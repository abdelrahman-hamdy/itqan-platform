<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\TeacherVideoSettingsResource\Pages;
use App\Models\TeacherVideoSettings;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;

class TeacherVideoSettingsResource extends Resource
{
    protected static ?string $model = TeacherVideoSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'إعدادات الفيديو الشخصية';
    protected static ?string $modelLabel = 'إعدادات الفيديو';
    protected static ?string $pluralModelLabel = 'إعدادات الفيديو الشخصية';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canViewAny(): bool { return true; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Teacher Video Settings')->tabs([
                
                Tabs\Tab::make('التفضيلات الأساسية')->schema([
                    Section::make('إعدادات الجودة والأداء')
                        ->description('تخصيص إعدادات الجودة حسب تفضيلاتك (اتركها فارغة لاستخدام إعدادات الأكاديمية)')
                        ->schema([
                        Grid::make(3)->schema([
                            Forms\Components\Select::make('preferred_video_quality')
                                ->label('جودة الفيديو المفضلة')
                                ->options(['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'])
                                ->placeholder('استخدام إعداد الأكاديمية'),
                            Forms\Components\Select::make('preferred_audio_quality')
                                ->label('جودة الصوت المفضلة')
                                ->options(['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'])
                                ->placeholder('استخدام إعداد الأكاديمية'),
                            Forms\Components\TextInput::make('preferred_max_participants')
                                ->label('الحد الأقصى للمشاركين')
                                ->numeric()->placeholder('استخدام إعداد الأكاديمية'),
                        ]),
                    ]),
                    
                    Section::make('السلوك الافتراضي للاجتماعات')->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('auto_start_recording')
                                ->label('بدء التسجيل تلقائياً')
                                ->helperText('بدء تسجيل الجلسات تلقائياً عند البداية'),
                            Forms\Components\Toggle::make('mute_participants_on_join')
                                ->label('كتم المشاركين عند الدخول')
                                ->helperText('كتم مايكروفون الطلاب عند دخولهم'),
                        ]),
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('enable_waiting_room')
                                ->label('تفعيل غرفة الانتظار')
                                ->helperText('المشاركون ينتظرون موافقتك للدخول'),
                            Forms\Components\Toggle::make('disable_camera_on_join')
                                ->label('إغلاق الكاميرا عند الدخول')
                                ->helperText('إغلاق كاميرات الطلاب عند دخولهم'),
                        ]),
                    ]),
                ]),
                
                Tabs\Tab::make('إدارة الطلاب')->schema([
                    Section::make('صلاحيات الطلاب')->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('allow_student_unmute')
                                ->label('السماح للطلاب بإلغاء الكتم')
                                ->default(true),
                            Forms\Components\Toggle::make('allow_student_camera')
                                ->label('السماح للطلاب بتشغيل الكاميرا')
                                ->default(true),
                        ]),
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('allow_student_screen_sharing')
                                ->label('السماح للطلاب بمشاركة الشاشة')
                                ->default(false),
                            Forms\Components\Toggle::make('auto_admit_known_students')
                                ->label('قبول الطلاب المعروفين تلقائياً')
                                ->default(true),
                        ]),
                    ]),
                ]),
                
                Tabs\Tab::make('الإشعارات')->schema([
                    Section::make('تفضيلات الإشعارات')->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('notify_before_session')
                                ->label('إشعار قبل الجلسة')
                                ->default(true),
                            Forms\Components\TextInput::make('notification_minutes_before')
                                ->label('الإشعار قبل الجلسة (دقائق)')
                                ->numeric()->default(15)->suffix('دقيقة'),
                        ]),
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('notify_on_late_student')
                                ->label('إشعار عند تأخر الطالب')
                                ->default(true),
                            Forms\Components\Toggle::make('notify_on_session_end')
                                ->label('إشعار انتهاء الجلسة')
                                ->default(false),
                        ]),
                    ]),
                ]),
                
                Tabs\Tab::make('التسجيل والتقارير')->schema([
                    Section::make('إعدادات التسجيل')->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('always_record_sessions')
                                ->label('تسجيل جميع الجلسات')
                                ->helperText('تسجيل جميع الجلسات تلقائياً'),
                            Forms\Components\Select::make('recording_quality_preference')
                                ->label('جودة التسجيل المفضلة')
                                ->options(['standard' => 'قياسية', 'high' => 'عالية', 'ultra' => 'فائقة'])
                                ->default('standard'),
                        ]),
                        Forms\Components\Toggle::make('include_chat_in_recording')
                            ->label('تضمين الدردشة في التسجيل')
                            ->default(true),
                    ]),
                    
                    Section::make('التقارير والتحليلات')->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('track_student_attendance')
                                ->label('تتبع حضور الطلاب')
                                ->default(true),
                            Forms\Components\Toggle::make('generate_session_reports')
                                ->label('إنشاء تقارير الجلسات')
                                ->default(true),
                        ]),
                        Forms\Components\Toggle::make('share_reports_with_parents')
                            ->label('مشاركة التقارير مع أولياء الأمور')
                            ->default(false),
                    ]),
                ]),
                
                Tabs\Tab::make('الجدولة الشخصية')->schema([
                    Section::make('أوقات العمل المفضلة')
                        ->description('تحديد الأوقات المفضلة لديك (اختياري - يمكن أن تكون أكثر تقييداً من إعدادات الأكاديمية)')
                        ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TimePicker::make('preferred_earliest_time')
                                ->label('أقرب وقت عمل مفضل'),
                            Forms\Components\TimePicker::make('preferred_latest_time')
                                ->label('أبعد وقت عمل مفضل'),
                        ]),
                        
                        Forms\Components\TextInput::make('break_minutes_between_sessions')
                            ->label('فترة الراحة بين الجلسات (دقائق)')
                            ->numeric()->default(5)->suffix('دقيقة'),
                            
                        Forms\Components\CheckboxList::make('unavailable_days')
                            ->label('أيام عدم التوفر الإضافية')
                            ->helperText('الأيام التي لا تريد العمل فيها (إضافة لإعدادات الأكاديمية)')
                            ->options([
                                0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء',
                                3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
                            ])
                            ->columns(3),
                    ]),
                ]),
                
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTeacherVideoSettings::route('/'),
        ];
    }
}
