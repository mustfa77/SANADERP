<?php

return [
    'title' => 'الدفعات',
    'record' => 'تسجيل دفعة',
    'verified' => 'معتمدة',
    'pending' => 'غير معتمدة',
    'view_receipt' => 'الإشعار',
    'confirm_delete' => 'ستُحذف الدفعة (يمكن حذف الدفعات غير المعتمدة فقط).',

    'summary' => [
        'total' => 'الإجمالي',
        'paid' => 'المدفوع',
        'balance' => 'المتبقي',
    ],

    'hints' => [
        'receipt' => 'إشعار التحويل أو الإيصال (PDF أو صورة).',
    ],

    'fields' => [
        'amount' => 'المبلغ',
        'paid_at' => 'تاريخ الدفع',
        'payment_method_id' => 'طريقة الدفع',
        'reference' => 'رقم الحوالة / المرجع',
        'receipt' => 'إشعار التحويل',
        'notes' => 'ملاحظات',
    ],

    'statuses' => [
        'unpaid' => 'غير مدفوع',
        'partial' => 'مدفوع جزئياً',
        'paid' => 'مدفوع',
    ],

    'actions' => [
        'record' => 'تسجيل',
        'verify' => 'اعتماد',
        'delete' => 'حذف الدفعة',
    ],

    'flash' => [
        'recorded' => 'تم تسجيل الدفعة.',
        'verified' => 'تم اعتماد الدفعة.',
        'deleted' => 'تم حذف الدفعة.',
    ],
];
