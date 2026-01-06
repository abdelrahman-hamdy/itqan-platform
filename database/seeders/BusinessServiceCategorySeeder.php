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
                'name' => 'تصميم شعارات',
                'description' => 'تصميم شعارات احترافية تعبر عن هوية شركتك',
                'color' => '#3B82F6',
                'icon' => 'heroicon-o-paint-brush',
                'is_active' => true,
            ],
            [
                'name' => 'هوية بصرية',
                'description' => 'تصميم هوية بصرية متكاملة لشركتك',
                'color' => '#10B981',
                'icon' => 'heroicon-o-eye',
                'is_active' => true,
            ],
            [
                'name' => 'تصاميم مطبوعة',
                'description' => 'تصاميم مطبوعة احترافية للمنشورات والكتيبات',
                'color' => '#F59E0B',
                'icon' => 'heroicon-o-document',
                'is_active' => true,
            ],
            [
                'name' => 'تصاميم سوشال ميديا',
                'description' => 'تصاميم جذابة لمنصات التواصل الاجتماعي',
                'color' => '#8B5CF6',
                'icon' => 'heroicon-o-share',
                'is_active' => true,
            ],
            [
                'name' => 'تطوير مواقع',
                'description' => 'تطوير مواقع احترافية متجاوبة',
                'color' => '#EF4444',
                'icon' => 'heroicon-o-globe-alt',
                'is_active' => true,
            ],
            [
                'name' => 'متاجر إلكترونية',
                'description' => 'تطوير متاجر إلكترونية متكاملة',
                'color' => '#06B6D4',
                'icon' => 'heroicon-o-shopping-cart',
                'is_active' => true,
            ],
            [
                'name' => 'تطبيقات جوال',
                'description' => 'تطوير تطبيقات جوال احترافية',
                'color' => '#84CC16',
                'icon' => 'heroicon-o-device-phone-mobile',
                'is_active' => true,
            ],
            [
                'name' => 'أنظمة إدارية',
                'description' => 'تطوير أنظمة إدارية مخصصة',
                'color' => '#F97316',
                'icon' => 'heroicon-o-cog',
                'is_active' => true,
            ],
            [
                'name' => 'تحسين محركات البحث',
                'description' => 'خدمات SEO لتحسين ظهور موقعك',
                'color' => '#EC4899',
                'icon' => 'heroicon-o-magnifying-glass',
                'is_active' => true,
            ],
            [
                'name' => 'إعلانات جوجل',
                'description' => 'إدارة حملات إعلانات جوجل',
                'color' => '#14B8A6',
                'icon' => 'heroicon-o-ad',
                'is_active' => true,
            ],
            [
                'name' => 'إعلانات السوشال ميديا',
                'description' => 'إدارة حملات إعلانات منصات التواصل',
                'color' => '#6366F1',
                'icon' => 'heroicon-o-megaphone',
                'is_active' => true,
            ],
            [
                'name' => 'التسويق بالبريد الإلكتروني',
                'description' => 'حملات تسويقية عبر البريد الإلكتروني',
                'color' => '#A855F7',
                'icon' => 'heroicon-o-envelope',
                'is_active' => true,
            ],
            [
                'name' => 'إدارة المحتوى',
                'description' => 'إنشاء وإدارة محتوى احترافي',
                'color' => '#F43F5E',
                'icon' => 'heroicon-o-document-text',
                'is_active' => true,
            ],
            [
                'name' => 'إدارة السوشال ميديا',
                'description' => 'إدارة حسابات التواصل الاجتماعي',
                'color' => '#0EA5E9',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            BusinessServiceCategory::create($category);
        }
    }
}
