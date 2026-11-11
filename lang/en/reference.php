<?php

return [
    'add' => 'Add :item',
    'edit' => 'Edit :item',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',
    'confirm_deactivate' => 'It will no longer be offered for new records. Linked records stay as they are.',
    'order' => 'Order',
    'move_up' => 'Move up',
    'move_down' => 'Move down',
    'required' => 'Required',
    'optional' => 'Optional',
    'days' => ':count days',
    'all_countries' => 'All countries',
    'all_professions' => 'All professions',
    'field_in_language' => [
        'ar' => ':field (Arabic)',
        'en' => ':field (English)',
    ],

    'tabs' => [
        'stages' => 'Travel stages',
        'documents' => 'Documents',
    ],
    'stages_hint' => 'Stages appear in this order when tracking recruitment for this country.',
    'no_stages' => 'No travel stages for this country.',
    'max_days_hint' => 'Expected time to complete the stage; an alert shows when exceeded.',
    'country_documents_hint' => 'Documents specific to this country, added to the global documents:',
    'global_document_types' => 'Global documents (all countries)',
    'global_document_types_hint' => 'Required from every candidate whatever the country, before the country-specific documents.',
    'no_document_types' => 'No documents defined.',
    'document_types_hint' => 'This order is the upload screen order and the printed authority file order. Deactivating a document hides it for new candidates only; uploaded files are kept.',
    'authority_hint' => 'Optional: link the document to one authority so it is only required for that authority\'s file.',

    'resources' => [
        'countries' => ['title' => 'Countries', 'singular' => 'country'],
        'stages' => ['title' => 'Travel stages', 'singular' => 'stage'],
        'agencies' => ['title' => 'External agencies', 'singular' => 'agency'],
        'authorities' => ['title' => 'External authorities', 'singular' => 'authority'],
        'camps' => ['title' => 'Housing', 'singular' => 'camp'],
        'professions' => ['title' => 'Professions', 'singular' => 'profession'],
        'shifts' => ['title' => 'Shifts', 'singular' => 'shift'],
        'prices' => [
            'title' => 'Prices',
            'singular' => 'price',
            'duplicate_active' => 'An active price already exists for this country and rental type. Deactivate it first.',
        ],
        'lookups' => ['title' => 'Lookup lists', 'singular' => 'item'],
        'completed_visa_lists' => ['title' => 'Completed visa lists', 'singular' => 'visa list setting'],
        'document_types' => ['title' => 'Document types', 'singular' => 'document'],
    ],

    'fields' => [
        'name' => 'Name',
        'nationality' => 'Nationality',
        'code' => 'Country code (ISO)',
        'country_id' => 'Country',
        'contact_person' => 'Contact person',
        'phone' => 'Phone',
        'email' => 'Email',
        'city' => 'City',
        'address' => 'Address',
        'district' => 'District',
        'branch_id' => 'Branch',
        'type' => 'Type',
        'capacity' => 'Capacity',
        'start_time' => 'Start time',
        'move_time' => 'Driver departure time',
        'hours' => 'Hours',
        'rental_type' => 'Rental type',
        'price' => 'Price before VAT',
        'vat_percent' => 'VAT %',
        'authority_id' => 'Authority',
        'profession_id' => 'Profession',
        'visas_count' => 'Number of visas',
        'max_days' => 'Max days',
        'is_required' => 'Required',
        'include_in_authority_file' => 'In authority file',
        'is_active' => 'Status',
    ],

    'camps' => [
        'types' => [
            'rental' => 'Rental',
            'individual' => 'Individual',
            'mixed' => 'Mixed',
        ],
    ],

    'prices' => [
        'rental_types' => [
            'monthly' => 'Monthly',
            'hourly' => 'Hourly',
        ],
    ],

    'lookups' => [
        'types' => [
            'return_reason' => 'Return reasons',
            'cancel_reason' => 'Cancel reasons',
            'payment_method' => 'Payment methods',
        ],
    ],

    'flash' => [
        'created' => 'The :item was added.',
        'updated' => 'The :item was updated.',
        'activated' => 'The :item was activated.',
        'deactivated' => 'The :item was deactivated.',
        'deleted' => 'The :item was deleted.',
        'in_use' => 'This :item cannot be deleted because other records use it. Deactivate it instead.',
    ],
];
