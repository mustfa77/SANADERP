<?php

return [
    'checklist' => 'Documents',
    'allowed_files' => 'Allowed files: :types — up to :size MB',
    'view' => 'View',
    'upload' => 'Upload',
    'upload_here' => 'Upload here',
    'replace' => 'Replace',
    'delete_version' => 'Delete version',
    'current' => 'Current',
    'version_short' => 'v:version',
    'versions' => 'Versions (:count)',
    'extra_documents' => 'Extra documents',
    'add_extra' => 'Add extra document',
    'no_extra_documents' => 'No extra documents.',

    'print_authority_file' => 'Print :authority file',
    'missing_title' => 'Missing documents',
    'missing_intro' => 'The :authority file cannot be printed until these required documents are uploaded:',

    'status' => [
        'uploaded' => 'Uploaded',
        'missing' => 'Not uploaded',
    ],

    'fields' => [
        'document' => 'Document',
        'status' => 'Status',
        'uploaded' => 'Uploaded',
        'version' => 'Version',
        'file' => 'File',
        'title' => 'Document title',
    ],

    'pdf' => [
        'title' => 'File submitted to :authority',
        'owner' => 'Name',
        'authority' => 'Authority',
        'printed_at' => 'Printed at',
        'printed_by' => 'Printed by',
        'contents' => 'Contents',
        'no_documents' => 'No documents attached.',
        'completed_visas_title' => 'Latest :count completed visas — :country',
        'visa_number' => 'Visa number',
        'worker_name' => 'Name',
        'issued_at' => 'Issued at',
        'no_completed_visas' => 'No completed visas yet.',
    ],

    'flash' => [
        'uploaded' => 'Document ":name" uploaded.',
        'replaced' => 'Document ":name" replaced; the previous version is kept.',
        'version_deleted' => 'Version :version deleted.',
        'last_version' => 'The only version of a document cannot be deleted.',
        'cannot_merge' => 'Document ":name" could not be merged: the PDF uses a newer format that is not supported. Save it as an older PDF or upload it as an image, or install qpdf on the server.',
    ],

    'demo' => [
        'title' => 'Documents engine demo',
        'notice' => 'Development page only (works when APP_ENV=local). The owner is a demo candidate.',
        'owner' => 'Owner: :name',
        'no_authority' => 'No authority for this country',
    ],
];
