<?php

return [
    'title' => 'Branches',
    'create' => 'Add branch',
    'edit' => 'Edit branch',
    'show' => 'Branch details',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'search_placeholder' => 'Search by name, code or city...',
    'code_hint' => 'Short English code (capital letters, digits, - or _), e.g. RYD.',
    'confirm_deactivate' => 'The branch will be hidden from the branch switcher. Its data is kept.',
    'view_all_users' => 'View all users',
    'no_users' => 'No users are assigned to this branch.',

    'sections' => [
        'basic' => 'Basic information',
        'contact' => 'Address & contact',
        'details' => 'Branch information',
        'users' => 'Branch users',
    ],

    'fields' => [
        'code' => 'Branch code',
        'name' => 'Branch',
        'name_ar' => 'Arabic name',
        'name_en' => 'English name',
        'city' => 'City',
        'city_ar' => 'City (Arabic)',
        'city_en' => 'City (English)',
        'address' => 'Address',
        'address_ar' => 'Address (Arabic)',
        'address_en' => 'Address (English)',
        'phone' => 'Phone',
        'email' => 'Email',
        'manager' => 'Branch manager',
        'is_active' => 'Branch is active',
        'status' => 'Status',
        'users_count' => 'Users',
        'workers_count' => 'Workers',
        'trips_count' => 'Trips',
    ],

    'actions' => [
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
    ],

    'flash' => [
        'created' => 'Branch added.',
        'updated' => 'Branch updated.',
        'activated' => 'Branch activated.',
        'deactivated' => 'Branch deactivated.',
        'deleted' => 'Branch deleted.',
        'in_use' => 'This branch cannot be deleted: it has :users users, :workers workers and :trips trips. Move them or deactivate the branch instead.',
        'last_active' => 'The last active branch cannot be deactivated or deleted.',
    ],
];
