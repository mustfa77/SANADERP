<?php

return [
    'title' => 'العملاء',
    'create' => 'إضافة عميل',
    'edit' => 'تعديل بيانات العميل',
    'show' => 'ملف العميل',
    'search_placeholder' => 'ابحث بالاسم أو الجوال...',
    'search_national_id' => 'رقم الهوية (مطابق)',

    'tabs' => [
        'overview' => 'البيانات',
        'documents' => 'المستندات',
    ],

    'fields' => [
        'name' => 'العميل',
        'name_ar' => 'الاسم بالعربي',
        'name_en' => 'الاسم بالإنجليزي',
        'national_id' => 'رقم الهوية / الإقامة',
        'phone' => 'الجوال',
        'phone2' => 'جوال آخر',
        'email' => 'البريد الإلكتروني',
        'city' => 'المدينة',
        'district' => 'الحي',
        'national_address' => 'العنوان الوطني',
        'branch_id' => 'الفرع',
        'notes' => 'ملاحظات',
        'is_active' => 'العميل نشط',
        'created_by' => 'أُضيف بواسطة',
    ],

    'validation' => [
        'duplicate_national_id' => 'رقم الهوية مسجّل لعميل آخر.',
        'open_existing' => 'فتح ملف العميل',
        'national_id_format' => 'رقم الهوية يجب أن يكون 10 أرقام ويبدأ بالرقم 1 (مواطن) أو 2 (مقيم).',
    ],

    'flash' => [
        'created' => 'تمت إضافة العميل.',
        'updated' => 'تم تحديث بيانات العميل.',
    ],
];
