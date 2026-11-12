<?php

return [
    'title' => 'Payments',
    'record' => 'Record a payment',
    'verified' => 'Verified',
    'pending' => 'Not verified',
    'view_receipt' => 'Receipt',
    'confirm_delete' => 'The payment is removed (only unverified payments can be removed).',

    'summary' => [
        'total' => 'Total',
        'paid' => 'Paid',
        'balance' => 'Balance',
    ],

    'hints' => [
        'receipt' => 'Transfer notice or receipt (PDF or image).',
    ],

    'fields' => [
        'amount' => 'Amount',
        'paid_at' => 'Payment date',
        'payment_method_id' => 'Payment method',
        'reference' => 'Transfer / reference no.',
        'receipt' => 'Transfer notice',
        'notes' => 'Notes',
    ],

    'statuses' => [
        'unpaid' => 'Unpaid',
        'partial' => 'Partly paid',
        'paid' => 'Paid',
    ],

    'actions' => [
        'record' => 'Record',
        'verify' => 'Verify',
        'delete' => 'Remove payment',
    ],

    'flash' => [
        'recorded' => 'Payment recorded.',
        'verified' => 'Payment verified.',
        'deleted' => 'Payment removed.',
    ],
];
