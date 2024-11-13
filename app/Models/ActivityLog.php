<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ActivityLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'activity_logs'; // Define the MongoDB collection name

    protected $fillable = [
        'user',
        'action',
        'collection_name',
        'document_before',
        'timestamp',
    ];

    protected $casts = [
        'document_before' => 'array', // Cast to array for JSON storage
        'timestamp' => 'datetime', // Cast to Carbon date
    ];
}
