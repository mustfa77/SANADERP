<?php

return [
    'title' => 'التنبيهات',
    'hint' => 'تُحسب كل ساعة من البيانات والمدد في الإعدادات > التنبيهات. يُغلق التنبيه تلقائياً عند زوال سببه، أو عند تسجيله كمُعالَج.',
    'none' => 'لا توجد تنبيهات.',
    'view_all' => 'كل التنبيهات',
    'refresh' => 'تحديث الآن',
    'resolve' => 'تمت المعالجة',
    'show_resolved' => 'عرض المغلقة',
    'show_open' => 'عرض المفتوحة',
    'auto_resolved' => 'أُغلق تلقائياً',

    'fields' => [
        'type' => 'التنبيه',
        'message' => 'التفاصيل',
        'due_at' => 'التاريخ',
        'branch' => 'الفرع',
    ],

    'types' => [
        'iqama_expiry' => 'انتهاء الإقامة',
        'passport_expiry' => 'انتهاء الجواز',
        'medical_expiry' => 'انتهاء الفحص الطبي',
        'contract_end' => 'نهاية عقد',
        'probation_end' => 'نهاية التجربة',
        'overdue_stage' => 'تأخر مرحلة سفر',
        'hold_expired' => 'انتهاء مدة الحجز',
        'worker_absent' => 'غياب من السكن',
        'trip_unassigned' => 'رحلة بدون سائق',
        'license_expiry' => 'انتهاء رخصة سائق',
        'case_waiting' => 'حالة بانتظار قرار',
    ],

    'messages' => [
        'iqama_expiry' => 'إقامة :worker (:number) تنتهي في :date.',
        'passport_expiry' => 'جواز :worker (:number) ينتهي في :date.',
        'medical_expiry' => 'الفحص الطبي لـ :worker (:number) ينتهي في :date.',
        'contract_end' => 'العقد :number (:worker — :client) ينتهي في :date.',
        'probation_end' => 'تجربة :worker (:client، طلب :number) تنتهي في :date.',
        'overdue_stage' => 'الطلب :number: مرحلة ":stage" متأخرة :days يوم.',
        'hold_expired' => ':worker محجوز للعميل :client (طلب :number) أكثر من المدة المسموحة.',
        'worker_absent' => ':worker غائب اليوم من :camp.',
        'trip_unassigned' => 'الرحلة :number الساعة :time بدون سائق.',
        'license_expiry' => 'رخصة السائق :driver تنتهي في :date.',
        'case_waiting' => 'الحالة :number للعامل :worker بانتظار قرار منذ :days يوم.',
    ],

    'flash' => [
        'refreshed' => 'تم تحديث التنبيهات: :created جديد، :resolved أُغلق.',
        'resolved' => 'تم تسجيل التنبيه كمُعالَج.',
    ],
];
