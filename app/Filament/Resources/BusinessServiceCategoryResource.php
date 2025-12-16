<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessServiceCategoryResource\Pages;
use App\Models\BusinessServiceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BusinessServiceCategoryResource extends Resource
{
    protected static ?string $model = BusinessServiceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'خدمات الأعمال';

    protected static ?string $navigationLabel = 'تصنيفات الخدمات';

    protected static ?string $modelLabel = 'تصنيف خدمة';

    protected static ?string $pluralModelLabel = 'تصنيفات الخدمات';

    protected static ?int $navigationSort = 1;

    /**
     * Check if the current user can access this resource
     */
    public static function canAccess(): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can create records
     */
    public static function canCreate(): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can edit records
     */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can delete records
     */
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can view records
     */
    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التصنيف')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم التصنيف')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: تصميم شعارات'),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف التصنيف')
                            ->maxLength(500)
                            ->placeholder('وصف مختصر للخدمات المقدمة في هذا التصنيف'),

                        Forms\Components\ColorPicker::make('color')
                            ->label('لون التصنيف')
                            ->default('#3B82F6'),

                        Forms\Components\Select::make('icon')
                            ->label('أيقونة التصنيف')
                            ->searchable()
                            ->options([
                                // التصميم والإبداع
                                'ri-brush-line' => '🎨 فرشاة رسم - التصميم',
                                'ri-image-line' => '📷 صورة - التصوير',
                                'ri-palette-line' => '🎭 ألوان - الهوية البصرية',
                                'ri-magic-line' => '✨ تأثيرات - الإبداع',
                                'ri-box-3-line' => '📦 مكعب - التصميم ثلاثي الأبعاد',
                                'ri-scissors-line' => '✂️ مقص - المونتاج',
                                'ri-pen-nib-line' => '🖊️ قلم تصميم - الجرافيك',
                                'ri-layout-line' => '📐 تخطيط - UI/UX',

                                // التطوير والتقنية
                                'ri-code-s-slash-line' => '💻 كود - البرمجة',
                                'ri-terminal-box-line' => '⌨️ سطر الأوامر - التطوير',
                                'ri-cpu-line' => '🔧 معالج - التقنية',
                                'ri-server-line' => '🖥️ خوادم - الاستضافة',
                                'ri-database-2-line' => '💾 قاعدة بيانات',
                                'ri-settings-3-line' => '⚙️ إعدادات - الصيانة',
                                'ri-git-branch-line' => '🌿 Git - إدارة الكود',
                                'ri-bug-line' => '🐛 اختبار - ضمان الجودة',

                                // الويب والموبايل
                                'ri-global-line' => '🌐 كرة أرضية - الويب',
                                'ri-computer-line' => '🖥️ حاسوب - تطبيقات سطح المكتب',
                                'ri-smartphone-line' => '📱 هاتف - تطبيقات الجوال',
                                'ri-tablet-line' => '📲 تابلت - التطبيقات',
                                'ri-window-line' => '🪟 نافذة - واجهات المستخدم',
                                'ri-cursor-line' => '🖱️ مؤشر - تجربة المستخدم',
                                'ri-html5-line' => '🌐 HTML - تطوير الويب',
                                'ri-apps-line' => '📱 تطبيقات - تطوير الموبايل',

                                // التسويق والمبيعات
                                'ri-megaphone-line' => '📢 مكبر صوت - التسويق',
                                'ri-bar-chart-line' => '📊 رسم بياني - التحليلات',
                                'ri-presentation-line' => '📈 عرض تقديمي - الاستراتيجية',
                                'ri-line-chart-line' => '📈 نمو - التطوير',
                                'ri-filter-line' => '🎯 قمع - المبيعات',
                                'ri-rocket-line' => '🚀 صاروخ - الإطلاق',
                                'ri-advertisement-line' => '📺 إعلان - الحملات',
                                'ri-focus-3-line' => '🎯 استهداف - SEO',

                                // المحتوى والكتابة
                                'ri-file-text-line' => '📄 مستند - المحتوى',
                                'ri-pencil-line' => '✏️ قلم - الكتابة',
                                'ri-newspaper-line' => '📰 جريدة - المقالات',
                                'ri-book-open-line' => '📖 كتاب - التعليم',
                                'ri-translate-2' => '🌍 لغات - الترجمة',
                                'ri-clipboard-line' => '📋 قائمة - إدارة المحتوى',
                                'ri-article-line' => '📝 مقال - التدوين',
                                'ri-quill-pen-line' => '🪶 ريشة - الكتابة الإبداعية',

                                // الفيديو والصوت
                                'ri-video-line' => '🎬 كاميرا فيديو - الإنتاج',
                                'ri-film-line' => '🎞️ فيلم - المونتاج',
                                'ri-mic-line' => '🎙️ ميكروفون - البودكاست',
                                'ri-music-line' => '🎵 موسيقى - الصوتيات',
                                'ri-play-circle-line' => '▶️ تشغيل - الوسائط',
                                'ri-volume-up-line' => '🔊 صوت - الهندسة الصوتية',
                                'ri-live-line' => '🔴 بث مباشر - الستريمنج',
                                'ri-youtube-line' => '📺 يوتيوب - إنتاج الفيديو',

                                // التجارة الإلكترونية
                                'ri-shopping-cart-line' => '🛒 سلة تسوق - المتاجر',
                                'ri-shopping-bag-line' => '🛍️ حقيبة تسوق - التجارة',
                                'ri-bank-card-line' => '💳 بطاقة - المدفوعات',
                                'ri-money-dollar-circle-line' => '💵 نقود - المالية',
                                'ri-coupon-line' => '🏷️ خصومات - العروض',
                                'ri-store-2-line' => '🏪 متجر - التجزئة',
                                'ri-wallet-line' => '👛 محفظة - الدفع الإلكتروني',
                                'ri-exchange-dollar-line' => '💱 تحويل - العملات',

                                // الدعم والتواصل
                                'ri-chat-3-line' => '💬 محادثة - الدعم',
                                'ri-mail-line' => '✉️ بريد - التواصل',
                                'ri-phone-line' => '📞 هاتف - الاتصال',
                                'ri-lifebuoy-line' => '🛟 طوق نجاة - المساعدة',
                                'ri-question-line' => '❓ علامة استفهام - الأسئلة',
                                'ri-customer-service-2-line' => '🎧 سماعات - خدمة العملاء',
                                'ri-message-3-line' => '💭 رسالة - الاستشارات',
                                'ri-contacts-line' => '📇 جهات اتصال - CRM',

                                // الأمان والحماية
                                'ri-shield-check-line' => '🛡️ درع - الأمان',
                                'ri-lock-line' => '🔒 قفل - الحماية',
                                'ri-key-line' => '🔑 مفتاح - الوصول',
                                'ri-fingerprint-line' => '👆 بصمة - التحقق',
                                'ri-eye-line' => '👁️ عين - المراقبة',
                                'ri-alarm-warning-line' => '⚠️ تحذير - الإنذارات',
                                'ri-spy-line' => '🕵️ تجسس - الأمن السيبراني',
                                'ri-shield-keyhole-line' => '🔐 حماية - التشفير',

                                // التعليم والتدريب
                                'ri-graduation-cap-line' => '🎓 قبعة تخرج - التعليم',
                                'ri-lightbulb-line' => '💡 مصباح - الأفكار',
                                'ri-puzzle-line' => '🧩 قطعة بازل - الحلول',
                                'ri-flask-line' => '🧪 دورق - البحث',
                                'ri-calculator-line' => '🧮 آلة حاسبة - المحاسبة',
                                'ri-todo-line' => '✅ قائمة تحقق - التقييم',
                                'ri-slideshow-line' => '📊 عرض - التدريب',
                                'ri-book-2-line' => '📚 كتب - الدورات',

                                // السحابة والبنية التحتية
                                'ri-cloud-line' => '☁️ سحابة - الخدمات السحابية',
                                'ri-upload-cloud-line' => '⬆️ رفع - التخزين السحابي',
                                'ri-download-cloud-line' => '⬇️ تنزيل - النسخ الاحتياطي',
                                'ri-signal-tower-line' => '📶 إشارة - الشبكات',
                                'ri-wifi-line' => '📡 واي فاي - الاتصال',
                                'ri-earth-line' => '🌎 عالمي - CDN',
                                'ri-hard-drive-2-line' => '💿 تخزين - الاستضافة',
                                'ri-router-line' => '🌐 راوتر - البنية التحتية',

                                // الأعمال والإدارة
                                'ri-briefcase-line' => '💼 حقيبة - الأعمال',
                                'ri-building-line' => '🏢 مبنى - الشركات',
                                'ri-team-line' => '👥 مستخدمون - الفرق',
                                'ri-group-line' => '👨‍👩‍👧‍👦 مجموعة - المجتمع',
                                'ri-calendar-line' => '📅 تقويم - الجدولة',
                                'ri-time-line' => '⏰ ساعة - إدارة الوقت',
                                'ri-pie-chart-line' => '📊 دائري - التقارير',
                                'ri-funds-line' => '📈 استثمار - التمويل',

                                // وسائل التواصل الاجتماعي
                                'ri-share-line' => '🔗 مشاركة - التواصل الاجتماعي',
                                'ri-heart-line' => '❤️ قلب - التفاعل',
                                'ri-thumb-up-line' => '👍 إعجاب - التقييم',
                                'ri-star-line' => '⭐ نجمة - المراجعات',
                                'ri-hashtag' => '#️⃣ هاشتاق - الترندات',
                                'ri-at-line' => '@ رمز - الإشارات',
                                'ri-instagram-line' => '📸 انستجرام - التصوير',
                                'ri-twitter-x-line' => '🐦 تويتر - التغريدات',
                                'ri-facebook-circle-line' => '👥 فيسبوك - التواصل',
                                'ri-linkedin-box-line' => '💼 لينكدإن - الأعمال',
                            ])
                            ->helperText('اختر أيقونة تناسب نوع الخدمة'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('إظهار هذا التصنيف في الواجهة الأمامية'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم التصنيف')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\ColorColumn::make('color')
                    ->label('اللون'),

                Tables\Columns\IconColumn::make('icon')
                    ->label('الأيقونة'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('serviceRequests_count')
                    ->label('عدد الطلبات')
                    ->counts('serviceRequests')
                    ->sortable(),

                Tables\Columns\TextColumn::make('portfolioItems_count')
                    ->label('عدد أعمال البورتفوليو')
                    ->counts('portfolioItems')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('جميع التصنيفات')
                    ->trueLabel('التصنيفات النشطة فقط')
                    ->falseLabel('التصنيفات غير النشطة فقط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListBusinessServiceCategories::route('/'),
            'create' => Pages\CreateBusinessServiceCategory::route('/create'),
            'edit' => Pages\EditBusinessServiceCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
