<?php

return [
    'excluded_collections' => [
        'password_reset_tokens',
        'personal_access_tokens',
        'migrations',
        'users',
        'failed_jobs',
        'activity_logs',
        'collection_metadata',
    ],

    'excluded_columns' => [
        'is_active',
        'is_deleted',
        'system_info',
    ],

    'default_per_page' => 25,


    'languages' => [
        'English' => 'en',
        'Indonesian' => 'id',
        'Malay' => 'ms',
    ],
];
