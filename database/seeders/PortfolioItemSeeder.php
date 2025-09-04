<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PortfolioItem;
use App\Models\BusinessServiceCategory;

class PortfolioItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories
        $designCategory = BusinessServiceCategory::where('name', 'تصميم')->first();
        $programmingCategory = BusinessServiceCategory::where('name', 'برمجة')->first();
        $marketingCategory = BusinessServiceCategory::where('name', 'تسويق رقمي')->first();
        $consultingCategory = BusinessServiceCategory::where('name', 'استشارات')->first();

        // Design Portfolio Items
        if ($designCategory) {
            PortfolioItem::create([
                'project_name' => 'تصميم هوية بصرية لشركة التقنية الإسلامية',
                'project_description' => 'تصميم هوية بصرية متكاملة لشركة ناشئة في مجال التقنية الإسلامية، شملت تصميم الشعار والهوية البصرية والمواد التسويقية.',
                'service_category_id' => $designCategory->id,
                'project_image' => 'portfolio/islamic-tech-brand.jpg',
                'project_features' => [
                    ['feature' => 'تصميم شعار عصري'],
                    ['feature' => 'هوية بصرية متكاملة'],
                    ['feature' => 'مواد تسويقية متنوعة'],
                    ['feature' => 'دليل استخدام الهوية'],
                ],
                'is_active' => true,
                'sort_order' => 1,
            ]);

            PortfolioItem::create([
                'project_name' => 'تصميم موقع إلكتروني لمؤسسة تعليمية',
                'project_description' => 'تصميم موقع إلكتروني احترافي لمؤسسة تعليمية إسلامية، مع التركيز على سهولة الاستخدام والتصميم الجذاب.',
                'service_category_id' => $designCategory->id,
                'project_image' => 'portfolio/educational-website.jpg',
                'project_features' => [
                    ['feature' => 'تصميم متجاوب'],
                    ['feature' => 'واجهة مستخدم سهلة'],
                    ['feature' => 'ألوان هادئة ومناسبة'],
                    ['feature' => 'تصميم صفحات متعددة'],
                ],
                'is_active' => true,
                'sort_order' => 2,
            ]);
        }

        // Programming Portfolio Items
        if ($programmingCategory) {
            PortfolioItem::create([
                'project_name' => 'تطبيق إدارة الحلقات القرآنية',
                'project_description' => 'تطبيق ويب متقدم لإدارة الحلقات القرآنية، يتضمن نظام حضور ومتابعة التقدم وإدارة المعلمين والطلاب.',
                'service_category_id' => $programmingCategory->id,
                'project_image' => 'portfolio/quran-circles-app.jpg',
                'project_features' => [
                    ['feature' => 'نظام إدارة متكامل'],
                    ['feature' => 'تقارير وإحصائيات'],
                    ['feature' => 'واجهة مستخدم متقدمة'],
                    ['feature' => 'نظام تنبيهات'],
                    ['feature' => 'دعم متعدد اللغات'],
                ],
                'is_active' => true,
                'sort_order' => 3,
            ]);

            PortfolioItem::create([
                'project_name' => 'منصة تعليمية إلكترونية',
                'project_description' => 'منصة تعليمية شاملة تدعم الدورات المسجلة والمباشرة، مع نظام إدارة محتوى متقدم ونظام مدفوعات.',
                'service_category_id' => $programmingCategory->id,
                'project_image' => 'portfolio/elearning-platform.jpg',
                'project_features' => [
                    ['feature' => 'نظام إدارة محتوى'],
                    ['feature' => 'دعم الفيديو المباشر'],
                    ['feature' => 'نظام مدفوعات آمن'],
                    ['feature' => 'تطبيق موبايل'],
                    ['feature' => 'نظام تقارير متقدم'],
                ],
                'is_active' => true,
                'sort_order' => 4,
            ]);
        }

        // Digital Marketing Portfolio Items
        if ($marketingCategory) {
            PortfolioItem::create([
                'project_name' => 'حملة تسويقية لمركز تعليمي',
                'project_description' => 'حملة تسويقية شاملة لمركز تعليمي إسلامي، شملت إدارة وسائل التواصل الاجتماعي وإعلانات جوجل وتحسين محركات البحث.',
                'service_category_id' => $marketingCategory->id,
                'project_image' => 'portfolio/educational-marketing.jpg',
                'project_features' => [
                    ['feature' => 'إدارة وسائل التواصل'],
                    ['feature' => 'إعلانات جوجل'],
                    ['feature' => 'تحسين محركات البحث'],
                    ['feature' => 'إنشاء محتوى تسويقي'],
                    ['feature' => 'تقارير الأداء'],
                ],
                'is_active' => true,
                'sort_order' => 5,
            ]);

            PortfolioItem::create([
                'project_name' => 'استراتيجية تسويقية لشركة تقنية',
                'project_description' => 'استراتيجية تسويقية متكاملة لشركة تقنية ناشئة، مع التركيز على بناء العلامة التجارية وجذب العملاء المستهدفين.',
                'service_category_id' => $marketingCategory->id,
                'project_image' => 'portfolio/tech-marketing.jpg',
                'project_features' => [
                    ['feature' => 'بناء العلامة التجارية'],
                    ['feature' => 'استراتيجية المحتوى'],
                    ['feature' => 'إدارة العلاقات العامة'],
                    ['feature' => 'تحليل السوق'],
                    ['feature' => 'خطة التسويق الرقمي'],
                ],
                'is_active' => true,
                'sort_order' => 6,
            ]);
        }

        // Consulting Portfolio Items
        if ($consultingCategory) {
            PortfolioItem::create([
                'project_name' => 'استشارة تطوير الأعمال لمؤسسة خيرية',
                'project_description' => 'استشارة شاملة لتطوير الأعمال لمؤسسة خيرية إسلامية، شملت تحليل الأداء وتطوير الاستراتيجية وتحسين العمليات.',
                'service_category_id' => $consultingCategory->id,
                'project_image' => 'portfolio/charity-consulting.jpg',
                'project_features' => [
                    ['feature' => 'تحليل الأداء الحالي'],
                    ['feature' => 'تطوير الاستراتيجية'],
                    ['feature' => 'تحسين العمليات'],
                    ['feature' => 'خطة التنفيذ'],
                    ['feature' => 'متابعة النتائج'],
                ],
                'is_active' => true,
                'sort_order' => 7,
            ]);

            PortfolioItem::create([
                'project_name' => 'استشارة التحول الرقمي لجامعة',
                'project_description' => 'استشارة متخصصة في التحول الرقمي لجامعة إسلامية، مع التركيز على تحديث البنية التحتية التقنية وتحسين الخدمات.',
                'service_category_id' => $consultingCategory->id,
                'project_image' => 'portfolio/digital-transformation.jpg',
                'project_features' => [
                    ['feature' => 'تقييم البنية التحتية'],
                    ['feature' => 'خطة التحول الرقمي'],
                    ['feature' => 'تحديث الأنظمة'],
                    ['feature' => 'تدريب الموظفين'],
                    ['feature' => 'متابعة التنفيذ'],
                ],
                'is_active' => true,
                'sort_order' => 8,
            ]);
        }
    }
}
