<?php

return [
    'title' => 'Cases',
    'show' => 'Case :number',
    'hint' => 'A case opens automatically when a worker becomes "awaiting decision" (returned by a client, manual status change). Decide: sponsorship transfer, deportation, or back to work.',
    'search_placeholder' => 'Case no. or worker name...',
    'none' => 'No cases.',
    'days' => ':count days',
    'decide_title' => 'Decision',
    'waiting_decision' => 'Waiting for the decision of a manager (permission "Decide cases").',
    'confirm_decide' => 'The worker status changes according to the decision.',
    'confirm_complete' => 'The case is closed and the worker gets the final status.',
    'other_sponsor' => 'Other sponsor (write the name)',
    'notes' => 'Follow-up',
    'add_note' => 'Add a follow-up note...',
    'no_notes' => 'No notes yet.',

    'complete_title' => [
        'transfer' => 'Transfer done',
        'deportation' => 'Worker deported',
    ],
    'complete_hint' => [
        'transfer' => 'When the sponsorship transfer is completed, record the date: the worker becomes "transferred".',
        'deportation' => 'When the worker has left the country, record the date: the worker becomes "deported".',
    ],

    'fields' => [
        'number' => 'Case no.',
        'worker_id' => 'Worker',
        'reason' => 'Reason',
        'related' => 'From',
        'opened_at' => 'Opened',
        'decision' => 'Decision',
        'decision_note' => 'Decision note',
        'status' => 'Status',
        'new_sponsor' => 'New sponsor',
        'new_sponsor_client_id' => 'New sponsor (client)',
        'new_sponsor_name' => 'New sponsor name',
        'new_sponsor_id_number' => 'New sponsor ID number',
        'transfer_fee' => 'Transfer fee',
        'flight_date' => 'Flight date',
        'ticket_cost' => 'Ticket cost',
        'completed_at' => 'Completed on',
        'note' => 'Note',
    ],

    'statuses' => [
        'open' => 'Waiting for decision',
        'in_progress' => 'In progress',
        'closed' => 'Closed',
    ],

    'decisions' => [
        'transfer' => 'Sponsorship transfer',
        'deportation' => 'Deportation',
        'return_to_work' => 'Back to work',
    ],

    'actions' => [
        'decide' => 'Save decision',
        'complete' => 'Complete',
        'add_note' => 'Add',
    ],

    'reasons' => [
        'decided' => 'Case :number: :decision',
        'completed' => 'Case :number completed: :decision',
    ],

    'errors' => [
        'worker_not_awaiting' => 'The worker is not awaiting a decision any more (status ":status").',
        'sponsor_required' => 'Choose the new sponsor or write the name.',
        'wrong_status' => 'This is not possible while the case is ":status".',
        'date_before_decision' => 'The date is before the decision.',
    ],

    'flash' => [
        'decided' => 'Decision saved.',
        'completed' => 'Case closed.',
        'note' => 'Note added.',
    ],
];
