<?php

return [
    'title' => 'المستخدمين',
    'create' => 'إضافة مستخدم',
    'edit' => 'تعديل المستخدم',
    'show' => 'بيانات المستخدم',

    'fields' => [
        'name' => 'الاسم',
        'name_ar' => 'الاسم بالعربي',
        'name_en' => 'الاسم بالإنجليزي',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الجوال',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'status' => 'الحالة',
        'user_type' => 'نوع المستخدم',
        'agency' => 'المكتب الخارجي',
        'preferred_language' => 'اللغة المفضلة',
        'avatar' => 'الصورة',
        'last_login_at' => 'آخر دخول',
        'must_change_password' => 'إجبار تغيير كلمة المرور عند الدخول القادم',
        'roles' => 'الأدوار',
        'branches' => 'الفروع',
        'all_branches' => 'كل الفروع',
        'created_by' => 'أنشئ بواسطة',
        'two_factor' => 'التحقق بخطوتين',
    ],

    'statuses' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'locked' => 'مقفل',
    ],

    'types' => [
        'internal' => 'موظف',
        'external_agency' => 'مكتب خارجي',
        'driver' => 'سائق',
    ],

    'sections' => [
        'basic' => 'البيانات الأساسية',
        'account' => 'بيانات الحساب',
        'access' => 'الأدوار والفروع',
    ],

    'search_placeholder' => 'ابحث بالاسم أو البريد أو الجوال...',
    'password_hint_edit' => 'اتركها فارغة إذا لا تريد تغييرها.',
    'all_branches_hint' => 'يمنح المستخدم صلاحية الوصول لبيانات كل الفروع.',
    'own_access_locked' => 'لا يمكنك تعديل أدوارك أو فروعك.',
    'no_roles' => 'بدون أدوار',

    'tabs' => [
        'info' => 'البيانات',
        'roles' => 'الأدوار',
        'branches' => 'الفروع',
        'activity' => 'النشاط',
    ],

    'actions' => [
        'activate' => 'تفعيل',
        'deactivate' => 'إيقاف',
        'reset_password' => 'إرسال رابط استعادة كلمة المرور',
        'force_change' => 'إجبار تغيير كلمة المرور عند الدخول القادم',
        'send' => 'إرسال',
    ],

    'activity' => [
        'logins' => 'محاولات الدخول',
        'recent_actions' => 'آخر الإجراءات',
        'changes' => 'التغييرات على الحساب',
        'date' => 'التاريخ',
        'result' => 'النتيجة',
        'success' => 'ناجح',
        'failed' => 'فاشل',
        'ip' => 'عنوان IP',
        'device' => 'الجهاز',
        'by' => 'بواسطة',
        'reasons' => [
            'invalid_credentials' => 'بيانات غير صحيحة',
            'inactive' => 'حساب غير نشط',
            'locked_out' => 'مقفل مؤقتاً',
        ],
    ],

    'confirm_deactivate' => 'سيتم تسجيل خروج المستخدم فوراً ولن يتمكن من الدخول.',

    'flash' => [
        'created' => 'تم إضافة المستخدم.',
        'updated' => 'تم تحديث بيانات المستخدم.',
        'deleted' => 'تم حذف المستخدم.',
        'activated' => 'تم تفعيل المستخدم.',
        'deactivated' => 'تم إيقاف المستخدم وتسجيل خروجه.',
        'reset_link_sent' => 'تم إرسال رابط استعادة كلمة المرور.',
        'cannot_remove_last_admin_role' => 'لا يمكن إزالة دور مدير النظام من آخر مدير نظام نشط.',
    ],
];
