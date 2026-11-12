<?php

return [
    'title' => 'الرحلات',
    'drivers_title' => 'السائقون',
    'create' => 'رحلة جديدة',
    'save' => 'حفظ الرحلة',
    'add_driver' => 'إضافة سائق',
    'save_driver' => 'حفظ السائق',
    'none' => 'لا توجد رحلات في هذا اليوم.',
    'no_drivers' => 'لا يوجد سائقون بعد.',
    'unassigned' => 'بدون سائق',
    'trips_today' => 'رحلات اليوم',
    'seats' => ':count مقاعد',
    'driver_no_trips' => 'لا توجد رحلات لك الآن.',
    'done_today' => 'المنتهية اليوم',

    'hints' => [
        'unassigned' => ':count رحلة بدون سائق.',
        'driver' => 'يدخل السائق إلى بوابة السائق بهذا البريد وكلمة المرور (ويُطلب منه تغييرها عند أول دخول).',
    ],

    'fields' => [
        'date' => 'اليوم',
        'scheduled_at' => 'الوقت',
        'type' => 'الرحلة',
        'route' => 'من ← إلى',
        'from_place' => 'من',
        'to_place' => 'إلى',
        'worker_id' => 'العامل',
        'client_id' => 'العميل',
        'driver_id' => 'السائق',
        'branch_id' => 'الفرع',
        'notes' => 'ملاحظات',
        'status' => 'الحالة',
    ],

    'driver_fields' => [
        'name_ar' => 'الاسم (عربي)',
        'name_en' => 'الاسم (إنجليزي)',
        'email' => 'البريد (للدخول)',
        'phone' => 'الجوال',
        'password' => 'كلمة المرور',
        'branch_id' => 'الفرع',
        'license_number' => 'رقم الرخصة',
        'license_expiry' => 'انتهاء الرخصة',
        'vehicle_model' => 'السيارة',
        'vehicle_plate' => 'رقم اللوحة',
        'seats' => 'المقاعد',
    ],

    'types' => [
        'hourly_dropoff' => 'ساعة — توصيل للعميل',
        'hourly_pickup' => 'ساعة — إرجاع',
        'delivery' => 'توصيل للمنزل',
        'manual' => 'رحلة أخرى',
    ],

    'statuses' => [
        'scheduled' => 'مجدولة',
        'started' => 'في الطريق',
        'completed' => 'تمت',
        'cancelled' => 'ملغاة',
    ],

    'actions' => [
        'start' => 'انطلقت',
        'complete' => 'تمت',
        'cancel' => 'إلغاء الرحلة',
        'activate' => 'تفعيل',
        'deactivate' => 'إيقاف',
    ],

    'errors' => [
        'closed' => 'هذه الرحلة منتهية أو ملغاة.',
        'not_scheduled' => 'الرحلة بدأت بالفعل.',
        'no_driver' => 'حدّد السائق أولاً.',
        'driver_not_allowed' => 'يجب أن يكون السائق نشطاً ومن سائقي فرع الرحلة.',
    ],

    'flash' => [
        'created' => 'تم إنشاء الرحلة.',
        'assigned' => 'تم حفظ السائق.',
        'started' => 'بدأت الرحلة.',
        'completed' => 'تمت الرحلة.',
        'cancelled' => 'تم إلغاء الرحلة.',
        'driver_created' => 'تمت إضافة السائق.',
        'driver_updated' => 'تم تحديث بيانات السائق.',
    ],
];
