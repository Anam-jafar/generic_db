<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as Eloquent;

class CollectionMetadata extends Eloquent
{
    protected $connection = 'mongodb'; // Ensure this is connected to MongoDB
    protected $collection = 'collection_metadata';

    protected $fillable = ['collection_name', 'fields', 'created_at', 'updated_at'];
}
