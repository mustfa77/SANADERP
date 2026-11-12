<?php

use App\Models\Candidate;
use App\Models\Client;
use App\Models\Worker;

return [
    /*
    |--------------------------------------------------------------------------
    | Document owners
    |--------------------------------------------------------------------------
    |
    | Models allowed to own documents, by the alias used in URLs. Each must
    | implement App\Services\Documents\DocumentOwner and use HasDocuments.
    | "permission" is required (in addition to documents.*) to reach the
    | owner's documents. The owner is loaded with its global scopes, so an
    | owner outside the user's branches is a 404.
    |
    */
    'owners' => [
        'candidate' => ['model' => Candidate::class, 'permission' => 'candidates.view'],
        'worker' => ['model' => Worker::class, 'permission' => 'workers.view'],
        'client' => ['model' => Client::class, 'permission' => 'clients.view'],
    ],

    /*
    | Accepted upload types. The size limit is the "document_max_upload_mb"
    | setting (Settings > Alerts & documents).
    */
    'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],

    /*
    | Folders on the private "local" disk.
    */
    'directory' => 'documents',
    'prints_directory' => 'document-prints',

    /*
    | Optional path to the qpdf binary, used to convert PDF 1.5+ files that
    | the bundled FPDI parser cannot import. Leave empty to look on PATH.
    */
    'qpdf_binary' => env('QPDF_BINARY', 'qpdf'),
];
