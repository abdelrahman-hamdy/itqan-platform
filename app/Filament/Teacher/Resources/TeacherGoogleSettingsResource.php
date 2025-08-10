<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\TeacherGoogleSettingsResource\Pages;
use App\Models\GoogleToken;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;

class TeacherGoogleSettingsResource extends Resource
{
    protected static ?string $model = User::class; // Using User model to store teacher preferences

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'إعدادات Google Meet';
    protected static ?string $modelLabel = 'إعدادات Google Meet';
    protected static ?string $pluralModelLabel = 'إعدادات Google Meet';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 20;

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

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('حالة اتصال Google')
                    ->description('إدارة اتصالك مع Google Calendar و Google Meet')
                    ->schema([
                        Placeholder::make('connection_status')
                            ->label('حالة الاتصال')
                            ->content(function () {
                                $user = Auth::user();
                                $token = GoogleToken::where('user_id', $user->id)->active()->first();
                                
                                if ($token) {
                                    return '✅ متصل - يمكنك إنشاء اجتماعات Google Meet تلقائياً';
                                } else {
                                    return '❌ غير متصل - ستستخدم الأكاديمية الحساب الاحتياطي لإنشاء الاجتماعات';
                                }
                            }),
                            
                        Placeholder::make('google_email')
                            ->label('حساب Google المتصل')
                            ->content(function () {
                                $user = Auth::user();
                                return $user->google_email ?? 'لم يتم الربط بعد';
                            }),
                            
                        Placeholder::make('last_connected')
                            ->label('آخر اتصال')
                            ->content(function () {
                                $user = Auth::user();
                                return $user->google_connected_at 
                                    ? $user->google_connected_at->diffForHumans()
                                    : 'لم يتم الاتصال بعد';
                            }),
                            
                        Actions::make([
                            Action::make('connect_google')
                                ->label('ربط حساب Google')
                                ->icon('heroicon-o-link')
                                ->color('success')
                                ->url(function () {
                                    // For local development, use local route
                                    if (config('app.env') === 'local') {
                                        return route('google.auth.local');
                                    }
                                    
                                    // For production, use subdomain-based route
                                    $user = Auth::user();
                                    $subdomain = $user->academy?->subdomain ?? 'itqan-academy';
                                    return route('google.auth', ['subdomain' => $subdomain]);
                                })
                                ->openUrlInNewTab()
                                ->visible(function () {
                                    $user = Auth::user();
                                    $token = GoogleToken::where('user_id', $user->id)->active()->first();
                                    return !$token;
                                }),
                                
                            Action::make('disconnect_google')
                                ->label('قطع اتصال Google')
                                ->icon('heroicon-o-x-mark')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('قطع اتصال Google')
                                ->modalDescription('هذا سيقطع اتصالك مع Google Calendar و Meet. ستعتمد جلساتك على الحساب الاحتياطي للأكاديمية.')
                                ->action(function () {
                                    $user = Auth::user();
                                    GoogleToken::where('user_id', $user->id)->delete();
                                    
                                    $user->update([
                                        'google_id' => null,
                                        'google_email' => null,
                                        'google_connected_at' => null,
                                        'google_calendar_enabled' => false,
                                    ]);
                                })
                                ->visible(function () {
                                    $user = Auth::user();
                                    $token = GoogleToken::where('user_id', $user->id)->active()->first();
                                    return (bool) $token;
                                }),
                        ])->alignCenter(),
                    ]),

                Section::make('تفضيلات الاجتماعات الشخصية')
                    ->description('إعداداتك الشخصية للاجتماعات - ستطبق على جلساتك فقط')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('teacher_auto_record')
                                ->label('تسجيل جلساتي تلقائياً')
                                ->helperText('تسجيل جلسات Google Meet الخاصة بي تلقائياً')
                                ->default(false),
                                
                            Select::make('teacher_default_duration')
                                ->label('المدة الافتراضية لجلساتي (بالدقائق)')
                                ->options([
                                    30 => '30 دقيقة',
                                    45 => '45 دقيقة',
                                    60 => '60 دقيقة (ساعة)',
                                    90 => '90 دقيقة (ساعة ونصف)',
                                    120 => '120 دقيقة (ساعتان)',
                                ])
                                ->default(60),
                        ]),
                        
                        Grid::make(2)->schema([
                            TextInput::make('teacher_meeting_prep_minutes')
                                ->label('وقت التحضير لجلساتي (بالدقائق)')
                                ->numeric()
                                ->default(60)
                                ->minValue(5)
                                ->maxValue(240)
                                ->helperText('كم دقيقة قبل جلستي يتم إنشاء رابط Google Meet'),
                                
                            Toggle::make('teacher_send_reminders')
                                ->label('إرسال تذكيرات لطلابي')
                                ->helperText('إرسال تذكيرات بالبريد الإلكتروني قبل جلساتي')
                                ->default(true),
                        ]),
                        
                        TagsInput::make('teacher_reminder_times')
                            ->label('أوقات التذكير لجلساتي (بالدقائق)')
                            ->default(['60', '15'])
                            ->helperText('كم دقيقة قبل جلستي يتم إرسال التذكير (مثال: 60, 15)')
                            ->nestedRecursiveRules([
                                'min:1',
                                'max:1440',
                                'numeric',
                            ]),
                    ]),

                Section::make('إعدادات التقويم')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('sync_to_google_calendar')
                                ->label('مزامنة جلساتي مع Google Calendar')
                                ->helperText('إضافة جلساتي تلقائياً إلى تقويم Google الشخصي')
                                ->default(true),
                                
                            Toggle::make('allow_calendar_conflicts')
                                ->label('السماح بتداخل الجلسات')
                                ->helperText('السماح بحجز جلسات في أوقات بها أحداث أخرى في تقويمي')
                                ->default(false),
                        ]),
                        
                        Select::make('calendar_visibility')
                            ->label('مستوى الخصوصية للجلسات في التقويم')
                            ->options([
                                'default' => 'افتراضي (مرئي للمدعوين فقط)',
                                'public' => 'عام (مرئي للجميع)',
                                'private' => 'خاص (مرئي لي فقط)',
                            ])
                            ->default('default')
                            ->helperText('كيف تظهر جلساتك في Google Calendar'),
                    ]),

                Section::make('إعدادات الإشعارات')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('notify_on_student_join')
                                ->label('إشعار عند دخول الطالب')
                                ->helperText('إرسال إشعار عند انضمام الطالب للاجتماع')
                                ->default(true),
                                
                            Toggle::make('notify_on_session_end')
                                ->label('إشعار عند انتهاء الجلسة')
                                ->helperText('إرسال إشعار عند انتهاء وقت الجلسة')
                                ->default(false),
                        ]),
                        
                        Select::make('notification_method')
                            ->label('طريقة الإشعار المفضلة')
                            ->options([
                                'email' => 'البريد الإلكتروني',
                                'platform' => 'إشعارات المنصة',
                                'both' => 'البريد الإلكتروني والمنصة',
                            ])
                            ->default('both'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTeacherGoogleSettings::route('/'),
        ];
    }
} 