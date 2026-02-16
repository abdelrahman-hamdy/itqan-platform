<?php

namespace Database\Seeders;

use App\Models\BusinessServiceCategory;
use Illuminate\Database\Seeder;

class BusinessServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'تطوير المواقع الإلكترونية',
                'description' => 'تصميم وتطوير مواقع إلكترونية احترافية ومتجاوبة مع جميع الأجهزة باستخدام أحدث التقنيات وأفضل الممارسات لضمان تجربة مستخدم مميزة',
                'color' => '#3B82F6',
                'icon' => 'ri-code-s-slash-line',
                'is_active' => true,
            ],
            [
                'name' => 'تطوير تطبيقات الجوال',
                'description' => 'بناء تطبيقات جوال ذكية لنظامي iOS و Android بتصميم عصري وأداء عالٍ يلبي احتياجات عملك ويوفر تجربة سلسة للمستخدمين',
                'color' => '#10B981',
                'icon' => 'ri-smartphone-line',
                'is_active' => true,
            ],
            [
                'name' => 'تصميم الهوية البصرية',
                'description' => 'تصميم هوية بصرية متكاملة تشمل الشعار والألوان والخطوط والمواد التسويقية لتعكس قيم علامتك التجارية وتميزها في السوق',
                'color' => '#8B5CF6',
                'icon' => 'ri-palette-line',
                'is_active' => true,
            ],
            [
                'name' => 'التسويق الرقمي',
                'description' => 'استراتيجيات تسويقية رقمية شاملة تشمل إعلانات جوجل والسوشال ميديا والبريد الإلكتروني لزيادة الوعي بعلامتك التجارية وتحقيق أهدافك',
                'color' => '#F59E0B',
                'icon' => 'ri-megaphone-line',
                'is_active' => true,
            ],
            [
                'name' => 'تصميم واجهات المستخدم',
                'description' => 'تصميم واجهات مستخدم جذابة وسهلة الاستخدام مع التركيز على تجربة المستخدم لضمان تفاعل أفضل ومعدلات تحويل أعلى',
                'color' => '#EC4899',
                'icon' => 'ri-layout-masonry-line',
                'is_active' => true,
            ],
            [
                'name' => 'إدارة وسائل التواصل الاجتماعي',
                'description' => 'إدارة احترافية لحساباتك على منصات التواصل الاجتماعي تشمل إنشاء المحتوى والجدولة والتفاعل مع الجمهور وتحليل الأداء',
                'color' => '#06B6D4',
                'icon' => 'ri-instagram-line',
                'is_active' => true,
            ],
            [
                'name' => 'تحسين محركات البحث',
                'description' => 'تحسين ظهور موقعك في نتائج البحث من خلال استراتيجيات SEO متقدمة تشمل تحسين المحتوى والروابط والأداء التقني للموقع',
                'color' => '#EF4444',
                'icon' => 'ri-search-eye-line',
                'is_active' => true,
            ],
            [
                'name' => 'المتاجر الإلكترونية',
                'description' => 'إنشاء متاجر إلكترونية متكاملة مع بوابات الدفع وإدارة المخزون والشحن لتمكينك من البيع عبر الإنترنت بكل سهولة واحترافية',
                'color' => '#F97316',
                'icon' => 'ri-shopping-bag-3-line',
                'is_active' => true,
            ],
            [
                'name' => 'الأنظمة الإدارية المتكاملة',
                'description' => 'تطوير أنظمة إدارية مخصصة تشمل إدارة الموارد البشرية والمحاسبة والمخزون وإدارة العملاء لتسهيل العمليات وزيادة الإنتاجية',
                'color' => '#6366F1',
                'icon' => 'ri-dashboard-line',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            BusinessServiceCategory::create($category);
        }
    }
}
