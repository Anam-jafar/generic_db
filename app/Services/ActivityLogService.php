<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public static function log($action, $collectionName, $documentBefore = null)
    {
        ActivityLog::create([
            'user' => Auth::user()->email,
            'action' => $action,
            'collection_name' => $collectionName,
            'document_before' => $documentBefore,
            'timestamp' => now(),
        ]);
    }
}

