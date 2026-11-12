<?php

return [
    /*
    | Label of a standard permission, e.g. "عرض العمالة".
    */
    'label' => ':action :module',

    'sections' => [
        'recruitment' => 'الاستقدام',
        'rental' => 'التأجير',
        'operations' => 'العمليات',
        'finance' => 'المالية',
        'administration' => 'الإدارة',
    ],

    'modules' => [
        'dashboard' => 'لوحة التحكم',
        'branches' => 'الفروع',
        'camps' => 'السكن',
        'workers' => 'العمالة',
        'clients' => 'العملاء',
        'agencies' => 'المكاتب الخارجية',
        'authorities' => 'الجهات الحكومية',
        'drivers' => 'السائقين',
        'recruitment' => 'طلبات الاستقدام',
        'recruitment_follow_up' => 'متابعة الاستقدام',
        'ready_candidates' => 'المرشحون الجاهزون',
        'candidates' => 'المرشحين',
        'documents' => 'المستندات',
        'document_types' => 'أنواع المستندات',
        'system_settings' => 'إعدادات النظام',
        'countries' => 'الدول',
        'professions' => 'المهن',
        'shifts' => 'الورديات',
        'prices' => 'الأسعار',
        'lookups' => 'القوائم',
        'completed_visa_lists' => 'قوائم التأشيرات المنجزة',
        'alert_settings' => 'إعدادات التنبيهات',
        'rental_workers' => 'العمالة المتاحة للتأجير',
        'rental_calendar' => 'تقويم التأجير',
        'rental_reports' => 'تقارير التأجير',
        'rental_contracts' => 'عقود التأجير الشهري',
        'hourly_bookings' => 'حجوزات الساعات',
        'camp_operations' => 'السكن والتحضير',
        'camp_movements' => 'حركات السكن',
        'trips' => 'الرحلات',
        'cases' => 'الحالات',
        'payments' => 'المدفوعات',
        'invoices' => 'الفواتير',
        'refunds' => 'الاسترجاعات',
        'reports' => 'التقارير',
        'alerts' => 'التنبيهات',
        'settings' => 'الإعدادات',
        'users' => 'المستخدمين',
        'roles' => 'الأدوار',
        'audit_log' => 'سجل التدقيق',
        'website_requests' => 'طلبات الموقع',
    ],

    'actions' => [
        'view' => 'عرض',
        'create' => 'إضافة',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'export' => 'تصدير',
        'print' => 'طباعة',
        'manage' => 'إدارة',
        'upload' => 'رفع',
        'replace' => 'استبدال',
        'delete_version' => 'حذف نسخة من',
        'print_authority_file' => 'طباعة ملف الجهة من',
    ],

    /*
    | Special permissions, keyed by module then action ("invoices.issue").
    */
    'special' => [
        'candidates' => ['approve' => 'اعتماد المرشح'],
        'documents' => ['authenticate' => 'تسجيل مصادقة المستند'],
        'payments' => ['verify' => 'التحقق من الدفعة'],
        'invoices' => [
            'issue' => 'إصدار الفاتورة في وافق',
            'retry' => 'إعادة إرسال فاتورة فاشلة',
        ],
        'refunds' => ['approve' => 'اعتماد الاسترجاع'],
        'cases' => ['decide' => 'اتخاذ قرار الترحيل أو نقل الكفالة'],
        'workers' => [
            'view_sensitive' => 'عرض رقم الجواز والإقامة',
            'change_status' => 'تغيير حالة العامل',
        ],
        'clients' => ['view_sensitive' => 'عرض رقم الهوية والعنوان الوطني'],
        'reports' => ['view_financial' => 'عرض التقارير المالية'],
        'branches' => ['all' => 'الوصول لكل الفروع'],
    ],
];
