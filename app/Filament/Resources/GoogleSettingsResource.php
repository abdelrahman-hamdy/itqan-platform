<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoogleSettingsResource\Pages;
use App\Models\AcademyGoogleSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class GoogleSettingsResource extends Resource
{
    protected static ?string $model = AcademyGoogleSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'إعدادات Google Meet';
    protected static ?string $modelLabel = 'إعدادات Google Meet';
    protected static ?string $pluralModelLabel = 'إعدادات Google Meet';
    protected static ?string $navigationGroup = 'الإعدادات العامة';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('إعدادات Google Cloud Project')
                    ->description('إعدادات مشروع Google Cloud والتطبيق')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('google_project_id')
                                ->label('معرف مشروع Google Cloud')
                                ->required()
                                ->placeholder('my-project-123456')
                                ->helperText('معرف المشروع من Google Cloud Console'),
                                
                            TextInput::make('google_client_id')
                                ->label('Client ID')
                                ->required()
                                ->placeholder('123456789-abcdefg.apps.googleusercontent.com')
                                ->helperText('Client ID من Google Cloud Console'),
                        ]),
                        
                        TextInput::make('google_client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->required()
                            ->placeholder('GOCSPX-...')
                            ->helperText('Client Secret من Google Cloud Console (سيتم تشفيره تلقائياً)'),
                            
                        TextInput::make('oauth_redirect_uri')
                            ->label('OAuth Redirect URI')
                            ->url()
                            ->placeholder('https://academy.itqan.com/google/callback')
                            ->helperText('رابط الإعادة التوجيه بعد تسجيل الدخول بـ Google'),
                            
                        TagsInput::make('oauth_scopes')
                            ->label('OAuth Scopes')
                            ->default([
                                'https://www.googleapis.com/auth/calendar',
                                'https://www.googleapis.com/auth/calendar.events'
                            ])
                            ->helperText('الصلاحيات المطلوبة من Google API'),
                    ]),

                Section::make('حساب الخدمة الرئيسي')
                    ->description('حساب الخدمة لإنشاء وإدارة الاجتماعات')
                    ->schema([
                        FileUpload::make('google_service_account_key')
                            ->label('ملف مفتاح حساب الخدمة (JSON)')
                            ->acceptedFileTypes(['application/json', '.json'])
                            ->disk('local')
                            ->directory('temp')
                            ->visibility('private')
                            ->storeFiles(false)
                            ->helperText('ارفع ملف JSON لحساب الخدمة من Google Cloud Console (سيتم تشفيره تلقائياً)')
                            ->afterStateUpdated(function ($state, $set) {
                                // This will trigger when a new file is uploaded
                                if ($state instanceof \Illuminate\Http\UploadedFile) {
                                    $set('service_account_file_status', '📤 ملف جديد محدد للرفع');
                                }
                            }),
                        
                        Placeholder::make('service_account_file_status')
                            ->label('حالة ملف حساب الخدمة')
                            ->content(function () {
                                try {
                                    // Get the current academy settings
                                    $academy = \App\Services\AcademyContextService::getCurrentAcademy();
                                    if (!$academy) {
                                        return '❌ لا توجد أكاديمية محددة';
                                    }
                                    
                                    $settings = \App\Models\AcademyGoogleSettings::forAcademy($academy);
                                    
                                    // Get the raw encrypted value directly from database
                                    $encryptedKey = $settings->getAttributes()['google_service_account_key'] ?? null;
                                    
                                    if (empty($encryptedKey)) {
                                        return '❌ لم يتم رفع أي ملف';
                                    }
                                    
                                    // Try to decrypt using Laravel's decrypt helper
                                    $content = \Illuminate\Support\Facades\Crypt::decryptString($encryptedKey);
                                    $data = json_decode($content, true);
                                    
                                    if (json_last_error() === JSON_ERROR_NONE && isset($data['type'], $data['project_id'])) {
                                        return "✅ تم رفع الملف بنجاح\nProject ID: " . $data['project_id'] . "\nType: " . $data['type'];
                                    }
                                    return '⚠️ الملف غير صالح - تحقق من تنسيق JSON';
                                    
                                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                                    return '❌ خطأ في فك التشفير: الملف قد يكون تالفاً';
                                } catch (\Exception $e) {
                                    return '❌ خطأ في قراءة الملف: ' . $e->getMessage();
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('حساب النظام الاحتياطي')
                    ->description('حساب Google للاستخدام عند فشل حسابات المعلمين')
                    ->schema([
                        Toggle::make('fallback_account_enabled')
                            ->label('تفعيل الحساب الاحتياطي')
                            ->helperText('استخدام حساب النظام عند فشل حساب المعلم'),
                            
                        Grid::make(2)->schema([
                            TextInput::make('fallback_account_email')
                                ->label('البريد الإلكتروني للحساب الاحتياطي')
                                ->email()
                                ->visible(fn (Forms\Get $get) => $get('fallback_account_enabled'))
                                ->placeholder('meetings@academy.com'),
                                
                            TextInput::make('fallback_daily_limit')
                                ->label('الحد الأقصى اليومي للاجتماعات')
                                ->numeric()
                                ->default(100)
                                ->minValue(1)
                                ->maxValue(1000)
                                ->visible(fn (Forms\Get $get) => $get('fallback_account_enabled')),
                        ]),
                        
                        Textarea::make('fallback_account_credentials')
                            ->label('بيانات اعتماد الحساب الاحتياطي (JSON)')
                            ->rows(6)
                            ->placeholder('{"type": "service_account", "project_id": "...", ...}')
                            ->helperText('مفتاح حساب الخدمة من Google Cloud Console (سيتم تشفيره)')
                            ->visible(fn (Forms\Get $get) => $get('fallback_account_enabled')),
                    ]),

                Section::make('إعدادات الاجتماعات')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('auto_create_meetings')
                                ->label('إنشاء الاجتماعات تلقائياً')
                                ->default(true)
                                ->helperText('إنشاء روابط Google Meet تلقائياً للجلسات'),
                                
                            Toggle::make('auto_record_sessions')
                                ->label('تسجيل الجلسات تلقائياً')
                                ->default(false)
                                ->helperText('تسجيل جلسات Google Meet تلقائياً'),
                        ]),
                        
                        Grid::make(2)->schema([
                            TextInput::make('meeting_prep_minutes')
                                ->label('وقت التحضير (بالدقائق)')
                                ->numeric()
                                ->default(60)
                                ->minValue(5)
                                ->maxValue(240)
                                ->helperText('كم دقيقة قبل الجلسة يتم إنشاء رابط الاجتماع'),
                                
                            TextInput::make('default_session_duration')
                                ->label('مدة الجلسة الافتراضية (بالدقائق)')
                                ->numeric()
                                ->default(60)
                                ->minValue(15)
                                ->maxValue(240),
                        ]),
                    ]),

                Section::make('إعدادات الإشعارات')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('send_meeting_reminders')
                                ->label('إرسال تذكيرات الاجتماعات')
                                ->default(true),
                                
                            Toggle::make('notify_on_teacher_disconnect')
                                ->label('إشعار عند قطع اتصال المعلم بـ Google')
                                ->helperText('إرسال إشعار للإدارة عند فقدان اتصال المعلم بـ Google')
                                ->default(true),
                        ]),
                        
                        TagsInput::make('reminder_times')
                            ->label('أوقات التذكير (بالدقائق)')
                            ->default(['60', '15'])
                            ->helperText('كم دقيقة قبل الجلسة يتم إرسال التذكير (مثال: 60, 15)')
                            ->nestedRecursiveRules([
                                'min:1',
                                'max:1440',
                                'numeric',
                            ]),
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
            'index' => Pages\ManageGoogleSettings::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->isAdmin();
    }
}