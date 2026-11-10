<?php

return [
    'title' => 'Users',
    'create' => 'Add User',
    'edit' => 'Edit User',
    'show' => 'User Details',

    'fields' => [
        'name' => 'Name',
        'name_ar' => 'Arabic name',
        'name_en' => 'English name',
        'email' => 'Email',
        'phone' => 'Mobile',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'status' => 'Status',
        'user_type' => 'User type',
        'agency' => 'External agency',
        'preferred_language' => 'Preferred language',
        'avatar' => 'Photo',
        'last_login_at' => 'Last login',
        'must_change_password' => 'Force password change on next login',
        'roles' => 'Roles',
        'branches' => 'Branches',
        'all_branches' => 'All branches',
        'created_by' => 'Created by',
        'two_factor' => 'Two-factor verification',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'locked' => 'Locked',
    ],

    'types' => [
        'internal' => 'Staff',
        'external_agency' => 'External agency',
        'driver' => 'Driver',
    ],

    'sections' => [
        'basic' => 'Basic information',
        'account' => 'Account',
        'access' => 'Roles & branches',
    ],

    'search_placeholder' => 'Search by name, email or mobile...',
    'password_hint_edit' => 'Leave empty to keep the current password.',
    'all_branches_hint' => 'Gives the user access to the data of every branch.',
    'own_access_locked' => 'You cannot change your own roles or branches.',
    'no_roles' => 'No roles',

    'tabs' => [
        'info' => 'Information',
        'roles' => 'Roles',
        'branches' => 'Branches',
        'activity' => 'Activity',
    ],

    'actions' => [
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'reset_password' => 'Send password reset link',
        'force_change' => 'Force password change on next login',
        'send' => 'Send',
    ],

    'activity' => [
        'logins' => 'Login attempts',
        'recent_actions' => 'Recent actions',
        'changes' => 'Changes to this account',
        'date' => 'Date',
        'result' => 'Result',
        'success' => 'Success',
        'failed' => 'Failed',
        'ip' => 'IP address',
        'device' => 'Device',
        'by' => 'By',
        'reasons' => [
            'invalid_credentials' => 'Invalid credentials',
            'inactive' => 'Inactive account',
            'locked_out' => 'Temporarily locked',
        ],
    ],

    'confirm_deactivate' => 'The user will be signed out immediately and cannot log in.',

    'flash' => [
        'created' => 'User created.',
        'updated' => 'User updated.',
        'deleted' => 'User deleted.',
        'activated' => 'User activated.',
        'deactivated' => 'User deactivated and signed out.',
        'reset_link_sent' => 'Password reset link sent.',
        'cannot_remove_last_admin_role' => 'The Super Admin role cannot be removed from the last active Super Admin.',
    ],
];
