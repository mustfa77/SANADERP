<?php

return [
    'title' => 'Dashboard',
    'demo_notice' => 'These figures are sample data for display only. They will be replaced with real numbers once the recruitment, rental and finance modules are live.',
    'from_previous' => 'vs. last month',
    'sar' => 'SAR',
    'total' => 'Total',

    'kpis' => [
        'workers_on_duty' => 'Workers on duty',
        'active_rentals' => 'Active rental contracts',
        'recruitment_in_progress' => 'Recruitment in progress',
        'month_revenue' => 'Revenue this month',
    ],

    'overview' => 'Revenue & contracts',
    'overview_contracts' => 'New contracts',
    'last_12_months' => 'Last 12 months',
    'chart' => [
        'rental' => 'Rental revenue',
        'recruitment' => 'Recruitment fees',
        'contracts' => 'New contracts',
    ],
    'totals' => [
        'rental_revenue' => 'Rental revenue',
        'recruitment_fees' => 'Recruitment fees',
        'refunds' => 'Refunds',
    ],

    'workers_by_nationality' => 'Workers by nationality',
    'nationalities' => [
        'philippines' => 'Philippines',
        'indonesia' => 'Indonesia',
        'kenya' => 'Kenya',
        'uganda' => 'Uganda',
        'ethiopia' => 'Ethiopia',
        'sri_lanka' => 'Sri Lanka',
    ],

    'recruitment_pipeline' => 'Recruitment pipeline',
    'pipeline' => [
        'new_request' => 'New request',
        'candidate_selected' => 'Candidate selected',
        'visa_issued' => 'Visa issued',
        'ticket_booked' => 'Ticket booked',
        'arrived' => 'Arrived (awaiting handover)',
    ],

    'needs_follow_up' => 'Needs follow-up',
    'alerts' => [
        'iqama_expiring' => ['title' => 'Iqamas expiring soon', 'hint' => 'Within 30 days'],
        'passport_expiring' => ['title' => 'Passports expiring soon', 'hint' => 'Within 6 months'],
        'rentals_ending' => ['title' => 'Rental contracts ending', 'hint' => 'Within 7 days'],
        'workers_in_camp' => ['title' => 'Workers in housing', 'hint' => 'Available for rent, no contract'],
        'overdue_invoices' => ['title' => 'Overdue invoices', 'hint' => 'Past the due date'],
        'open_cases' => ['title' => 'Open cases', 'hint' => 'Absconding or complaints in progress'],
    ],

    'by_branch' => 'By branch',
    'no_branches' => 'No active branches.',
    'branch' => 'Branch',
    'workers' => 'Workers',
    'contracts' => 'Contracts',
    'revenue' => 'Revenue',

    'latest_contracts' => 'Latest contracts',
    'table' => [
        'number' => 'Contract no.',
        'client' => 'Client',
        'worker' => 'Worker',
        'type' => 'Type',
        'start' => 'Start date',
        'amount' => 'Amount',
        'status' => 'Status',
    ],
    'contract_types' => [
        'monthly' => 'Monthly rental',
        'hourly' => 'Hourly rental',
        'recruitment' => 'Recruitment',
    ],
    'contract_statuses' => [
        'active' => 'Active',
        'pending' => 'In progress',
        'ended' => 'Ended',
        'cancelled' => 'Cancelled',
    ],
];
