<?php

return [
    'checklist' => 'المستندات',
    'allowed_files' => 'الملفات المسموحة: :types — بحد أقصى :size ميجا',
    'view' => 'عرض',
    'upload' => 'رفع',
    'upload_here' => 'ارفع هنا',
    'replace' => 'استبدال',
    'delete_version' => 'حذف النسخة',
    'current' => 'الحالية',
    'version_short' => 'نسخة :version',
    'versions' => 'النسخ (:count)',
    'extra_documents' => 'مستندات إضافية',
    'add_extra' => 'إضافة مستند إضافي',
    'no_extra_documents' => 'لا توجد مستندات إضافية.',

    'print_authority_file' => 'طباعة ملف :authority',
    'missing_title' => 'مستندات ناقصة',
    'missing_intro' => 'لا يمكن طباعة ملف :authority قبل رفع المستندات الإلزامية التالية:',

    'status' => [
        'uploaded' => 'مرفوع',
        'missing' => 'غير مرفوع',
    ],

    'fields' => [
        'document' => 'المستند',
        'status' => 'الحالة',
        'uploaded' => 'تاريخ الرفع',
        'version' => 'النسخة',
        'file' => 'الملف',
        'title' => 'عنوان المستند',
    ],

    'pdf' => [
        'title' => 'ملف مقدَّم إلى :authority',
        'owner' => 'الاسم',
        'authority' => 'الجهة',
        'printed_at' => 'تاريخ الطباعة',
        'printed_by' => 'طُبع بواسطة',
        'contents' => 'محتويات الملف',
        'no_documents' => 'لا توجد مستندات مرفقة.',
        'completed_visas_title' => 'آخر :count تأشيرة منجزة — :country',
        'visa_number' => 'رقم التأشيرة',
        'worker_name' => 'الاسم',
        'issued_at' => 'تاريخ الإصدار',
        'no_completed_visas' => 'لا توجد تأشيرات منجزة بعد.',
    ],

    'flash' => [
        'uploaded' => 'تم رفع المستند ":name".',
        'replaced' => 'تم استبدال المستند ":name"، والنسخة السابقة محفوظة.',
        'version_deleted' => 'تم حذف النسخة :version.',
        'last_version' => 'لا يمكن حذف النسخة الوحيدة من المستند.',
        'cannot_merge' => 'تعذّر دمج المستند ":name" في الملف: ملف PDF بصيغة حديثة غير مدعومة. احفظه بصيغة PDF أقدم أو ارفعه كصورة، أو ثبّت أداة qpdf على الخادم.',
    ],

    'demo' => [
        'title' => 'تجربة محرك المستندات',
        'notice' => 'صفحة للتطوير فقط (تعمل عندما APP_ENV=local). المالك هنا مرشحة تجريبية.',
        'owner' => 'المالك: :name',
        'no_authority' => 'لا توجد جهة لهذه الدولة',
    ],
];
