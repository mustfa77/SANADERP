<?php

return [
    'title' => 'الأدوار',
    'create' => 'إضافة دور',
    'edit' => 'تعديل الدور',
    'clone' => 'نسخ',
    'permissions' => 'الصلاحيات',
    'permissions_of' => 'صلاحيات: :role',
    'copy_of' => ':name (نسخة)',
    'badge_2fa' => 'تحقق بخطوتين',

    'fields' => [
        'name' => 'المفتاح',
        'display_name' => 'اسم الدور',
        'name_ar' => 'الاسم بالعربي',
        'name_en' => 'الاسم بالإنجليزي',
        'description_ar' => 'الوصف بالعربي',
        'description_en' => 'الوصف بالإنجليزي',
        'description' => 'الوصف',
        'requires_2fa' => 'يتطلب التحقق بخطوتين (رمز بالبريد عند الدخول)',
        'users_count' => 'المستخدمين',
        'permissions_count' => 'الصلاحيات',
    ],

    'key_hint' => 'معرّف فريد بالإنجليزي، مثل rental_team. لا يمكن تغييره للأدوار النظامية.',

    'matrix' => [
        'module' => 'الوحدة',
        'other' => 'صلاحيات خاصة',
        'select_all' => 'تحديد الكل',
        'select_row' => 'تحديد الصف',
        'super_admin_locked' => 'صلاحيات مدير النظام كاملة ولا يمكن تقليلها.',
        'read_only' => 'عرض فقط — ليس لديك صلاحية تعديل الصلاحيات.',
    ],

    'flash' => [
        'created' => 'تم إنشاء الدور. حدد صلاحياته الآن.',
        'updated' => 'تم تحديث الدور.',
        'deleted' => 'تم حذف الدور.',
        'cloned' => 'تم نسخ الدور مع صلاحياته.',
        'permissions_updated' => 'تم حفظ الصلاحيات.',
        'in_use' => 'لا يمكن حذف الدور لأنه مسند إلى: :users',
    ],

    /*
    | الأدوار الافتراضية: تُنسخ إلى roles.name_ar / description_ar عبر RoleSeeder.
    */
    'defaults' => [
        'super_admin' => ['name' => 'مدير النظام', 'description' => 'صلاحيات كاملة على النظام'],
        'general_manager' => ['name' => 'المدير العام', 'description' => 'كل الفروع، عرض كل البيانات والاعتمادات'],
        'branch_manager' => ['name' => 'مدير الفرع', 'description' => 'إدارة عمليات الفرع المسند إليه'],
        'recruitment_team' => ['name' => 'فريق الاستقدام', 'description' => 'الاستقدام والمرشحين والمستندات'],
        'marketing_team' => ['name' => 'فريق التسويق', 'description' => 'عرض المرشحين وطلبات الموقع'],
        'rental_team' => ['name' => 'فريق التأجير', 'description' => 'عقود التأجير الشهري وحجوزات الساعات'],
        'accounts' => ['name' => 'الحسابات', 'description' => 'التحقق من المدفوعات والفواتير والاسترجاعات'],
        'finance_manager' => ['name' => 'المدير المالي', 'description' => 'المالية واعتماد الاسترجاع والتقارير المالية'],
        'camp_team' => ['name' => 'فريق السكن', 'description' => 'السكن وحركات السكن وعرض الحالات'],
        'transport_coordinator' => ['name' => 'منسق النقل', 'description' => 'الرحلات والسائقين'],
        'driver' => ['name' => 'سائق', 'description' => 'مسارات السائق فقط'],
        'external_agency' => ['name' => 'مكتب خارجي', 'description' => 'بوابة المكاتب الخارجية فقط'],
    ],
];
