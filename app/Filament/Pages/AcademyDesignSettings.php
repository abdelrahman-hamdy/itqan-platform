<?php

namespace App\Filament\Pages;

use App\Models\Academy;
use App\Services\AcademyContextService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

/**
 * @property Form $form
 */
class AcademyDesignSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static string $view = 'filament.pages.academy-design-settings';

    protected static ?string $navigationGroup = 'إدارة النظام';

    protected static ?string $navigationLabel = 'تصميم الصفحة الرئيسية';

    protected static ?string $title = 'تخصيص تصميم الصفحة الرئيسية';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public ?int $selectedAcademyId = null;

    public function mount(): void
    {
        // Get current academy context or first academy
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        $this->selectedAcademyId = $currentAcademy?->id ?? Academy::first()?->id;

        if ($this->selectedAcademyId) {
            $this->loadAcademyData();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('selectedAcademyId')
                            ->label('اختر الأكاديمية')
                            ->options(Academy::pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selectedAcademyId = $state;
                                $this->loadAcademyData();
                            })
                            ->required(),
                    ]),

                Section::make('ترتيب الأقسام')
                    ->description('اسحب وأسقط الأقسام لإعادة ترتيبها')
                    ->schema([
                        Repeater::make('sections_order')
                            ->label('')
                            ->simple(
                                \Filament\Forms\Components\ViewField::make('section')
                                    ->label('')
                                    ->view('filament.forms.components.section-display'),
                            )
                            ->reorderable()
                            ->deletable(false)
                            ->addable(false)
                            ->defaultItems(7)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->selectedAcademyId !== null)
                    ->collapsible(),

                // Hero Section Settings
                Section::make('إعدادات القسم الرئيسي (Hero)')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('hero_visible')
                                    ->label('إظهار القسم')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('hero_show_in_nav')
                                    ->label('إظهار في القائمة العلوية')
                                    ->default(false)
                                    ->inline(false),

                                Toggle::make('hero_show_boxes')
                                    ->label('إظهار صناديق الخدمات')
                                    ->helperText('الصناديق الأربعة (حلقات القرآن، التعلم الفردي، الدروس الخصوصية، الدورات التفاعلية)')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Select::make('hero_template')
                            ->label('قالب التصميم')
                            ->options([
                                'template_1' => 'القالب الأول (الافتراضي)',
                                'template_2' => 'القالب الثاني',
                                'template_3' => 'القالب الثالث',
                            ])
                            ->default('template_1')
                            ->live()
                            ->required(),

                        FileUpload::make('hero_image')
                            ->label('صورة القسم الرئيسي')
                            ->helperText('هذه الصورة تظهر فقط في القالب الثالث')
                            ->image()
                            ->directory('academies/hero-images')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('4:3')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('600')
                            ->visible(fn ($get) => $get('hero_template') === 'template_3'),

                        TextInput::make('hero_heading')
                            ->label('عنوان القسم')
                            ->placeholder('تعليم متميز للمستقبل')
                            ->default('تعليم متميز للمستقبل'),

                        \Filament\Forms\Components\Textarea::make('hero_subheading')
                            ->label('عنوان فرعي')
                            ->placeholder('انضم إلى آلاف الطلاب الذين يطورون مهاراتهم...')
                            ->default('انضم إلى آلاف الطلاب الذين يطورون مهاراتهم في القرآن الكريم والتعليم الأكاديمي مع أفضل المعلمين المتخصصين')
                            ->rows(3),
                    ])
                    ->visible(fn () => $this->selectedAcademyId !== null)
                    ->collapsible()
                    ->collapsed(),

                // Stats Section Settings
                Section::make('إعدادات قسم الإحصائيات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('stats_visible')
                                    ->label('إظهار القسم')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('stats_show_in_nav')
                                    ->label('إظهار في القائمة العلوية')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Select::make('stats_template')
                            ->label('قالب التصميم')
                            ->options([
                                'template_1' => 'القالب الأول (الافتراضي)',
                                'template_2' => 'القالب الثاني',
                                'template_3' => 'القالب الثالث',
                            ])
                            ->default('template_1')
                            ->required(),

                        TextInput::make('stats_heading')
                            ->label('عنوان القسم')
                            ->placeholder('إنجازاتنا بالأرقام')
                            ->default('إنجازاتنا بالأرقام'),

                        \Filament\Forms\Components\Textarea::make('stats_subheading')
                            ->label('عنوان فرعي')
                            ->placeholder('نفخر بإنجازاتنا ونتائج طلابنا المتميزة')
                            ->default('نفخر بإنجازاتنا ونتائج طلابنا المتميزة')
                            ->rows(3),
                    ])
                    ->visible(fn () => $this->selectedAcademyId !== null)
                    ->collapsible()
                    ->collapsed(),

                // Reviews Section Settings
                Section::make('إعدادات قسم التقييمات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('reviews_visible')
                                    ->label('إظهار القسم')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('reviews_show_in_nav')
                                    ->label('إظهار في القائمة العلوية')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Select::make('reviews_template')
                            ->label('قالب التصميم')
                            ->options([
                                'template_1' => 'القالب الأول (الافتراضي)',
                                'template_2' => 'القالب الثاني',
                                'template_3' => 'القالب الثالث',
                            ])
                            ->default('template_1')
                            ->required(),

                        TextInput::make('reviews_heading')
                            ->label('عنوان القسم')
                            ->placeholder('آراء طلابنا')
                            ->default('آراء طلابنا'),

                        \Filament\Forms\Components\Textarea::make('reviews_subheading')
                            ->label('عنوان فرعي')
                            ->placeholder('اكتشف تجارب طلابنا الناجحة وكيف ساعدتهم في تحقيق أهدافهم التعليمية')
                            ->default('اكتشف تجارب طلابنا الناجحة وكيف ساعدتهم في تحقيق أهدافهم التعليمية')
                            ->rows(3),

                        Repeater::make('reviews_items')
                            ->label('آراء الطلاب')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('اسم الطالب')
                                            ->required()
                                            ->maxLength(100),

                                        TextInput::make('role')
                                            ->label('الوصف / الدور')
                                            ->placeholder('طالب في قسم القرآن الكريم')
                                            ->maxLength(100),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Select::make('rating')
                                            ->label('التقييم')
                                            ->options([
                                                5 => '★★★★★ (5 نجوم)',
                                                4 => '★★★★☆ (4 نجوم)',
                                                3 => '★★★☆☆ (3 نجوم)',
                                                2 => '★★☆☆☆ (نجمتان)',
                                                1 => '★☆☆☆☆ (نجمة واحدة)',
                                            ])
                                            ->default(5),

                                        FileUpload::make('avatar')
                                            ->label('صورة الطالب')
                                            ->image()
                                            ->directory('academies/reviews')
                                            ->visibility('public')
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('1:1')
                                            ->imageResizeTargetWidth('150')
                                            ->imageResizeTargetHeight('150'),
                                    ]),

                                \Filament\Forms\Components\Textarea::make('content')
                                    ->label('نص التقييم')
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(500),
                            ])
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'تقييم جديد')
                            ->reorderable()
                            ->defaultItems(0)
                            ->addActionLabel('إضافة تقييم جديد')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->selectedAcademyId !== null)
                    ->collapsible()
                    ->collapsed(),

                // Quran Section Settings
                Section::make('إعدادات قسم القرآن الكريم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('quran_visible')
                                    ->label('إظهار القسم')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('quran_show_in_nav')
                                    ->label('إظهار في القائمة العلوية')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Select::make('quran_template')
                            ->label('قالب التصميم')
                            ->options([
                                'template_1' => 'القالب الأول (الافتراضي)',
                                'template_2' => 'القالب الثاني',
                                'template_3' => 'القالب الثالث',
                            ])
                            ->default('template_1')
                            ->required(),

                        TextInput::make('quran_heading')
                            ->label('عنوان القسم')
                            ->placeholder('برامج القرآن الكريم')
                            ->default('برامج القرآن الكريم'),

                        \Filament\Forms\Components\Textarea::make('quran_subheading')
                            ->label('عنوان فرعي')
                            ->placeholder('ابدأ رحلتك في حفظ القرآن الكريم مع نخبة من أفضل المعلمين المتخصصين')
                            ->default('ابدأ رحلتك في حفظ القرآن الكريم مع نخبة من أفضل المعلمين المتخصصين')
                            ->rows(3),
                    ])
                    ->visible(fn () => $this->selectedAcademyId !== null)
                    ->collapsible()
                    ->collapsed(),

                // Academic Section Settings
                Section::make('إعدادات القسم الأكاديمي')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('academic_visible')
                                    ->label('إظهار القسم')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('academic_show_in_nav')
                                    ->label('إظهار في القائمة العلوية')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Select::make('academic_template')
                            ->label('قالب التصميم')
                            ->options([
                                'template_1' => 'القالب الأول (الافتراضي)',
                                'template_2' => 'القالب الثاني',
                                'template_3' => 'القالب الثالث',
                            ])
                            ->default('template_1')
                            ->required(),

                        TextInput::make('academic_heading')
                            ->label('عنوان القسم')
                            ->placeholder('البرامج الأكاديمية')
                            ->default('البرامج الأكاديمية'),

                        \Filament\Forms\Components\Textarea::make('academic_subheading')
                            ->label('عنوان فرعي')
                            ->placeholder('تعليم أكاديمي متميز مع معلمين خبراء في جميع المواد الدراسية')
                            ->default('تعليم أكاديمي متميز مع معلمين خبراء في جميع المواد الدراسية')
                            ->rows(3),
                    ])
                    ->visible(fn () => $this->selectedAcademyId !== null)
                    ->collapsible()
                    ->collapsed(),

                // Courses Section Settings
                Section::make('إعدادات قسم الدورات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('courses_visible')
                                    ->label('إظهار القسم')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('courses_show_in_nav')
                                    ->label('إظهار في القائمة العلوية')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Select::make('courses_template')
                            ->label('قالب التصميم')
                            ->options([
                                'template_1' => 'القالب الأول (الافتراضي)',
                                'template_2' => 'القالب الثاني',
                                'template_3' => 'القالب الثالث',
                            ])
                            ->default('template_1')
                            ->required(),

                        TextInput::make('courses_heading')
                            ->label('عنوان القسم')
                            ->placeholder('الدورات المسجلة')
                            ->default('الدورات المسجلة'),

                        \Filament\Forms\Components\Textarea::make('courses_subheading')
                            ->label('عنوان فرعي')
                            ->placeholder('دورات تعليمية شاملة ومتنوعة في مختلف المجالات والتخصصات')
                            ->default('دورات تعليمية شاملة ومتنوعة في مختلف المجالات والتخصصات')
                            ->rows(3),
                    ])
                    ->visible(fn () => $this->selectedAcademyId !== null)
                    ->collapsible()
                    ->collapsed(),

                // Features Section Settings
                Section::make('إعدادات قسم المميزات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('features_visible')
                                    ->label('إظهار القسم')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('features_show_in_nav')
                                    ->label('إظهار في القائمة العلوية')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Select::make('features_template')
                            ->label('قالب التصميم')
                            ->options([
                                'template_1' => 'القالب الأول (الافتراضي)',
                                'template_2' => 'القالب الثاني',
                                'template_3' => 'القالب الثالث',
                            ])
                            ->default('template_1')
                            ->required(),

                        TextInput::make('features_heading')
                            ->label('عنوان القسم')
                            ->placeholder('مميزات المنصة')
                            ->default('مميزات المنصة'),

                        \Filament\Forms\Components\Textarea::make('features_subheading')
                            ->label('عنوان فرعي')
                            ->placeholder('اكتشف المميزات التي تجعلنا الخيار الأفضل لتعليمك وتطوير مهاراتك')
                            ->default('اكتشف المميزات التي تجعلنا الخيار الأفضل لتعليمك وتطوير مهاراتك')
                            ->rows(3),
                    ])
                    ->visible(fn () => $this->selectedAcademyId !== null)
                    ->collapsible()
                    ->collapsed(),

                // Footer Settings
                Section::make('إعدادات الفوتر')
                    ->description('التحكم في إظهار وإخفاء أعمدة الفوتر')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('footer_show_academy_info')
                                    ->label('إظهار معلومات الأكاديمية')
                                    ->helperText('الشعار والوصف وروابط التواصل الاجتماعي')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('footer_show_main_sections')
                                    ->label('إظهار الأقسام الرئيسية')
                                    ->helperText('روابط الصفحة الرئيسية والحلقات والدورات')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('footer_show_important_links')
                                    ->label('إظهار الروابط المهمة')
                                    ->helperText('من نحن، سياسة الخصوصية، الشروط والأحكام')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('footer_show_contact_info')
                                    ->label('إظهار معلومات التواصل')
                                    ->helperText('رقم الهاتف والبريد الإلكتروني والعنوان')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ])
                    ->visible(fn () => $this->selectedAcademyId !== null)
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    protected function loadAcademyData(): void
    {
        if (! $this->selectedAcademyId) {
            return;
        }

        $academy = Academy::find($this->selectedAcademyId);

        if (! $academy) {
            return;
        }

        $data = $academy->toArray();

        // Convert flat array to repeater format
        if (! isset($data['sections_order']) || ! is_array($data['sections_order']) || empty($data['sections_order'])) {
            // Default order if not set
            $data['sections_order'] = ['hero', 'stats', 'reviews', 'quran', 'academic', 'courses', 'features'];
        }

        // Convert to repeater format: ['hero', 'stats'] => [['section' => 'hero'], ['section' => 'stats']]
        $data['sections_order'] = array_map(fn ($section) => ['section' => $section], $data['sections_order']);

        $data['selectedAcademyId'] = $this->selectedAcademyId;

        $this->form->fill($data);
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            if (! $this->selectedAcademyId) {
                Notification::make()
                    ->title('يرجى اختيار أكاديمية')
                    ->danger()
                    ->send();

                return;
            }

            $academy = Academy::find($this->selectedAcademyId);

            if (! $academy) {
                Notification::make()
                    ->title('الأكاديمية غير موجودة')
                    ->danger()
                    ->send();

                return;
            }

            // Convert repeater format back to simple array
            if (isset($data['sections_order']) && is_array($data['sections_order'])) {
                // Extract 'section' values from repeater format
                $sectionsOrder = array_column($data['sections_order'], 'section');
                // Filter out empty values and reindex
                $sectionsOrder = array_filter($sectionsOrder);
                $data['sections_order'] = array_values($sectionsOrder);
            } else {
                // Set default if not present
                $data['sections_order'] = ['hero', 'stats', 'reviews', 'quran', 'academic', 'courses', 'features'];
            }

            // Remove the selectedAcademyId from data before updating
            unset($data['selectedAcademyId']);

            // Update the academy
            $academy->update($data);

            // Force refresh the model to verify save
            $academy->refresh();

            Notification::make()
                ->title('تم حفظ التغييرات بنجاح')
                ->success()
                ->send();

            // Reload the form data to reflect saved changes
            $this->loadAcademyData();

        } catch (Halt $exception) {
            return;
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء الحفظ')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('حفظ التغييرات')
                ->submit('save'),
        ];
    }

    public static function canAccess(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }
}
