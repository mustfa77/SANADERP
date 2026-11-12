<?php

/*
|--------------------------------------------------------------------------
| Worker status workflow
|--------------------------------------------------------------------------
|
| The ONLY place that defines which status changes are allowed. Keys and
| values are App\Enums\WorkerStatus values. Read by
| App\Services\Workers\WorkerStatusService.
|
*/

return [
    /*
    | from => list of allowed targets. Final statuses have no targets.
    */
    'transitions' => [
        'candidate_new' => ['under_review', 'rejected'],
        'under_review' => ['ready_for_selection', 'rejected', 'candidate_new'],
        'rejected' => ['candidate_new'],
        'ready_for_selection' => ['on_hold'],
        'on_hold' => ['ready_for_selection', 'contracted'],
        'contracted' => ['in_travel_process'],
        'in_travel_process' => ['arrived'],
        'arrived' => ['delivered', 'available', 'awaiting_decision'],
        'delivered' => ['in_probation'],
        'in_probation' => ['probation_completed', 'awaiting_decision', 'absconded'],
        'probation_completed' => [],
        'available' => ['rented_monthly', 'sick', 'suspended', 'exit_reentry', 'awaiting_decision'],
        'rented_monthly' => ['available', 'awaiting_decision', 'absconded'],
        'sick' => ['available', 'awaiting_decision'],
        'suspended' => ['available', 'awaiting_decision'],
        'exit_reentry' => ['available', 'awaiting_decision'],
        'awaiting_decision' => ['for_deportation', 'for_transfer', 'available'],
        'for_deportation' => ['deported'],
        'for_transfer' => ['ready_for_selection', 'transferred'],
        'deported' => [],
        'transferred' => [],
        'absconded' => [],
    ],

    /*
    | Extra conditions on a transition: from => to => worker attribute values.
    */
    'conditions' => [
        'arrived' => [
            'delivered' => ['sponsor_type' => 'client'],
            'available' => ['sponsor_type' => 'company'],
        ],
    ],

    /*
    | Targets a user may choose with the "Change status" button (still only
    | when the transition above is allowed). "from" limits the source status.
    | Everything else is triggered by the modules (recruitment, rental, cases).
    */
    'manual' => [
        'sick' => [],
        'suspended' => [],
        'exit_reentry' => [],
        'available' => ['from' => ['sick', 'suspended', 'exit_reentry']],
        'awaiting_decision' => [],
        'absconded' => [],
    ],

    /*
    | Statuses a worker may start with when entered by hand (existing workers
    | already in the country). New candidates start as candidate_new.
    */
    'initial' => ['candidate_new', 'available', 'delivered', 'rented_monthly'],
];
