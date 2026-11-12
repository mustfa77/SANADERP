<?php

return [
    'title' => 'الاسترجاعات',
    'none' => 'لا توجد طلبات استرجاع.',
    'confirm_approve' => 'اعتماد استرجاع :amount للعميل؟ سيُخصم من المبلغ المدفوع.',

    'fields' => [
        'payable' => 'العقد / الحجز',
        'amount' => 'مبلغ الاسترجاع',
        'reason' => 'السبب',
        'requested' => 'طلبه',
        'status' => 'الحالة',
    ],

    'statuses' => [
        'pending' => 'بانتظار الاعتماد',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
    ],

    'actions' => [
        'approve' => 'اعتماد',
        'reject' => 'رفض',
    ],

    'flash' => [
        'approved' => 'تم اعتماد الاسترجاع.',
        'rejected' => 'تم رفض الاسترجاع.',
    ],
];
