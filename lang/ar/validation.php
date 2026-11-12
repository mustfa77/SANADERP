<?php

return [

    /*
    |--------------------------------------------------------------------------
    | رسائل التحقق من البيانات
    |--------------------------------------------------------------------------
    | القواعد غير المذكورة هنا تعود للرسالة الإنجليزية (fallback_locale).
    */

    'accepted' => 'يجب قبول :attribute.',
    'active_url' => 'حقل :attribute يجب أن يكون رابطاً صحيحاً.',
    'after' => 'حقل :attribute يجب أن يكون تاريخاً بعد :date.',
    'after_or_equal' => 'حقل :attribute يجب أن يكون تاريخاً بعد أو يساوي :date.',
    'alpha' => 'حقل :attribute يجب أن يحتوي على أحرف فقط.',
    'alpha_dash' => 'حقل :attribute يجب أن يحتوي على أحرف وأرقام وشرطات فقط.',
    'alpha_num' => 'حقل :attribute يجب أن يحتوي على أحرف وأرقام فقط.',
    'array' => 'حقل :attribute يجب أن يكون قائمة.',
    'before' => 'حقل :attribute يجب أن يكون تاريخاً قبل :date.',
    'before_or_equal' => 'حقل :attribute يجب أن يكون تاريخاً قبل أو يساوي :date.',
    'between' => [
        'array' => 'حقل :attribute يجب أن يحتوي على :min إلى :max عناصر.',
        'file' => 'حجم :attribute يجب أن يكون بين :min و :max كيلوبايت.',
        'numeric' => 'قيمة :attribute يجب أن تكون بين :min و :max.',
        'string' => 'طول :attribute يجب أن يكون بين :min و :max حرفاً.',
    ],
    'boolean' => 'حقل :attribute يجب أن يكون نعم أو لا.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة المرور الحالية غير صحيحة.',
    'date' => 'حقل :attribute يجب أن يكون تاريخاً صحيحاً.',
    'date_format' => 'حقل :attribute يجب أن يطابق الصيغة :format.',
    'different' => 'حقل :attribute يجب أن يختلف عن :other.',
    'digits' => 'حقل :attribute يجب أن يتكون من :digits أرقام.',
    'digits_between' => 'حقل :attribute يجب أن يكون بين :min و :max أرقام.',
    'email' => 'حقل :attribute يجب أن يكون بريداً إلكترونياً صحيحاً.',
    'enum' => 'قيمة :attribute المختارة غير صحيحة.',
    'exists' => 'قيمة :attribute المختارة غير موجودة.',
    'file' => 'حقل :attribute يجب أن يكون ملفاً.',
    'filled' => 'حقل :attribute مطلوب.',
    'image' => 'حقل :attribute يجب أن يكون صورة.',
    'in' => 'قيمة :attribute المختارة غير صحيحة.',
    'integer' => 'حقل :attribute يجب أن يكون عدداً صحيحاً.',
    'ip' => 'حقل :attribute يجب أن يكون عنوان IP صحيحاً.',
    'json' => 'حقل :attribute يجب أن يكون نص JSON صحيحاً.',
    'lowercase' => 'حقل :attribute يجب أن يكون بأحرف صغيرة.',
    'max' => [
        'array' => 'حقل :attribute يجب ألا يحتوي على أكثر من :max عناصر.',
        'file' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
        'numeric' => 'قيمة :attribute يجب ألا تتجاوز :max.',
        'string' => 'طول :attribute يجب ألا يتجاوز :max حرفاً.',
    ],
    'mimes' => 'حقل :attribute يجب أن يكون ملفاً من نوع: :values.',
    'min' => [
        'array' => 'حقل :attribute يجب أن يحتوي على :min عناصر على الأقل.',
        'file' => 'حجم :attribute يجب أن يكون :min كيلوبايت على الأقل.',
        'numeric' => 'قيمة :attribute يجب أن تكون :min على الأقل.',
        'string' => 'طول :attribute يجب أن يكون :min أحرف على الأقل.',
    ],
    'not_in' => 'قيمة :attribute المختارة غير صحيحة.',
    'numeric' => 'حقل :attribute يجب أن يكون رقماً.',
    'password' => [
        'letters' => 'حقل :attribute يجب أن يحتوي على حرف واحد على الأقل.',
        'mixed' => 'حقل :attribute يجب أن يحتوي على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'حقل :attribute يجب أن يحتوي على رقم واحد على الأقل.',
        'symbols' => 'حقل :attribute يجب أن يحتوي على رمز واحد على الأقل.',
        'uncompromised' => 'قيمة :attribute ظهرت في تسريب بيانات. يرجى اختيار قيمة أخرى.',
    ],
    'prohibited' => 'لا يمكنك تعديل :attribute.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'same' => 'حقل :attribute يجب أن يطابق :other.',
    'size' => [
        'array' => 'حقل :attribute يجب أن يحتوي على :size عناصر.',
        'file' => 'حجم :attribute يجب أن يكون :size كيلوبايت.',
        'numeric' => 'قيمة :attribute يجب أن تكون :size.',
        'string' => 'طول :attribute يجب أن يكون :size حرفاً.',
    ],
    'string' => 'حقل :attribute يجب أن يكون نصاً.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'uploaded' => 'فشل رفع :attribute.',
    'url' => 'حقل :attribute يجب أن يكون رابطاً صحيحاً.',

    /*
    |--------------------------------------------------------------------------
    | رسائل مخصصة
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'phone' => [
            'regex' => 'رقم الجوال يجب أن يكون بالصيغة 05XXXXXXXX.',
        ],
        'name' => [
            'regex' => 'المفتاح يجب أن يبدأ بحرف إنجليزي صغير ويحتوي على أحرف صغيرة وأرقام و _ فقط.',
        ],
    ],

    'values' => [
        'user_type' => [
            'internal' => 'موظف',
            'external_agency' => 'مكتب خارجي',
            'driver' => 'سائق',
        ],
        'mail_mailer' => [
            'smtp' => 'SMTP',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | أسماء الحقول
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'المفتاح',
        'name_ar' => 'الاسم بالعربي',
        'name_en' => 'الاسم بالإنجليزي',
        'description_ar' => 'الوصف بالعربي',
        'description_en' => 'الوصف بالإنجليزي',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الجوال',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'user_type' => 'نوع المستخدم',
        'agency_id' => 'المكتب الخارجي',
        'preferred_language' => 'اللغة المفضلة',
        'avatar' => 'الصورة',
        'must_change_password' => 'إجبار تغيير كلمة المرور',
        'roles' => 'الأدوار',
        'roles.*' => 'الدور',
        'branches' => 'الفروع',
        'branches.*' => 'الفرع',
        'branch_id' => 'الفرع',
        'all_branches' => 'كل الفروع',
        'requires_2fa' => 'التحقق بخطوتين',
        'two_factor_enabled' => 'التحقق بخطوتين',
        'permissions' => 'الصلاحيات',
        'permissions.*' => 'الصلاحية',
        'code' => 'رمز التحقق',
        'token' => 'الرمز',
        'session_timeout_minutes' => 'مهلة الخمول',
        'login_max_attempts' => 'عدد محاولات الدخول',
        'login_lockout_minutes' => 'مدة قفل الدخول',
        'two_factor_code_minutes' => 'صلاحية رمز التحقق',
        'site_name_ar' => 'اسم النظام بالعربي',
        'site_name_en' => 'اسم النظام بالإنجليزي',
        'logo_dark' => 'الشعار للخلفية الفاتحة',
        'logo_light' => 'الشعار للخلفية الداكنة',
        'logo_sm' => 'الشعار المصغر',
        'favicon' => 'أيقونة المتصفح',
        'city_ar' => 'المدينة بالعربي',
        'city_en' => 'المدينة بالإنجليزي',
        'address_ar' => 'العنوان بالعربي',
        'address_en' => 'العنوان بالإنجليزي',
        'manager_id' => 'مدير الفرع',
        'is_active' => 'الحالة',
        'maintenance_enabled' => 'وضع الصيانة',
        'maintenance_message_ar' => 'رسالة الصيانة بالعربي',
        'maintenance_message_en' => 'رسالة الصيانة بالإنجليزي',
        'backup_auto_enabled' => 'النسخ التلقائي',
        'backup_hour' => 'وقت النسخ اليومي',
        'backup_keep' => 'عدد النسخ المحفوظة',
        'audit_retention_days' => 'مدة الاحتفاظ بسجل التدقيق',
        'login_attempts_retention_days' => 'مدة الاحتفاظ بمحاولات الدخول',
        'primary_color' => 'اللون الرئيسي',
        'theme_mode' => 'الوضع الافتراضي',
        'sidebar_color' => 'لون القائمة الجانبية',
        'topbar_color' => 'لون الشريط العلوي',
        'sidebar_size' => 'حجم القائمة الجانبية',
        'layout_width' => 'عرض الصفحة',
        'button_shape' => 'شكل الأزرار',
        'mail_mailer' => 'طريقة الإرسال',
        'mail_host' => 'خادم SMTP',
        'mail_port' => 'المنفذ',
        'mail_encryption' => 'التشفير',
        'mail_username' => 'اسم مستخدم البريد',
        'mail_password' => 'كلمة مرور البريد',
        'mail_from_address' => 'بريد المرسل',
        'mail_from_name' => 'اسم المرسل',
    ],

];
