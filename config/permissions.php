<?php

/*
|--------------------------------------------------------------------------
| Permissions catalogue
|--------------------------------------------------------------------------
|
| Single source of truth for permissions. The PermissionSeeder, the role
| permission matrix and the sidebar all read from this file.
|
| Permission names follow "module.action". Labels are NOT stored here: they
| come from lang/{ar,en}/permissions.php (sections, modules, actions and
| special permissions) and are copied to label_ar / label_en when seeding.
|
*/

return [

    /*
    | Standard actions given to every module unless the module overrides them.
    */
    'standard_actions' => ['view', 'create', 'edit', 'delete', 'export', 'print'],

    /*
    | Sections (matrix row groups) in display order, each listing its modules.
    | A module may override its actions with an explicit list.
    */
    'sections' => [
        'recruitment' => [
            'recruitment' => null,
            'candidates' => null,
            'documents' => ['view', 'upload', 'replace', 'delete_version', 'print_authority_file'],
            'document_types' => ['manage'],
            'agencies' => null,
            'authorities' => null,
            'website_requests' => null,
        ],
        'rental' => [
            'clients' => ['view', 'create', 'edit', 'export', 'print'],
            'rental_contracts' => null,
            'hourly_bookings' => null,
        ],
        'operations' => [
            'workers' => ['view', 'create', 'edit', 'export', 'print'],
            'camps' => null,
            'camp_movements' => null,
            'drivers' => null,
            'trips' => null,
            'cases' => null,
            'alerts' => null,
        ],
        'finance' => [
            'payments' => null,
            'invoices' => null,
            'refunds' => null,
            'reports' => ['view', 'export', 'print'],
        ],
        'administration' => [
            'dashboard' => ['view'],
            'branches' => null,
            'users' => null,
            'roles' => null,
            'audit_log' => ['view', 'export', 'print'],
            'settings' => ['view', 'edit', 'manage'],
        ],
    ],

    /*
    | Extra permissions beyond the standard actions. Each is "module.action"
    | and is shown in the matrix row of its module.
    */
    'special' => [
        'candidates.approve',
        'documents.authenticate',
        'payments.verify',
        'invoices.issue',
        'invoices.retry',
        'refunds.approve',
        'cases.decide',
        'workers.change_status',
        'workers.view_sensitive',
        'clients.view_sensitive',
        'reports.view_financial',
        'branches.all',
    ],

    /*
    | Default roles. Names and descriptions come from lang/{ar,en}/roles.php.
    | "permissions" accepts wildcards: "*" (everything), "candidates.*",
    | "*.view". "except" removes matches from the result.
    */
    'roles' => [
        'super_admin' => [
            'is_system' => true,
            'permissions' => ['*'],
        ],
        'general_manager' => [
            'is_system' => false,
            'permissions' => [
                '*.view', '*.export', '*.print',
                'branches.all',
                'candidates.approve', 'refunds.approve', 'cases.decide', 'payments.verify',
                'workers.view_sensitive', 'clients.view_sensitive', 'reports.view_financial',
            ],
            'except' => ['settings.*'],
        ],
        'branch_manager' => [
            'is_system' => false,
            'permissions' => [
                'dashboard.view', 'reports.view', 'reports.export', 'reports.print', 'alerts.*',
                'workers.*', 'clients.*', 'camps.*', 'camp_movements.*', 'trips.*', 'drivers.view',
                'rental_contracts.*', 'hourly_bookings.*', 'cases.*',
                'payments.view', 'invoices.view', 'refunds.view',
                'candidates.view', 'documents.view',
            ],
            'except' => ['*.delete', 'cases.decide'],
        ],
        'recruitment_team' => [
            'is_system' => false,
            'permissions' => [
                'dashboard.view', 'recruitment.*', 'candidates.*', 'documents.*',
                'agencies.view', 'authorities.view', 'workers.view',
            ],
            'except' => ['candidates.approve', '*.delete'],
        ],
        'marketing_team' => [
            'is_system' => false,
            'permissions' => ['dashboard.view', 'candidates.view', 'website_requests.*'],
        ],
        'rental_team' => [
            'is_system' => false,
            'permissions' => [
                'dashboard.view', 'rental_contracts.*', 'hourly_bookings.*',
                'clients.view', 'clients.create', 'clients.edit', 'workers.view',
            ],
            'except' => ['*.delete'],
        ],
        'accounts' => [
            'is_system' => false,
            'permissions' => [
                'dashboard.view', 'payments.*', 'invoices.*', 'refunds.view', 'refunds.create', 'clients.view',
            ],
            'except' => ['*.delete'],
        ],
        'finance_manager' => [
            'is_system' => false,
            'permissions' => [
                'dashboard.view', 'payments.*', 'invoices.*', 'refunds.*', 'reports.*', 'clients.view',
            ],
        ],
        'camp_team' => [
            'is_system' => false,
            'permissions' => ['dashboard.view', 'camps.*', 'camp_movements.*', 'cases.view', 'workers.view'],
            'except' => ['*.delete'],
        ],
        'transport_coordinator' => [
            'is_system' => false,
            'permissions' => ['dashboard.view', 'trips.*', 'drivers.*'],
            'except' => ['*.delete'],
        ],
        'driver' => [
            'is_system' => true,
            'permissions' => [],
        ],
        'external_agency' => [
            'is_system' => true,
            'permissions' => [],
        ],
    ],

    /*
    | Role that bypasses every check (Gate::before) and whose permissions
    | cannot be reduced.
    */
    'super_admin_role' => 'super_admin',

    /*
    | First Super Admin account, seeded from the environment.
    */
    'super_admin' => [
        'name_ar' => env('SUPER_ADMIN_NAME_AR', 'مدير النظام'),
        'name_en' => env('SUPER_ADMIN_NAME_EN', 'System Admin'),
        'email' => env('SUPER_ADMIN_EMAIL'),
        'phone' => env('SUPER_ADMIN_PHONE'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],

];
