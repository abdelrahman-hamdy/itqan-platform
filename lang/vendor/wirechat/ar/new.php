<?php

return [

    // new-chat component
    'chat' => [
        'labels' => [
            'heading' => 'محادثة جديدة',
            'you' => 'أنت',

        ],

        'inputs' => [
            'search' => [
                'label' => 'بحث في المحادثات',
                'placeholder' => 'بحث',
            ],
        ],

        'actions' => [
            'new_group' => [
                'label' => 'مجموعة جديدة',
            ],

        ],

        'messages' => [

            'empty_search_result' => 'لم يتم العثور على مستخدمين مطابقين للبحث.',
        ],
    ],

    // new-group component
    'group' => [
        'labels' => [
            'heading' => 'محادثة جديدة',
            'add_members' => 'إضافة أعضاء',

        ],

        'inputs' => [
            'name' => [
                'label' => 'اسم المجموعة',
                'placeholder' => 'أدخل الاسم',
            ],
            'description' => [
                'label' => 'الوصف',
                'placeholder' => 'اختياري',
            ],
            'search' => [
                'label' => 'بحث',
                'placeholder' => 'بحث',
            ],
            'photo' => [
                'label' => 'الصورة',
            ],
        ],

        'actions' => [
            'cancel' => [
                'label' => 'إلغاء',
            ],
            'next' => [
                'label' => 'التالي',
            ],
            'create' => [
                'label' => 'إنشاء',
            ],

        ],

        'messages' => [
            'members_limit_error' => 'لا يمكن أن يتجاوز عدد الأعضاء :count',
            'empty_search_result' => 'لم يتم العثور على مستخدمين مطابقين للبحث.',
        ],
    ],

];
