<?php

return [
    'title' => 'Roles',
    'create' => 'Add Role',
    'edit' => 'Edit Role',
    'clone' => 'Clone',
    'permissions' => 'Permissions',
    'permissions_of' => 'Permissions: :role',
    'copy_of' => ':name (copy)',
    'badge_2fa' => '2FA',

    'fields' => [
        'name' => 'Key',
        'display_name' => 'Role name',
        'name_ar' => 'Arabic name',
        'name_en' => 'English name',
        'description_ar' => 'Arabic description',
        'description_en' => 'English description',
        'description' => 'Description',
        'requires_2fa' => 'Requires two-factor verification (email code at login)',
        'users_count' => 'Users',
        'permissions_count' => 'Permissions',
    ],

    'key_hint' => 'Unique English identifier, e.g. rental_team. Cannot be changed for system roles.',

    'matrix' => [
        'module' => 'Module',
        'other' => 'Special permissions',
        'select_all' => 'Select all',
        'select_row' => 'Select row',
        'super_admin_locked' => 'Super Admin has every permission; it cannot be reduced.',
        'read_only' => 'Read only — you are not allowed to change permissions.',
    ],

    'flash' => [
        'created' => 'Role created. Choose its permissions now.',
        'updated' => 'Role updated.',
        'deleted' => 'Role deleted.',
        'cloned' => 'Role cloned with its permissions.',
        'permissions_updated' => 'Permissions saved.',
        'in_use' => 'The role cannot be deleted because it is assigned to: :users',
    ],

    /*
    | Default roles: copied to roles.name_en / description_en by the RoleSeeder.
    */
    'defaults' => [
        'super_admin' => ['name' => 'Super Admin', 'description' => 'Full access to the system'],
        'general_manager' => ['name' => 'General Manager', 'description' => 'All branches, view everything, approvals'],
        'branch_manager' => ['name' => 'Branch Manager', 'description' => 'Manages operations of the assigned branch'],
        'recruitment_team' => ['name' => 'Recruitment Team', 'description' => 'Recruitment, candidates and documents'],
        'marketing_team' => ['name' => 'Marketing Team', 'description' => 'View candidates and website requests'],
        'rental_team' => ['name' => 'Rental Team', 'description' => 'Monthly rental contracts and hourly bookings'],
        'accounts' => ['name' => 'Accounts', 'description' => 'Payment verification, invoices and refunds'],
        'finance_manager' => ['name' => 'Finance Manager', 'description' => 'Finance, refund approval and financial reports'],
        'camp_team' => ['name' => 'Camp Team', 'description' => 'Camps, camp movements and viewing cases'],
        'transport_coordinator' => ['name' => 'Transport Coordinator', 'description' => 'Trips and drivers'],
        'driver' => ['name' => 'Driver', 'description' => 'Driver routes only'],
        'external_agency' => ['name' => 'External Agency', 'description' => 'Agency portal only'],
    ],
];
