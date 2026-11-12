<?php

return [
    'title' => 'Clients',
    'create' => 'Add client',
    'edit' => 'Edit client',
    'show' => 'Client file',
    'search_placeholder' => 'Search by name or mobile...',
    'search_national_id' => 'National ID (exact)',

    'tabs' => [
        'overview' => 'Overview',
        'documents' => 'Documents',
    ],

    'fields' => [
        'name' => 'Client',
        'name_ar' => 'Arabic name',
        'name_en' => 'English name',
        'national_id' => 'National ID / Iqama',
        'phone' => 'Mobile',
        'phone2' => 'Other mobile',
        'email' => 'Email',
        'city' => 'City',
        'district' => 'District',
        'national_address' => 'National address',
        'branch_id' => 'Branch',
        'visa_balance' => 'Visa balance',
        'notes' => 'Notes',
        'is_active' => 'Client is active',
        'created_by' => 'Added by',
    ],

    'validation' => [
        'duplicate_national_id' => 'This national ID is already registered for another client.',
        'open_existing' => 'Open client file',
        'national_id_format' => 'The national ID must be 10 digits starting with 1 (citizen) or 2 (resident).',
    ],

    'flash' => [
        'created' => 'Client added.',
        'updated' => 'Client updated.',
    ],
];
