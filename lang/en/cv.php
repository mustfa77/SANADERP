<?php

return [
    'tab' => 'CV',
    'edit' => 'Fill in CV',
    'print' => 'View CV',
    'preview_title' => 'CV — :name',
    'actions' => ['print' => 'Print', 'download' => 'Download PDF'],
    'no_photos' => 'No photos',
    'years_old' => ':count years old',
    'updated_at' => 'CV last updated: :date',

    'templates' => [
        'housemaid' => 'Housemaid',
        'driver' => 'Private / family driver',
        'house_boy' => 'House boy',
        'care_giver' => 'Care giver',
    ],

    'categories' => [
        'first_timer' => 'First-Timer',
        'experienced' => 'Ex-abroad',
    ],

    'language_levels' => [
        'none' => 'N/A',
        'weak' => 'Weak',
        'good' => 'Good',
        'fluent' => 'Fluent',
    ],

    'sections' => [
        'photos' => 'Photos',
        'personal' => 'Personal information',
        'experience' => 'Employment background',
        'skills' => 'Skills',
        'details_housemaid' => 'More details',
        'details_driver' => 'Driving licence & professional test',
        'details_house_boy' => 'More details',
        'details_care_giver' => 'More details',
    ],

    'documents_hint' => [
        'driver' => 'Upload the driving licence image and the professional test certificate in the "Documents" tab.',
    ],

    'fields' => [
        'photo' => 'Photo',
        'full_photo' => 'Full-body photo',
        'cv_reference_no' => 'Reference no.',
        'cv_category' => 'Category',
        'position' => 'Position',
        'salary' => 'Salary',
        'name' => 'Name',
        'home_city' => 'City',
        'nationality' => 'Nationality',
        'gender' => 'Gender',
        'age' => 'Age',
        'birth_date' => 'Date of birth',
        'education' => 'Education',
        'religion' => 'Religion',
        'marital_status' => 'Marital status',
        'children_count' => 'No. of kids',
        'height_cm' => 'Height (cm)',
        'weight_kg' => 'Weight (kg)',
        'arabic_level' => 'Arabic language',
        'english_level' => 'English language',
    ],

    'experience' => [
        'country' => 'Country',
        'city' => 'City',
        'position' => 'Position',
        'duration' => 'Duration of employment',
        'company' => 'Company',
        'location' => 'Location',
        'started_at' => 'Duration (from)',
        'ended_at' => 'To',
        'description' => 'Job description',
        'description_hint' => 'e.g. patient care, preparing meals per diet, giving medicines on time...',
        'present' => 'present',
        'hint' => 'Leave the rows empty for a first-timer (shown as N/A on the CV).',
        'hint_detailed' => 'One job per block. Leave "To" empty if still employed.',
        'none' => 'No previous experience.',
    ],

    'skills' => [
        // Housemaid
        'special_needs' => 'Special needs',
        'elder_care' => 'Elder care',
        'help_kids' => 'Help kids',
        'infants_care' => 'Infants care',
        'pets_care' => 'Help only - pets care',
        'home_cleaning' => 'Home cleaning',
        'washing_ironing' => 'Clothes washing & ironing',
        'cooking' => 'Cooking',
        'help_cooking' => 'Help in cooking',
        // Driver
        'sedan' => 'Sedan',
        'pickup' => 'Pick-up',
        'minivan' => 'MiniVan',
        'suv' => 'SUV',
        'saudi_roads' => 'Knowledge of Saudi roads and routes',
        'saudi_traffic_laws' => 'Knowledge of Saudi traffic laws',
        'road_navigation' => 'Road navigation skills',
        'saudi_license' => 'Saudi license',
        // House boy
        'perform_cleaning' => 'Perform cleaning',
        'dish_washing' => 'Dish washing',
        'car_washing' => 'Car washing',
        'gardening' => 'Gardening',
        'assigned_tasks' => 'Done assigned tasks',
        'purchase_necessary' => 'Purchase necessary',
    ],

    'details' => [
        'license_number' => ['label' => 'Driving licence no.'],
        'license_expiry' => ['label' => 'Driving licence expiry'],
        'test_video_url' => ['label' => 'Professional test video link'],
    ],

    'pdf' => [
        'title' => 'Candidate\'s Resume',
        'date' => 'Date',
    ],

    'portal' => [
        'title' => 'Our workers',
        'hint' => 'Workers linked to your agency. Fill in the CV and upload the photos for each worker.',
        'search' => 'Name or reference no...',
        'cv_status' => 'CV',
        'filled' => 'Filled :date',
        'missing' => 'Not filled',
    ],

    'flash' => [
        'updated' => 'CV saved.',
    ],
];
