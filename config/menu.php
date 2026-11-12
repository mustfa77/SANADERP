<?php

/*
|--------------------------------------------------------------------------
| Sidebar menu
|--------------------------------------------------------------------------
|
| Each section lists its items. An item is shown only when the user has
| its "permission". Labels come from lang/{ar,en}/permissions.php
| (modules.{key}) and section titles from permissions.sections or app.nav.
| "route" is null for modules that are not built yet (shown as "soon").
|
*/

return [
    'main' => [
        ['key' => 'dashboard', 'icon' => 'mdi mdi-home-variant-outline', 'route' => 'admin.dashboard', 'permission' => 'dashboard.view'],
    ],
    'recruitment' => [
        ['key' => 'recruitment', 'icon' => 'ri-flight-takeoff-line', 'route' => null, 'permission' => 'recruitment.view'],
        ['key' => 'candidates', 'icon' => 'ri-user-search-line', 'route' => null, 'permission' => 'candidates.view'],
        ['key' => 'documents', 'icon' => 'ri-file-list-3-line', 'route' => null, 'permission' => 'documents.view'],
        ['key' => 'website_requests', 'icon' => 'ri-global-line', 'route' => null, 'permission' => 'website_requests.view'],
    ],
    'rental' => [
        ['key' => 'clients', 'icon' => 'ri-contacts-book-line', 'route' => 'admin.clients.index', 'permission' => 'clients.view'],
        ['key' => 'rental_contracts', 'icon' => 'ri-file-paper-2-line', 'route' => null, 'permission' => 'rental_contracts.view'],
        ['key' => 'hourly_bookings', 'icon' => 'ri-time-line', 'route' => null, 'permission' => 'hourly_bookings.view'],
    ],
    'operations' => [
        ['key' => 'workers', 'icon' => 'ri-team-line', 'route' => 'admin.workers.index', 'permission' => 'workers.view'],
        ['key' => 'camp_movements', 'icon' => 'ri-arrow-left-right-line', 'route' => null, 'permission' => 'camp_movements.view'],
        ['key' => 'drivers', 'icon' => 'ri-steering-2-line', 'route' => null, 'permission' => 'drivers.view'],
        ['key' => 'trips', 'icon' => 'ri-bus-line', 'route' => null, 'permission' => 'trips.view'],
        ['key' => 'cases', 'icon' => 'ri-briefcase-4-line', 'route' => null, 'permission' => 'cases.view'],
        ['key' => 'alerts', 'icon' => 'ri-alarm-warning-line', 'route' => null, 'permission' => 'alerts.view'],
    ],
    'finance' => [
        ['key' => 'payments', 'icon' => 'ri-bank-card-line', 'route' => null, 'permission' => 'payments.view'],
        ['key' => 'invoices', 'icon' => 'ri-bill-line', 'route' => null, 'permission' => 'invoices.view'],
        ['key' => 'refunds', 'icon' => 'ri-refund-2-line', 'route' => null, 'permission' => 'refunds.view'],
        ['key' => 'reports', 'icon' => 'ri-bar-chart-box-line', 'route' => null, 'permission' => 'reports.view'],
    ],
    'administration' => [
        ['key' => 'branches', 'icon' => 'ri-git-branch-line', 'route' => 'admin.branches.index', 'permission' => 'branches.view'],
        ['key' => 'users', 'icon' => 'ri-user-settings-line', 'route' => 'admin.users.index', 'permission' => 'users.view'],
        ['key' => 'roles', 'icon' => 'ri-shield-keyhole-line', 'route' => 'admin.roles.index', 'permission' => 'roles.view'],
        ['key' => 'audit_log', 'icon' => 'ri-history-line', 'route' => 'admin.audit-logs.index', 'permission' => 'audit_log.view'],
        [
            'key' => 'settings', 'icon' => 'ri-settings-3-line', 'route' => null, 'permission' => null,
            'children' => [
                ['key' => 'system_settings', 'route' => 'admin.settings.edit', 'permission' => 'settings.view'],
                ['key' => 'alert_settings', 'route' => 'admin.settings.edit', 'params' => ['tab' => 'alerts'], 'permission' => 'settings.view'],
                ['key' => 'countries', 'route' => 'admin.reference.countries.index', 'permission' => 'settings.view', 'active' => 'admin.countries.*'],
                ['key' => 'document_types', 'route' => 'admin.document-types.index', 'permission' => 'settings.view'],
                ['key' => 'completed_visa_lists', 'route' => 'admin.reference.completed_visa_lists.index', 'permission' => 'settings.view'],
                ['key' => 'agencies', 'route' => 'admin.reference.agencies.index', 'permission' => 'agencies.view'],
                ['key' => 'authorities', 'route' => 'admin.reference.authorities.index', 'permission' => 'authorities.view'],
                ['key' => 'camps', 'route' => 'admin.reference.camps.index', 'permission' => 'camps.view'],
                ['key' => 'professions', 'route' => 'admin.reference.professions.index', 'permission' => 'settings.view'],
                ['key' => 'shifts', 'route' => 'admin.reference.shifts.index', 'permission' => 'settings.view'],
                ['key' => 'prices', 'route' => 'admin.reference.prices.index', 'permission' => 'settings.view'],
                ['key' => 'lookups', 'route' => 'admin.reference.lookups.index', 'permission' => 'settings.view'],
            ],
        ],
    ],
];
