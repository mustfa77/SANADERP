<?php

return [
    'title' => 'Trips',
    'drivers_title' => 'Drivers',
    'create' => 'New trip',
    'save' => 'Save trip',
    'add_driver' => 'Add a driver',
    'save_driver' => 'Save driver',
    'none' => 'No trips on this day.',
    'no_drivers' => 'No drivers yet.',
    'unassigned' => 'No driver',
    'trips_today' => 'Trips today',
    'seats' => ':count seats',
    'driver_no_trips' => 'No trips for you now.',
    'done_today' => 'Done today',

    'hints' => [
        'unassigned' => ':count trips have no driver yet.',
        'driver' => 'The driver logs in to the driver portal with this e-mail and password (asked to change it at the first login).',
    ],

    'fields' => [
        'date' => 'Day',
        'scheduled_at' => 'Time',
        'type' => 'Trip',
        'route' => 'From → To',
        'from_place' => 'From',
        'to_place' => 'To',
        'worker_id' => 'Worker',
        'client_id' => 'Client',
        'driver_id' => 'Driver',
        'branch_id' => 'Branch',
        'notes' => 'Notes',
        'status' => 'Status',
    ],

    'driver_fields' => [
        'name_ar' => 'Name (Arabic)',
        'name_en' => 'Name (English)',
        'email' => 'E-mail (login)',
        'phone' => 'Mobile',
        'password' => 'Password',
        'branch_id' => 'Branch',
        'license_number' => 'Licence no.',
        'license_expiry' => 'Licence expiry',
        'vehicle_model' => 'Vehicle',
        'vehicle_plate' => 'Plate no.',
        'seats' => 'Seats',
    ],

    'types' => [
        'hourly_dropoff' => 'Hourly — take to client',
        'hourly_pickup' => 'Hourly — bring back',
        'delivery' => 'Home delivery',
        'manual' => 'Other trip',
    ],

    'statuses' => [
        'scheduled' => 'Scheduled',
        'started' => 'On the way',
        'completed' => 'Done',
        'cancelled' => 'Cancelled',
    ],

    'actions' => [
        'start' => 'On my way',
        'complete' => 'Done',
        'cancel' => 'Cancel trip',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
    ],

    'errors' => [
        'closed' => 'This trip is already done or cancelled.',
        'not_scheduled' => 'The trip has already started.',
        'no_driver' => 'Assign a driver first.',
        'driver_not_allowed' => 'The driver must be an active driver of the trip\'s branch.',
    ],

    'flash' => [
        'created' => 'Trip created.',
        'assigned' => 'Driver saved.',
        'started' => 'Trip started.',
        'completed' => 'Trip done.',
        'cancelled' => 'Trip cancelled.',
        'driver_created' => 'Driver added.',
        'driver_updated' => 'Driver updated.',
    ],
];
