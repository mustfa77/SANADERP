<?php

return [
    'title' => 'Refunds',
    'none' => 'No refund requests.',
    'confirm_approve' => 'Approve refunding :amount to the client? The amount paid is reduced.',

    'fields' => [
        'payable' => 'Contract / booking',
        'amount' => 'Refund amount',
        'reason' => 'Reason',
        'requested' => 'Requested by',
        'status' => 'Status',
    ],

    'statuses' => [
        'pending' => 'Waiting for approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
    ],

    'flash' => [
        'approved' => 'Refund approved.',
        'rejected' => 'Refund rejected.',
    ],
];
