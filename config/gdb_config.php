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
        'deleted',
        'created_at',
        'updated_at',
    ],

    'default_per_page' => 25,


];
