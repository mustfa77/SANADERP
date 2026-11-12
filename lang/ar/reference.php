<?php

return [
    'add' => 'إضافة :item',
    'edit' => 'تعديل :item',
    'activate' => 'تفعيل',
    'deactivate' => 'إيقاف',
    'confirm_deactivate' => 'لن يظهر في الاختيارات الجديدة، وتبقى السجلات المرتبطة به كما هي.',
    'order' => 'الترتيب',
    'move_up' => 'تحريك لأعلى',
    'move_down' => 'تحريك لأسفل',
    'required' => 'إلزامي',
    'optional' => 'اختياري',
    'days' => ':count يوم',
    'all_countries' => 'كل الدول',
    'all_professions' => 'كل المهن',
    'field_in_language' => [
        'ar' => ':field بالعربي',
        'en' => ':field بالإنجليزي',
    ],

    'tabs' => [
        'stages' => 'مراحل السفر',
        'documents' => 'المستندات',
    ],
    'stages_hint' => 'المراحل تظهر بهذا الترتيب في متابعة الاستقدام لهذه الدولة.',
    'no_stages' => 'لا توجد مراحل سفر لهذه الدولة.',
    'max_days_hint' => 'المدة المتوقعة لإنهاء المرحلة؛ يظهر تنبيه عند تجاوزها.',
    'country_documents_hint' => 'مستندات خاصة بهذه الدولة، تُضاف إلى المستندات العامة:',
    'global_document_types' => 'المستندات العامة (لكل الدول)',
    'global_document_types_hint' => 'تُطلب من كل المرشحات مهما كانت الدولة، قبل المستندات الخاصة بكل دولة.',
    'global_document_types_hint_worker' => 'تُطلب من كل العمالة مهما كانت الدولة، قبل المستندات الخاصة بكل دولة.',
    'global_document_types_hint_client' => 'تُطلب من كل العملاء.',
    'subjects' => [
        'worker' => 'العمالة',
        'client' => 'العملاء',
    ],
    'no_document_types' => 'لا توجد مستندات معرّفة.',
    'document_types_hint' => 'الترتيب هنا هو ترتيب شاشة الرفع وترتيب ملف الجهة المطبوع. إيقاف مستند يخفيه عن المرشحات الجدد فقط، والملفات المرفوعة تبقى.',
    'authority_hint' => 'اختياري: اربط المستند بجهة معينة ليُطلب فقط عند طباعة ملفها.',

    'resources' => [
        'countries' => ['title' => 'الدول', 'singular' => 'دولة'],
        'stages' => ['title' => 'مراحل السفر', 'singular' => 'مرحلة'],
        'agencies' => ['title' => 'المكاتب الخارجية', 'singular' => 'مكتب خارجي'],
        'authorities' => ['title' => 'الجهات الخارجية', 'singular' => 'جهة'],
        'camps' => ['title' => 'السكن', 'singular' => 'سكن'],
        'professions' => ['title' => 'المهن', 'singular' => 'مهنة'],
        'shifts' => ['title' => 'الورديات', 'singular' => 'وردية'],
        'prices' => [
            'title' => 'الأسعار',
            'singular' => 'سعر',
            'duplicate_active' => 'يوجد سعر نشط لنفس الدولة ونوع التأجير. أوقف السعر الحالي أولاً.',
        ],
        'lookups' => ['title' => 'القوائم', 'singular' => 'عنصر'],
        'completed_visa_lists' => ['title' => 'قوائم التأشيرات المنجزة', 'singular' => 'إعداد قائمة التأشيرات'],
        'document_types' => ['title' => 'أنواع المستندات', 'singular' => 'مستند'],
    ],

    'fields' => [
        'name' => 'الاسم',
        'nationality' => 'الجنسية',
        'code' => 'رمز الدولة (ISO)',
        'country_id' => 'الدولة',
        'contact_person' => 'الشخص المسؤول',
        'phone' => 'الهاتف',
        'email' => 'البريد الإلكتروني',
        'city' => 'المدينة',
        'address' => 'العنوان',
        'district' => 'الحي',
        'branch_id' => 'الفرع',
        'type' => 'النوع',
        'capacity' => 'السعة',
        'cv_template' => 'نموذج السيرة الذاتية',
        'start_time' => 'وقت البداية',
        'move_time' => 'وقت تحرك السائق',
        'hours' => 'عدد الساعات',
        'rental_type' => 'نوع التأجير',
        'price' => 'السعر قبل الضريبة',
        'vat_percent' => 'نسبة الضريبة %',
        'authority_id' => 'الجهة',
        'profession_id' => 'المهنة',
        'visas_count' => 'عدد التأشيرات',
        'max_days' => 'أقصى مدة (أيام)',
        'is_required' => 'إلزامي',
        'include_in_authority_file' => 'ضمن ملف الجهة',
        'is_active' => 'الحالة',
        'applies_to' => 'يُطلب من',
    ],

    'camps' => [
        'types' => [
            'rental' => 'تأجير',
            'individual' => 'أفراد',
            'mixed' => 'مختلط',
        ],
    ],

    'prices' => [
        'rental_types' => [
            'monthly' => 'شهري',
            'hourly' => 'بالساعة',
        ],
    ],

    'lookups' => [
        'types' => [
            'return_reason' => 'أسباب الإرجاع',
            'cancel_reason' => 'أسباب الإلغاء',
            'payment_method' => 'طرق الدفع',
            'language' => 'اللغات',
            'skill' => 'المهارات',
        ],
    ],

    'flash' => [
        'created' => 'تمت إضافة :item.',
        'updated' => 'تم تحديث :item.',
        'activated' => 'تم تفعيل :item.',
        'deactivated' => 'تم إيقاف :item.',
        'deleted' => 'تم حذف :item.',
        'in_use' => 'لا يمكن حذف :item لأنه مستخدم في سجلات أخرى. أوقفه بدلاً من الحذف.',
    ],
];
