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
                                'heroicon-o-paint-brush' => '🎨 فرشاة رسم - التصميم',
                                'heroicon-o-photo' => '📷 صورة - التصوير',
                                'heroicon-o-swatch' => '🎭 ألوان - الهوية البصرية',
                                'heroicon-o-sparkles' => '✨ تأثيرات - الإبداع',
                                'heroicon-o-cube' => '📦 مكعب - التصميم ثلاثي الأبعاد',
                                'heroicon-o-scissors' => '✂️ مقص - المونتاج',

                                // التطوير والتقنية
                                'heroicon-o-code-bracket' => '💻 كود - البرمجة',
                                'heroicon-o-command-line' => '⌨️ سطر الأوامر - التطوير',
                                'heroicon-o-cpu-chip' => '🔧 معالج - التقنية',
                                'heroicon-o-server-stack' => '🖥️ خوادم - الاستضافة',
                                'heroicon-o-circle-stack' => '💾 قاعدة بيانات',
                                'heroicon-o-cog-6-tooth' => '⚙️ إعدادات - الصيانة',

                                // الويب والموبايل
                                'heroicon-o-globe-alt' => '🌐 كرة أرضية - الويب',
                                'heroicon-o-computer-desktop' => '🖥️ حاسوب - تطبيقات سطح المكتب',
                                'heroicon-o-device-phone-mobile' => '📱 هاتف - تطبيقات الجوال',
                                'heroicon-o-device-tablet' => '📲 تابلت - التطبيقات',
                                'heroicon-o-window' => '🪟 نافذة - واجهات المستخدم',
                                'heroicon-o-cursor-arrow-rays' => '🖱️ مؤشر - تجربة المستخدم',

                                // التسويق والمبيعات
                                'heroicon-o-megaphone' => '📢 مكبر صوت - التسويق',
                                'heroicon-o-chart-bar' => '📊 رسم بياني - التحليلات',
                                'heroicon-o-presentation-chart-line' => '📈 عرض تقديمي - الاستراتيجية',
                                'heroicon-o-arrow-trending-up' => '📈 نمو - التطوير',
                                'heroicon-o-funnel' => '🎯 قمع - المبيعات',
                                'heroicon-o-rocket-launch' => '🚀 صاروخ - الإطلاق',

                                // المحتوى والكتابة
                                'heroicon-o-document-text' => '📄 مستند - المحتوى',
                                'heroicon-o-pencil-square' => '✏️ قلم - الكتابة',
                                'heroicon-o-newspaper' => '📰 جريدة - المقالات',
                                'heroicon-o-book-open' => '📖 كتاب - التعليم',
                                'heroicon-o-language' => '🌍 لغات - الترجمة',
                                'heroicon-o-clipboard-document-list' => '📋 قائمة - إدارة المحتوى',

                                // الفيديو والصوت
                                'heroicon-o-video-camera' => '🎬 كاميرا فيديو - الإنتاج',
                                'heroicon-o-film' => '🎞️ فيلم - المونتاج',
                                'heroicon-o-microphone' => '🎙️ ميكروفون - البودكاست',
                                'heroicon-o-musical-note' => '🎵 موسيقى - الصوتيات',
                                'heroicon-o-play-circle' => '▶️ تشغيل - الوسائط',
                                'heroicon-o-speaker-wave' => '🔊 صوت - الهندسة الصوتية',

                                // التجارة الإلكترونية
                                'heroicon-o-shopping-cart' => '🛒 سلة تسوق - المتاجر',
                                'heroicon-o-shopping-bag' => '🛍️ حقيبة تسوق - التجارة',
                                'heroicon-o-credit-card' => '💳 بطاقة - المدفوعات',
                                'heroicon-o-banknotes' => '💵 نقود - المالية',
                                'heroicon-o-receipt-percent' => '🏷️ خصومات - العروض',
                                'heroicon-o-building-storefront' => '🏪 متجر - التجزئة',

                                // الدعم والتواصل
                                'heroicon-o-chat-bubble-left-right' => '💬 محادثة - الدعم',
                                'heroicon-o-envelope' => '✉️ بريد - التواصل',
                                'heroicon-o-phone' => '📞 هاتف - الاتصال',
                                'heroicon-o-lifebuoy' => '🛟 طوق نجاة - المساعدة',
                                'heroicon-o-question-mark-circle' => '❓ علامة استفهام - الأسئلة',
                                'heroicon-o-chat-bubble-oval-left-ellipsis' => '💭 فقاعة محادثة - الاستشارات',

                                // الأمان والحماية
                                'heroicon-o-shield-check' => '🛡️ درع - الأمان',
                                'heroicon-o-lock-closed' => '🔒 قفل - الحماية',
                                'heroicon-o-key' => '🔑 مفتاح - الوصول',
                                'heroicon-o-finger-print' => '👆 بصمة - التحقق',
                                'heroicon-o-eye' => '👁️ عين - المراقبة',
                                'heroicon-o-shield-exclamation' => '⚠️ تحذير - الأمان',

                                // التعليم والتدريب
                                'heroicon-o-academic-cap' => '🎓 قبعة تخرج - التعليم',
                                'heroicon-o-light-bulb' => '💡 مصباح - الأفكار',
                                'heroicon-o-puzzle-piece' => '🧩 قطعة بازل - الحلول',
                                'heroicon-o-beaker' => '🧪 دورق - البحث',
                                'heroicon-o-calculator' => '🧮 آلة حاسبة - المحاسبة',
                                'heroicon-o-clipboard-document-check' => '✅ قائمة تحقق - التقييم',

                                // السحابة والبنية التحتية
                                'heroicon-o-cloud' => '☁️ سحابة - الخدمات السحابية',
                                'heroicon-o-cloud-arrow-up' => '⬆️ رفع - التخزين السحابي',
                                'heroicon-o-cloud-arrow-down' => '⬇️ تنزيل - النسخ الاحتياطي',
                                'heroicon-o-signal' => '📶 إشارة - الشبكات',
                                'heroicon-o-wifi' => '📡 واي فاي - الاتصال',
                                'heroicon-o-globe-americas' => '🌎 عالمي - CDN',

                                // الأعمال والإدارة
                                'heroicon-o-briefcase' => '💼 حقيبة - الأعمال',
                                'heroicon-o-building-office' => '🏢 مبنى - الشركات',
                                'heroicon-o-users' => '👥 مستخدمون - الفرق',
                                'heroicon-o-user-group' => '👨‍👩‍👧‍👦 مجموعة - المجتمع',
                                'heroicon-o-calendar' => '📅 تقويم - الجدولة',
                                'heroicon-o-clock' => '⏰ ساعة - إدارة الوقت',

                                // وسائل التواصل الاجتماعي
                                'heroicon-o-share' => '🔗 مشاركة - التواصل الاجتماعي',
                                'heroicon-o-heart' => '❤️ قلب - التفاعل',
                                'heroicon-o-hand-thumb-up' => '👍 إعجاب - التقييم',
                                'heroicon-o-star' => '⭐ نجمة - المراجعات',
                                'heroicon-o-hashtag' => '#️⃣ هاشتاق - الترندات',
                                'heroicon-o-at-symbol' => '@ رمز - الإشارات',
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
