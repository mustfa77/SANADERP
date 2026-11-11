<?php

return [
    /*
    | Label of a standard permission, e.g. "View Workers".
    */
    'label' => ':action :module',

    'sections' => [
        'recruitment' => 'Recruitment',
        'rental' => 'Rental',
        'operations' => 'Operations',
        'finance' => 'Finance',
        'administration' => 'Administration',
    ],

    'modules' => [
        'dashboard' => 'Dashboard',
        'branches' => 'Branches',
        'camps' => 'Camps',
        'workers' => 'Workers',
        'clients' => 'Clients',
        'agencies' => 'External Agencies',
        'authorities' => 'Authorities',
        'drivers' => 'Drivers',
        'recruitment' => 'Recruitment Requests',
        'candidates' => 'Candidates',
        'documents' => 'Documents',
        'document_types' => 'Document types',
        'system_settings' => 'System settings',
        'countries' => 'Countries',
        'professions' => 'Professions',
        'shifts' => 'Shifts',
        'prices' => 'Prices',
        'lookups' => 'Lookup lists',
        'completed_visa_lists' => 'Completed visa lists',
        'alert_settings' => 'Alert settings',
        'rental_contracts' => 'Monthly Rental Contracts',
        'hourly_bookings' => 'Hourly Bookings',
        'camp_movements' => 'Camp Movements',
        'trips' => 'Trips',
        'cases' => 'Cases',
        'payments' => 'Payments',
        'invoices' => 'Invoices',
        'refunds' => 'Refunds',
        'reports' => 'Reports',
        'alerts' => 'Alerts',
        'settings' => 'Settings',
        'users' => 'Users',
        'roles' => 'Roles',
        'audit_log' => 'Audit Log',
        'website_requests' => 'Website Requests',
    ],

    'actions' => [
        'view' => 'View',
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'export' => 'Export',
        'print' => 'Print',
        'manage' => 'Manage',
        'upload' => 'Upload',
        'replace' => 'Replace',
        'delete_version' => 'Delete version of',
        'print_authority_file' => 'Print authority file of',
    ],

    /*
    | Special permissions, keyed by module then action ("invoices.issue").
    */
    'special' => [
        'candidates' => ['approve' => 'Approve candidate'],
        'documents' => ['authenticate' => 'Record document authentication'],
        'payments' => ['verify' => 'Verify payment'],
        'invoices' => [
            'issue' => 'Issue invoice in Wafeq',
            'retry' => 'Resend failed invoice',
        ],
        'refunds' => ['approve' => 'Approve refund'],
        'cases' => ['decide' => 'Decide deportation or sponsorship transfer'],
        'workers' => ['view_sensitive' => 'View passport and iqama numbers'],
        'clients' => ['view_sensitive' => 'View national ID and national address'],
        'reports' => ['view_financial' => 'View financial reports'],
        'branches' => ['all' => 'Access all branches'],
    ],
];
