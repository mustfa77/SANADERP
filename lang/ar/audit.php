<?php

return [
    'title' => 'سجل التدقيق',
    'show' => 'تفاصيل السجل',
    'system' => 'النظام',

    'fields' => [
        'date' => 'التاريخ',
        'user' => 'المستخدم',
        'action' => 'الإجراء',
        'description' => 'الوصف',
        'target' => 'السجل المتأثر',
        'ip' => 'عنوان IP',
        'user_agent' => 'المتصفح / الجهاز',
        'old_values' => 'القيم السابقة',
        'new_values' => 'القيم الجديدة',
        'field' => 'الحقل',
    ],

    'filters' => [
        'from' => 'من تاريخ',
        'to' => 'إلى تاريخ',
    ],

    'targets' => [
        'User' => 'مستخدم',
        'Role' => 'دور',
        'Branch' => 'فرع',
    ],

    /*
    | اسم مختصر لكل إجراء (للفلاتر والجداول).
    */
    'action_names' => [
        'auth' => [
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
            'failed' => 'محاولة دخول فاشلة',
            'lockout' => 'قفل الدخول',
            'timeout' => 'انتهاء الجلسة',
            'password_reset' => 'استعادة كلمة المرور',
            'password_changed' => 'تغيير كلمة المرور',
        ],
        'user' => [
            'created' => 'إضافة مستخدم',
            'updated' => 'تعديل مستخدم',
            'activated' => 'تفعيل مستخدم',
            'deactivated' => 'إيقاف مستخدم',
            'deleted' => 'حذف مستخدم',
            'reset_link_sent' => 'إرسال رابط استعادة',
            'roles_assigned' => 'تغيير أدوار مستخدم',
            'branches_assigned' => 'تغيير فروع مستخدم',
        ],
        'role' => [
            'created' => 'إضافة دور',
            'cloned' => 'نسخ دور',
            'updated' => 'تعديل دور',
            'deleted' => 'حذف دور',
            'permissions_changed' => 'تغيير الصلاحيات',
        ],
        'branch' => [
            'created' => 'إضافة فرع',
            'updated' => 'تعديل فرع',
            'activated' => 'تفعيل فرع',
            'deactivated' => 'إيقاف فرع',
            'deleted' => 'حذف فرع',
        ],
        'profile' => [
            'two_factor_enabled' => 'تفعيل التحقق بخطوتين',
            'two_factor_disabled' => 'إيقاف التحقق بخطوتين',
        ],
        'settings' => [
            'updated' => 'تعديل الإعدادات',
        ],
        'maintenance' => [
            'enabled' => 'تفعيل وضع الصيانة',
            'disabled' => 'إيقاف وضع الصيانة',
        ],
        'backup' => [
            'created' => 'إنشاء نسخة احتياطية',
            'downloaded' => 'تحميل نسخة احتياطية',
            'deleted' => 'حذف نسخة احتياطية',
            'failed' => 'فشل النسخ الاحتياطي',
        ],
    ],

    /*
    | نص الوصف الكامل لكل إجراء.
    */
    'actions' => [
        'auth' => [
            'login' => 'سجّل :name الدخول',
            'logout' => 'سجّل :name الخروج',
            'failed' => 'محاولة دخول فاشلة بالبريد :email',
            'lockout' => 'تم قفل الدخول للبريد :email بعد محاولات فاشلة متكررة',
            'timeout' => 'انتهت الجلسة بسبب عدم النشاط',
            'password_reset' => 'استعاد :name كلمة المرور',
            'password_changed' => 'غيّر :name كلمة المرور',
        ],
        'user' => [
            'created' => 'تمت إضافة المستخدم :name',
            'updated' => 'تم تعديل بيانات المستخدم :name',
            'activated' => 'تم تفعيل المستخدم :name',
            'deactivated' => 'تم إيقاف المستخدم :name',
            'deleted' => 'تم حذف المستخدم :name',
            'reset_link_sent' => 'تم إرسال رابط استعادة كلمة المرور للمستخدم :name',
            'roles_assigned' => 'تم تغيير أدوار المستخدم :name',
            'branches_assigned' => 'تم تغيير فروع المستخدم :name',
        ],
        'role' => [
            'created' => 'تمت إضافة الدور :role',
            'cloned' => 'تم إنشاء الدور :role بنسخ :source',
            'updated' => 'تم تعديل الدور :role',
            'deleted' => 'تم حذف الدور :role',
            'permissions_changed' => 'تم تغيير صلاحيات الدور :role (إضافة :added، إزالة :removed)',
        ],
        'branch' => [
            'created' => 'تمت إضافة الفرع :branch',
            'updated' => 'تم تعديل الفرع :branch',
            'activated' => 'تم تفعيل الفرع :branch',
            'deactivated' => 'تم إيقاف الفرع :branch',
            'deleted' => 'تم حذف الفرع :branch',
        ],
        'profile' => [
            'two_factor_enabled' => 'فعّل :name التحقق بخطوتين',
            'two_factor_disabled' => 'أوقف :name التحقق بخطوتين',
        ],
        'settings' => [
            'updated' => 'تم تعديل الإعدادات (:section)',
        ],
        'maintenance' => [
            'enabled' => 'تم تفعيل وضع الصيانة',
            'disabled' => 'تم إيقاف وضع الصيانة',
        ],
        'backup' => [
            'created' => 'تم إنشاء النسخة الاحتياطية :file',
            'downloaded' => 'تم تحميل النسخة الاحتياطية :file',
            'deleted' => 'تم حذف النسخة الاحتياطية :file',
            'failed' => 'فشل إنشاء نسخة احتياطية: :error',
        ],
    ],

    'added' => 'مضافة',
    'removed' => 'محذوفة',
];
