<?php

/*
|--------------------------------------------------------------------------
| Worker CV templates
|--------------------------------------------------------------------------
|
| Each profession uses one template (Settings > Professions > CV template),
| built from the agencies' CV samples.
|
| skills:      yes/no checklist printed on the CV (empty = no checklist)
| fields:      extra fields of that worker type (text | number | date | url | select)
| experience:  "short"    = place, position, duration
|              "detailed" = position, from / to dates, employer, location, job description
| place:       label of the short experience place column: country | city
|
| Labels: lang/{ar,en}/cv.php -> skills.{key}, details.{key}.label, templates.{key}.
| Documents of a worker type (e.g. the driving licence image) are document
| types linked to the profession (Settings > Countries / Document types).
|
*/

return [
    // MR-048
    'housemaid' => [
        'skills' => [
            'special_needs', 'elder_care', 'help_kids', 'infants_care', 'pets_care',
            'home_cleaning', 'washing_ironing', 'cooking', 'help_cooking',
        ],
        'fields' => [],
        'experience' => 'short',
        'place' => 'country',
    ],

    // SJJ-001, SJP-003
    'driver' => [
        'skills' => [
            'sedan', 'pickup', 'minivan', 'suv',
            'saudi_roads', 'saudi_traffic_laws', 'road_navigation', 'saudi_license',
        ],
        'fields' => [
            'license_number' => ['type' => 'text'],
            'license_expiry' => ['type' => 'date'],
            'test_video_url' => ['type' => 'url'],
        ],
        'experience' => 'short',
        'place' => 'country',
    ],

    // AZ-11
    'house_boy' => [
        'skills' => [
            'perform_cleaning', 'dish_washing', 'car_washing', 'gardening', 'pets_care',
            'assigned_tasks', 'washing_ironing', 'cooking', 'purchase_necessary',
        ],
        'fields' => [],
        'experience' => 'short',
        'place' => 'city',
    ],

    // NOR-012: tasks are written in each job description.
    'care_giver' => [
        'skills' => [],
        'fields' => [],
        'experience' => 'detailed',
        'place' => 'country',
    ],
];
