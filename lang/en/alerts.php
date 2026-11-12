<?php

return [
    'title' => 'Alerts',
    'hint' => 'Recalculated every hour from the data and the periods in Settings > Alerts. An alert closes by itself when its cause is gone, or when you mark it as handled.',
    'none' => 'No alerts.',
    'view_all' => 'All alerts',
    'refresh' => 'Refresh now',
    'resolve' => 'Handled',
    'show_resolved' => 'Show closed',
    'show_open' => 'Show open',
    'auto_resolved' => 'Closed automatically',

    'fields' => [
        'type' => 'Alert',
        'message' => 'Details',
        'due_at' => 'Date',
        'branch' => 'Branch',
    ],

    'types' => [
        'iqama_expiry' => 'Iqama expiry',
        'passport_expiry' => 'Passport expiry',
        'medical_expiry' => 'Medical check expiry',
        'contract_end' => 'Contract ends',
        'probation_end' => 'Probation ends',
        'overdue_stage' => 'Travel stage late',
        'hold_expired' => 'Hold time over',
        'worker_absent' => 'Absent from housing',
        'trip_unassigned' => 'Trip without driver',
        'license_expiry' => 'Driver licence expiry',
        'case_waiting' => 'Case waiting',
    ],

    'messages' => [
        'iqama_expiry' => 'Iqama of :worker (:number) expires on :date.',
        'passport_expiry' => 'Passport of :worker (:number) expires on :date.',
        'medical_expiry' => 'Medical check of :worker (:number) expires on :date.',
        'contract_end' => 'Contract :number (:worker — :client) ends on :date.',
        'probation_end' => 'Probation of :worker (:client, order :number) ends on :date.',
        'overdue_stage' => 'Order :number: stage ":stage" is :days days late.',
        'hold_expired' => ':worker is held for :client (order :number) longer than allowed.',
        'worker_absent' => ':worker is absent today from :camp.',
        'trip_unassigned' => 'Trip :number at :time has no driver.',
        'license_expiry' => 'Licence of driver :driver expires on :date.',
        'case_waiting' => 'Case :number of :worker waits for a decision for :days days.',
    ],

    'flash' => [
        'refreshed' => 'Alerts refreshed: :created new, :resolved closed.',
        'resolved' => 'Alert marked as handled.',
    ],
];
