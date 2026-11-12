<?php

return [
    'title' => 'Housing',
    'tab' => 'Housing',
    'movements_title' => 'Housing movements',
    'none' => 'No active housing. Add one in Settings > Camps.',
    'empty' => 'No worker in this housing.',
    'no_movements' => 'No movements.',
    'check_in' => 'Check a worker in',
    'move_title' => 'Transfer / check out',
    'check_out_option' => 'Check out (leave housing)',
    'attendance_not_taken' => 'Roll-call not taken today',
    'attendance_taken' => 'Roll-call saved for :count workers (:by)',
    'current' => 'Current housing',
    'not_housed' => 'Not in a housing',
    'system' => 'System',
    'search_worker' => 'Worker name...',

    'summary' => [
        'camps' => 'Active housings',
        'housed' => 'Workers housed',
        'movements_today' => 'Movements today',
        'unhoused' => 'In the office without housing',
    ],

    'hints' => [
        'unhoused' => ':count workers are in the office (arrived, available, sick, awaiting decision...) but not in any housing. Check them in from the housing page.',
        'no_movable' => 'No worker of this branch can be checked in now (only workers in the office: arrived, available, sick, suspended, awaiting decision, for transfer or deportation).',
        'note' => 'Reason (e.g. moved from another housing, back from hospital)',
    ],

    'tabs' => [
        'workers' => 'Workers',
        'attendance' => 'Daily roll-call',
        'movements' => 'Movements',
    ],

    'fields' => [
        'worker_id' => 'Worker',
        'camp' => 'Housing',
        'to_camp_id' => 'To housing',
        'from_camp_id' => 'From',
        'note' => 'Reason / note',
        'date' => 'Date',
        'status' => 'Roll-call',
        'today' => 'Today',
        'type' => 'Movement',
        'moved_at' => 'Date and time',
        'created_by' => 'By',
    ],

    'movement_types' => [
        'in' => 'Check-in',
        'out' => 'Check-out',
        'transfer' => 'Transfer',
    ],

    'attendance_statuses' => [
        'present' => 'Present',
        'absent' => 'Absent',
        'sick' => 'Sick',
        'outside' => 'Outside (permission)',
    ],

    'actions' => [
        'check_in' => 'Check in',
        'save_attendance' => 'Save roll-call',
        'all_present' => 'Mark all present',
    ],

    'errors' => [
        'camp_not_allowed' => 'The housing must be an active housing of the worker\'s branch.',
        'status_not_housed' => 'A worker in status ":status" cannot be housed.',
        'camp_full' => 'The housing ":camp" is full.',
        'same_camp' => 'The worker is already there.',
        'future_date' => 'The roll-call date cannot be in the future.',
    ],

    'flash' => [
        'checked_in' => 'Worker checked in.',
        'transferred' => 'Worker transferred.',
        'checked_out' => 'Worker checked out of the housing.',
        'attendance' => 'Roll-call saved (:count workers).',
    ],
];
