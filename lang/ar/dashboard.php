<?php

return [
    'title' => 'لوحة التحكم',
    'demo_notice' => 'الأرقام المعروضة بيانات تجريبية للعرض فقط، وستُستبدل بالأرقام الفعلية عند تشغيل وحدات الاستقدام والتأجير والمالية.',
    'from_previous' => 'عن الشهر السابق',
    'sar' => 'ر.س',
    'total' => 'الإجمالي',

    'kpis' => [
        'workers_on_duty' => 'العمالة على رأس العمل',
        'active_rentals' => 'عقود التأجير النشطة',
        'recruitment_in_progress' => 'طلبات استقدام جارية',
        'month_revenue' => 'إيرادات الشهر',
    ],

    'overview' => 'الإيرادات والعقود',
    'overview_contracts' => 'العقود الجديدة',
    'last_12_months' => 'آخر 12 شهر',
    'chart' => [
        'rental' => 'إيرادات التأجير',
        'recruitment' => 'رسوم الاستقدام',
        'contracts' => 'عقود جديدة',
    ],
    'totals' => [
        'rental_revenue' => 'إيرادات التأجير',
        'recruitment_fees' => 'رسوم الاستقدام',
        'refunds' => 'المسترجعات',
    ],

    'workers_by_nationality' => 'العمالة حسب الجنسية',
    'nationalities' => [
        'philippines' => 'الفلبين',
        'indonesia' => 'إندونيسيا',
        'kenya' => 'كينيا',
        'uganda' => 'أوغندا',
        'ethiopia' => 'إثيوبيا',
        'sri_lanka' => 'سريلانكا',
    ],

    'recruitment_pipeline' => 'مراحل الاستقدام',
    'pipeline' => [
        'new_request' => 'طلب جديد',
        'candidate_selected' => 'تم اختيار المرشحة',
        'visa_issued' => 'صدرت التأشيرة',
        'ticket_booked' => 'تم حجز التذكرة',
        'arrived' => 'وصلت (بانتظار التسليم)',
    ],

    'needs_follow_up' => 'تحتاج متابعة',
    'alerts' => [
        'iqama_expiring' => ['title' => 'إقامات تنتهي قريباً', 'hint' => 'خلال 30 يوماً'],
        'passport_expiring' => ['title' => 'جوازات تنتهي قريباً', 'hint' => 'خلال 6 أشهر'],
        'rentals_ending' => ['title' => 'عقود تأجير تنتهي', 'hint' => 'خلال 7 أيام'],
        'workers_in_camp' => ['title' => 'عمالة في السكن', 'hint' => 'متاحة للتأجير بدون عقد'],
        'overdue_invoices' => ['title' => 'فواتير متأخرة السداد', 'hint' => 'تجاوزت تاريخ الاستحقاق'],
        'open_cases' => ['title' => 'قضايا وبلاغات مفتوحة', 'hint' => 'هروب أو شكاوى قيد المعالجة'],
    ],

    'by_branch' => 'حسب الفرع',
    'no_branches' => 'لا توجد فروع نشطة.',
    'branch' => 'الفرع',
    'workers' => 'العمالة',
    'contracts' => 'العقود',
    'revenue' => 'الإيرادات',

    'latest_contracts' => 'أحدث العقود',
    'table' => [
        'number' => 'رقم العقد',
        'client' => 'العميل',
        'worker' => 'العاملة',
        'type' => 'النوع',
        'start' => 'تاريخ البداية',
        'amount' => 'المبلغ',
        'status' => 'الحالة',
    ],
    'contract_types' => [
        'monthly' => 'تأجير شهري',
        'hourly' => 'تأجير بالساعة',
        'recruitment' => 'استقدام',
    ],
    'contract_statuses' => [
        'active' => 'ساري',
        'pending' => 'قيد الإجراء',
        'ended' => 'منتهي',
        'cancelled' => 'ملغي',
    ],
];
