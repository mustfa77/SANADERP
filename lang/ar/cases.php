<?php

return [
    'title' => 'الحالات',
    'show' => 'الحالة :number',
    'hint' => 'تُفتح الحالة تلقائياً عندما يصبح العامل "بانتظار قرار" (أرجعه العميل، أو تغيير حالة يدوي). القرار: نقل كفالة، أو ترحيل، أو عودة للعمل.',
    'search_placeholder' => 'رقم الحالة أو اسم العامل...',
    'none' => 'لا توجد حالات.',
    'days' => ':count يوم',
    'decide_title' => 'القرار',
    'waiting_decision' => 'بانتظار قرار المدير (صلاحية "اتخاذ قرار الحالات").',
    'confirm_decide' => 'ستتغير حالة العامل حسب القرار.',
    'confirm_complete' => 'ستُغلق الحالة ويأخذ العامل حالته النهائية.',
    'other_sponsor' => 'كفيل آخر (اكتب الاسم)',
    'notes' => 'المتابعة',
    'add_note' => 'أضف ملاحظة متابعة...',
    'no_notes' => 'لا توجد ملاحظات بعد.',

    'complete_title' => [
        'transfer' => 'تم نقل الكفالة',
        'deportation' => 'تم ترحيل العامل',
    ],
    'complete_hint' => [
        'transfer' => 'عند اكتمال نقل الكفالة سجّل التاريخ: يصبح العامل "منقول الكفالة".',
        'deportation' => 'عند مغادرة العامل للمملكة سجّل التاريخ: يصبح العامل "مُرحّل".',
    ],

    'fields' => [
        'number' => 'رقم الحالة',
        'worker_id' => 'العامل',
        'reason' => 'السبب',
        'related' => 'المصدر',
        'opened_at' => 'تاريخ الفتح',
        'decision' => 'القرار',
        'decision_note' => 'ملاحظة القرار',
        'status' => 'الحالة',
        'new_sponsor' => 'الكفيل الجديد',
        'new_sponsor_client_id' => 'الكفيل الجديد (عميل)',
        'new_sponsor_name' => 'اسم الكفيل الجديد',
        'new_sponsor_id_number' => 'رقم هوية الكفيل الجديد',
        'transfer_fee' => 'رسوم النقل',
        'flight_date' => 'تاريخ الرحلة',
        'ticket_cost' => 'تكلفة التذكرة',
        'completed_at' => 'تاريخ الإنجاز',
        'note' => 'ملاحظة',
    ],

    'statuses' => [
        'open' => 'بانتظار القرار',
        'in_progress' => 'قيد التنفيذ',
        'closed' => 'مغلقة',
    ],

    'decisions' => [
        'transfer' => 'نقل كفالة',
        'deportation' => 'ترحيل',
        'return_to_work' => 'عودة للعمل',
    ],

    'actions' => [
        'decide' => 'حفظ القرار',
        'complete' => 'إنجاز',
        'add_note' => 'إضافة',
    ],

    'reasons' => [
        'decided' => 'الحالة :number: :decision',
        'completed' => 'إنجاز الحالة :number: :decision',
    ],

    'errors' => [
        'worker_not_awaiting' => 'العامل لم يعد بانتظار قرار (الحالة ":status").',
        'sponsor_required' => 'اختر الكفيل الجديد أو اكتب اسمه.',
        'wrong_status' => 'لا يمكن ذلك والحالة ":status".',
        'date_before_decision' => 'التاريخ قبل تاريخ القرار.',
    ],

    'flash' => [
        'decided' => 'تم حفظ القرار.',
        'completed' => 'تم إغلاق الحالة.',
        'note' => 'تمت إضافة الملاحظة.',
    ],
];
