<?php

return [
    'title' => 'Audit Log',
    'show' => 'Log Entry',
    'system' => 'System',

    'fields' => [
        'date' => 'Date',
        'user' => 'User',
        'action' => 'Action',
        'description' => 'Description',
        'target' => 'Affected record',
        'ip' => 'IP address',
        'user_agent' => 'Browser / device',
        'old_values' => 'Before',
        'new_values' => 'After',
        'field' => 'Field',
    ],

    'filters' => [
        'from' => 'From date',
        'to' => 'To date',
    ],

    'targets' => [
        'User' => 'User',
        'Role' => 'Role',
        'Branch' => 'Branch',
    ],

    /*
    | Short name of each action (filters and tables).
    */
    'action_names' => [
        'auth' => [
            'login' => 'Login',
            'logout' => 'Logout',
            'failed' => 'Failed login',
            'lockout' => 'Login lockout',
            'timeout' => 'Session timeout',
            'password_reset' => 'Password reset',
            'password_changed' => 'Password changed',
        ],
        'user' => [
            'created' => 'User created',
            'updated' => 'User updated',
            'activated' => 'User activated',
            'deactivated' => 'User deactivated',
            'deleted' => 'User deleted',
            'reset_link_sent' => 'Reset link sent',
            'roles_assigned' => 'User roles changed',
            'branches_assigned' => 'User branches changed',
        ],
        'role' => [
            'created' => 'Role created',
            'cloned' => 'Role cloned',
            'updated' => 'Role updated',
            'deleted' => 'Role deleted',
            'permissions_changed' => 'Permissions changed',
        ],
        'branch' => [
            'created' => 'Branch created',
            'updated' => 'Branch updated',
            'activated' => 'Branch activated',
            'deactivated' => 'Branch deactivated',
            'deleted' => 'Branch deleted',
        ],
        'profile' => [
            'two_factor_enabled' => '2FA enabled',
            'two_factor_disabled' => '2FA disabled',
        ],
        'settings' => [
            'updated' => 'Settings changed',
        ],
        'maintenance' => [
            'enabled' => 'Maintenance on',
            'disabled' => 'Maintenance off',
        ],
        'backup' => [
            'created' => 'Backup created',
            'downloaded' => 'Backup downloaded',
            'deleted' => 'Backup deleted',
            'failed' => 'Backup failed',
        ],
    ],

    /*
    | Full description of each action.
    */
    'actions' => [
        'auth' => [
            'login' => ':name logged in',
            'logout' => ':name logged out',
            'failed' => 'Failed login attempt for :email',
            'lockout' => 'Login locked for :email after repeated failures',
            'timeout' => 'Session ended after inactivity',
            'password_reset' => ':name reset their password',
            'password_changed' => ':name changed their password',
        ],
        'user' => [
            'created' => 'User :name was created',
            'updated' => 'User :name was updated',
            'activated' => 'User :name was activated',
            'deactivated' => 'User :name was deactivated',
            'deleted' => 'User :name was deleted',
            'reset_link_sent' => 'Password reset link sent to :name',
            'roles_assigned' => 'Roles of :name were changed',
            'branches_assigned' => 'Branches of :name were changed',
        ],
        'role' => [
            'created' => 'Role :role was created',
            'cloned' => 'Role :role was created as a copy of :source',
            'updated' => 'Role :role was updated',
            'deleted' => 'Role :role was deleted',
            'permissions_changed' => 'Permissions of :role were changed (:added added, :removed removed)',
        ],
        'branch' => [
            'created' => 'Branch :branch was created',
            'updated' => 'Branch :branch was updated',
            'activated' => 'Branch :branch was activated',
            'deactivated' => 'Branch :branch was deactivated',
            'deleted' => 'Branch :branch was deleted',
        ],
        'profile' => [
            'two_factor_enabled' => ':name enabled two-factor verification',
            'two_factor_disabled' => ':name disabled two-factor verification',
        ],
        'settings' => [
            'updated' => 'Settings were changed (:section)',
        ],
        'maintenance' => [
            'enabled' => 'Maintenance mode was turned on',
            'disabled' => 'Maintenance mode was turned off',
        ],
        'backup' => [
            'created' => 'Backup :file was created',
            'downloaded' => 'Backup :file was downloaded',
            'deleted' => 'Backup :file was deleted',
            'failed' => 'Backup failed: :error',
        ],
    ],

    'added' => 'Added',
    'removed' => 'Removed',
];
