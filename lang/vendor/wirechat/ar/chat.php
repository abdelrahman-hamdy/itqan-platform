<?php

return [

    /**-------------------------
     * Chat
     *------------------------*/
    'labels' => [

        'you_replied_to_yourself' => 'لقد رددت على نفسك',
        'participant_replied_to_you' => ':sender رد عليك',
        'participant_replied_to_themself' => ':sender رد على نفسه',
        'participant_replied_other_participant' => ':sender رد على :receiver',
        'you' => 'أنت',
        'user' => 'مستخدم',
        'replying_to' => 'الرد على :participant',
        'replying_to_yourself' => 'الرد على نفسك',
        'attachment' => 'مرفق',
        'media' => 'الوسائط',
        'files' => 'الملفات',
        'media_and_files' => 'الوسائط والملفات',
        'no_media' => 'لا توجد وسائط',
        'no_media_description' => 'الصور والفيديوهات المشتركة ستظهر هنا',
        'no_files' => 'لا توجد ملفات',
        'no_files_description' => 'المستندات والملفات المشتركة ستظهر هنا',
    ],

    'inputs' => [
        'message' => [
            'label' => 'رسالة',
            'placeholder' => 'اكتب رسالة',
        ],
        'media' => [
            'label' => 'وسائط',
            'placeholder' => 'وسائط',
        ],
        'files' => [
            'label' => 'ملفات',
            'placeholder' => 'ملفات',
        ],
    ],

    'message_groups' => [
        'today' => 'اليوم',
        'yesterday' => 'أمس',

    ],

    'actions' => [
        'open_group_info' => [
            'label' => 'معلومات المجموعة',
        ],
        'open_chat_info' => [
            'label' => 'معلومات المحادثة',
        ],
        'close_chat' => [
            'label' => 'إغلاق المحادثة',
        ],
        'clear_chat' => [
            'label' => 'مسح سجل المحادثة',
            'confirmation_message' => 'هل أنت متأكد من مسح سجل محادثاتك؟ سيتم مسح محادثتك فقط ولن يؤثر ذلك على المشاركين الآخرين.',
        ],
        'delete_chat' => [
            'label' => 'حذف المحادثة',
            'confirmation_message' => 'هل أنت متأكد من حذف هذه المحادثة؟ سيتم حذف المحادثة من جهتك فقط ولن تُحذف للمشاركين الآخرين.',
        ],

        'delete_for_everyone' => [
            'label' => 'حذف للجميع',
            'confirmation_message' => 'هل أنت متأكد؟',
        ],
        'delete_for_me' => [
            'label' => 'حذف من عندي',
            'confirmation_message' => 'هل أنت متأكد؟',
        ],
        'reply' => [
            'label' => 'رد',
        ],

        'exit_group' => [
            'label' => 'مغادرة المجموعة',
            'confirmation_message' => 'هل أنت متأكد من مغادرة هذه المجموعة؟',
        ],
        'upload_file' => [
            'label' => 'ملف',
            'description' => 'مستندات وملفات',
        ],
        'upload_media' => [
            'label' => 'صور وفيديوهات',
            'description' => 'صور وفيديوهات',
        ],
    ],

    'messages' => [

        'cannot_exit_self_or_private_conversation' => 'لا يمكن مغادرة محادثة شخصية أو ذاتية',
        'owner_cannot_exit_conversation' => 'لا يمكن للمالك مغادرة المحادثة',
        'rate_limit' => 'محاولات كثيرة جداً! الرجاء التمهل',
        'conversation_not_found' => 'المحادثة غير موجودة.',
        'conversation_id_required' => 'معرّف المحادثة مطلوب',
        'invalid_conversation_input' => 'إدخال محادثة غير صالح.',
    ],

    /**-------------------------
     * Info Component
     *------------------------*/

    'info' => [
        'heading' => [
            'label' => 'معلومات المحادثة',
        ],
        'labels' => [
            'media_and_files' => 'الوسائط والملفات',
            'media' => 'الوسائط',
            'files' => 'الملفات',
            'no_media' => 'لا توجد وسائط مشتركة',
            'no_files' => 'لا توجد ملفات مشتركة',
        ],
        'actions' => [
            'delete_chat' => [
                'label' => 'حذف المحادثة',
                'confirmation_message' => 'هل أنت متأكد من حذف هذه المحادثة؟ سيتم حذف المحادثة من جهتك فقط ولن تُحذف للمشاركين الآخرين.',
            ],
        ],
        'messages' => [
            'invalid_conversation_type_error' => 'المحادثات الخاصة والذاتية فقط مسموحة',
        ],

    ],

    /**-------------------------
     * Group Folder
     *------------------------*/

    'group' => [

        // Group info component
        'info' => [
            'heading' => [
                'label' => 'معلومات المجموعة',
            ],
            'labels' => [
                'members' => 'الأعضاء',
                'add_description' => 'إضافة وصف للمجموعة',
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
                'photo' => [
                    'label' => 'الصورة',
                ],
            ],
            'actions' => [
                'delete_group' => [
                    'label' => 'حذف المجموعة',
                    'confirmation_message' => 'هل أنت متأكد من حذف هذه المجموعة؟',
                    'helper_text' => 'قبل أن تتمكن من حذف المجموعة، تحتاج إلى إزالة جميع أعضاء المجموعة.',
                ],
                'add_members' => [
                    'label' => 'إضافة أعضاء',
                ],
                'group_permissions' => [
                    'label' => 'أذونات المجموعة',
                ],
                'exit_group' => [
                    'label' => 'مغادرة المجموعة',
                    'confirmation_message' => 'هل أنت متأكد من مغادرة المجموعة؟',

                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'محادثات المجموعة فقط مسموحة',
            ],
        ],
        // Members component
        'members' => [
            'heading' => [
                'label' => 'الأعضاء',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'بحث',
                    'placeholder' => 'بحث عن الأعضاء',
                ],
            ],
            'labels' => [
                'members' => 'الأعضاء',
                'owner' => 'المالك',
                'admin' => 'مشرف',
                'no_members_found' => 'لم يتم العثور على أعضاء',
            ],
            'actions' => [
                'send_message_to_yourself' => [
                    'label' => 'راسل نفسك',

                ],
                'send_message_to_member' => [
                    'label' => 'راسل :member',

                ],
                'dismiss_admin' => [
                    'label' => 'عزل من الإشراف',
                    'confirmation_message' => 'هل أنت متأكد من عزل :member من الإشراف؟',
                ],
                'make_admin' => [
                    'label' => 'جعله مشرف',
                    'confirmation_message' => 'هل أنت متأكد من جعل :member مشرفاً؟',
                ],
                'remove_from_group' => [
                    'label' => 'إزالة',
                    'confirmation_message' => 'هل أنت متأكد من إزالة :member من هذه المجموعة؟',
                ],
                'load_more' => [
                    'label' => 'تحميل المزيد',
                ],

            ],
            'messages' => [
                'invalid_conversation_type_error' => 'محادثات المجموعة فقط مسموحة',
            ],
        ],
        // add-Members component
        'add_members' => [
            'heading' => [
                'label' => 'إضافة أعضاء',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'بحث',
                    'placeholder' => 'بحث',
                ],
            ],
            'labels' => [

            ],
            'actions' => [
                'save' => [
                    'label' => 'حفظ',

                ],

            ],
            'messages' => [
                'invalid_conversation_type_error' => 'محادثات المجموعة فقط مسموحة',
                'members_limit_error' => 'لا يمكن أن يتجاوز عدد الأعضاء :count',
                'member_already_exists' => 'تمت الإضافة بالفعل إلى المجموعة',
            ],
        ],
        // permissions component
        'permissions' => [
            'heading' => [
                'label' => 'الأذونات',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'بحث',
                    'placeholder' => 'بحث',
                ],
            ],
            'labels' => [
                'members_can' => 'يمكن للأعضاء',

            ],
            'actions' => [
                'edit_group_information' => [
                    'label' => 'تعديل معلومات المجموعة',
                    'helper_text' => 'يشمل ذلك الاسم والأيقونة والوصف',
                ],
                'send_messages' => [
                    'label' => 'إرسال الرسائل',
                ],
                'add_other_members' => [
                    'label' => 'إضافة أعضاء آخرين',
                ],

            ],
            'messages' => [
            ],
        ],

    ],

];
