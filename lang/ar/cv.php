<?php

return [
    'tab' => 'السيرة الذاتية',
    'edit' => 'تعبئة السيرة الذاتية',
    'print' => 'عرض السيرة الذاتية',
    'preview_title' => 'السيرة الذاتية — :name',
    'actions' => ['print' => 'طباعة', 'download' => 'تحميل PDF'],
    'no_photos' => 'لا توجد صور',
    'years_old' => ':count سنة',
    'updated_at' => 'آخر تحديث للسيرة: :date',

    'templates' => [
        'housemaid' => 'عاملة منزلية',
        'driver' => 'سائق خاص / عائلي',
        'house_boy' => 'عامل منزلي',
        'care_giver' => 'مقدم رعاية',
    ],

    'categories' => [
        'first_timer' => 'أول مرة',
        'experienced' => 'سبق له العمل',
    ],

    'language_levels' => [
        'none' => 'لا يتحدث',
        'weak' => 'ضعيف',
        'good' => 'جيد',
        'fluent' => 'ممتاز',
    ],

    'sections' => [
        'photos' => 'الصور',
        'personal' => 'المعلومات الشخصية',
        'experience' => 'خلفية العمل',
        'skills' => 'المهارات',
        'details_housemaid' => 'تفاصيل إضافية',
        'details_driver' => 'رخصة القيادة والاختبار المهني',
        'details_house_boy' => 'تفاصيل إضافية',
        'details_care_giver' => 'تفاصيل إضافية',
    ],

    'documents_hint' => [
        'driver' => 'صورة رخصة القيادة وشهادة الاختبار المهني تُرفع في تبويب "المستندات".',
    ],

    'fields' => [
        'photo' => 'الصورة الشخصية',
        'full_photo' => 'الصورة كاملة',
        'cv_reference_no' => 'الرقم المرجعي',
        'cv_category' => 'الفئة',
        'position' => 'الوظيفة',
        'salary' => 'الراتب',
        'name' => 'الاسم',
        'home_city' => 'المدينة',
        'nationality' => 'الجنسية',
        'gender' => 'الجنس',
        'age' => 'العمر',
        'birth_date' => 'تاريخ الميلاد',
        'education' => 'الحالة التعليمية',
        'religion' => 'الديانة',
        'marital_status' => 'الحالة الاجتماعية',
        'children_count' => 'عدد الأطفال',
        'height_cm' => 'الطول (سم)',
        'weight_kg' => 'الوزن (كيلو)',
        'arabic_level' => 'اللغة العربية',
        'english_level' => 'اللغة الإنجليزية',
    ],

    'experience' => [
        'country' => 'الدولة',
        'city' => 'المدينة',
        'position' => 'الوظيفة',
        'duration' => 'مدة العمل',
        'company' => 'جهة العمل',
        'location' => 'الموقع',
        'started_at' => 'المدة (من)',
        'ended_at' => 'إلى',
        'description' => 'الوصف الوظيفي',
        'description_hint' => 'مثل: رعاية مريض، تحضير الوجبات حسب الحمية، إعطاء الأدوية في مواعيدها...',
        'present' => 'حتى الآن',
        'hint' => 'اترك الصفوف فارغة إذا كانت أول مرة (تظهر N/A في السيرة).',
        'hint_detailed' => 'خبرة لكل خانة. اترك "إلى" فارغاً إذا كان ما زال على رأس العمل.',
        'none' => 'لا توجد خبرة سابقة.',
    ],

    'skills' => [
        // Housemaid
        'special_needs' => 'العناية بذوي الاحتياجات الخاصة',
        'elder_care' => 'العناية بكبار السن',
        'help_kids' => 'المساعدة بالأطفال',
        'infants_care' => 'الاهتمام بالرضع وحديثي الولادة',
        'pets_care' => 'المساعدة بالعناية بالحيوانات الأليفة',
        'home_cleaning' => 'تنظيف المنزل',
        'washing_ironing' => 'غسيل وكي الملابس',
        'cooking' => 'الطبخ العادي',
        'help_cooking' => 'المساعدة بالطبخ',
        // Driver
        'sedan' => 'سيدان',
        'pickup' => 'سيارات النقل الصغيرة',
        'minivan' => 'الباصات الصغيرة',
        'suv' => 'سيارات الجيب الصغيرة والدفع الرباعي',
        'saudi_roads' => 'المعرفة بالطرق السعودية',
        'saudi_traffic_laws' => 'المعرفة بقوانين المرور السعودية',
        'road_navigation' => 'مهارات الخرائط',
        'saudi_license' => 'رخصة القيادة السعودية',
        // House boy
        'perform_cleaning' => 'إجراء التنظيف',
        'dish_washing' => 'غسل الأطباق',
        'car_washing' => 'غسيل السيارات',
        'gardening' => 'الحدائق',
        'assigned_tasks' => 'القيام بالمهام المعينة',
        'purchase_necessary' => 'الشراء الضروري',
    ],

    'details' => [
        'license_number' => ['label' => 'رقم رخصة القيادة'],
        'license_expiry' => ['label' => 'انتهاء رخصة القيادة'],
        'test_video_url' => ['label' => 'رابط فيديو الاختبار المهني'],
    ],

    'pdf' => [
        'title' => 'سيرة ذاتية',
        'date' => 'التاريخ',
    ],

    'portal' => [
        'title' => 'عمالتنا',
        'hint' => 'العمالة المرتبطة بمكتبكم. عبّئوا السيرة الذاتية وارفعوا الصور لكل عامل.',
        'search' => 'الاسم أو الرقم المرجعي...',
        'cv_status' => 'السيرة الذاتية',
        'filled' => 'معبّأة :date',
        'missing' => 'لم تُعبّأ',
    ],

    'flash' => [
        'updated' => 'تم حفظ السيرة الذاتية.',
    ],
];
