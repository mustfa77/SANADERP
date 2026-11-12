<?php

/*
|--------------------------------------------------------------------------
| Profile tabs
|--------------------------------------------------------------------------
|
| Tabs of the worker and client profile pages. Later modules (recruitment,
| contracts, camps, cases...) add their tab here only.
|
| key: tab id in the URL (?tab=key)    label: translation key
| view: Blade partial (receives the owner as $worker / $client)
| permission: required to see the tab  sort: display order
|
*/

return [
    'worker' => [
        ['key' => 'overview', 'label' => 'workers.tabs.overview', 'view' => 'admin.workers.tabs.overview', 'permission' => 'workers.view', 'icon' => 'ri-user-line', 'sort' => 10],
        ['key' => 'documents', 'label' => 'workers.tabs.documents', 'view' => 'admin.workers.tabs.documents', 'permission' => 'documents.view', 'icon' => 'ri-file-list-3-line', 'sort' => 20],
        ['key' => 'status_history', 'label' => 'workers.tabs.status_history', 'view' => 'admin.workers.tabs.status-history', 'permission' => 'workers.view', 'icon' => 'ri-history-line', 'sort' => 30],
    ],

    'client' => [
        ['key' => 'overview', 'label' => 'clients.tabs.overview', 'view' => 'admin.clients.tabs.overview', 'permission' => 'clients.view', 'icon' => 'ri-user-line', 'sort' => 10],
        ['key' => 'documents', 'label' => 'clients.tabs.documents', 'view' => 'admin.clients.tabs.documents', 'permission' => 'documents.view', 'icon' => 'ri-file-list-3-line', 'sort' => 20],
    ],
];
