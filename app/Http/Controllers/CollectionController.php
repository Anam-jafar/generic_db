<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use MongoDB\Client as MongoDBClient;
use App\Exports\CollectionTemplateExport;
use App\Services\ActivityLogService;
use App\Models\ActivityLog;

class CollectionController extends Controller
{
    protected $database;

    public function __construct()
    {
        $this->database = (new MongoDBClient)->selectDatabase('generic_data');
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $mongoClient = DB::connection('mongodb')->getMongoClient();
        $database = $mongoClient->selectDatabase('generic_data');
        $collections = $database->listCollections();

        $excludedCollections = [
            'password_reset_tokens',
            'personal_access_tokens',
            'migrations',
            'users',
            'failed_jobs',
            'activity_logs',
        ];

        $collectionsArray = [];
        foreach ($collections as $collection) {
            $collectionName = $collection->getName();
            if (in_array($collectionName, $excludedCollections)) {
                continue;
            }
            $collectionSize = DB::connection('mongodb')
                                ->getCollection($collectionName)
                                ->countDocuments(['deleted' => 0]);

            if (empty($search) || stripos($collectionName, $search) !== false) {
                $collectionsArray[] = ['name' => $collectionName, 'size' => $collectionSize];
            }
        }

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($collectionsArray, ($currentPage - 1) * $perPage, $perPage);
        $collections = new LengthAwarePaginator($currentItems, count($collectionsArray), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        return view('collections.index', compact('collections'));
    }

    public function suggestCollections(Request $request)
    {
        $search = $request->get('search', '');
        $mongoClient = DB::connection('mongodb')->getMongoClient();
        $database = $mongoClient->selectDatabase('generic_data');
        $collections = $database->listCollections();
    
        $excludedCollections = [
            'password_reset_tokens', 'personal_access_tokens', 'migrations', 'users', 'failed_jobs',
        ];
    
        $suggestions = [];
        foreach ($collections as $collection) {
            $collectionName = $collection->getName();
    
            if (in_array($collectionName, $excludedCollections)) {
                continue;
            }
    
            if (empty($search) || stripos($collectionName, $search) !== false) {
                $formattedName = ucwords(str_replace('_', ' ', $collectionName));
                $suggestions[] = $formattedName;
            }
        }
    
        return response()->json($suggestions);
    }

    public function ajax(Request $request)
    {
        return response()->json("Hello");
    }

    
    public function show(Request $request, $collectionName)
    {
        $showAll = $request->get('show_all', false);
        
        $perPage = $request->get('per_page', 10);
        
        $search = $request->get('search', '');
    
        $filter = $showAll ? [] : ['deleted' => 0];
        
        $query = DB::connection('mongodb')->getCollection($collectionName)
                    ->find($filter)
                    ->toArray();
    
        if ($search) {
            $query = array_filter($query, function($document) use ($search) {
                foreach ($document as $key => $value) {
                    if (strpos((string)$value, $search) !== false) {
                        return true; 
                    }
                }
                return false; 
            });
        }
    
        // Implement pagination
        $total = count($query);
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $documents = array_slice($query, $offset, $perPage);
    
        // Create a simple pagination array for the view
        $pagination = [
            'current_page' => $currentPage,
            'last_page' => ceil($total / $perPage),
            'per_page' => $perPage,
            'total' => $total,
        ];
    
        // Return the view with the paginated documents and pagination info
        return view('collections.show', compact('collectionName', 'documents', 'showAll', 'total', 'perPage', 'search', 'pagination'));
    }
    
    
    
    

    public function edit($collectionName, $id)
    {
        $document = DB::connection('mongodb')->getCollection($collectionName)
                      ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'deleted' => 0]);

        return view('collections.edit', compact('collectionName', 'document'));
    }

    public function update(Request $request, $collectionName, $id)
    {
        $request->validate([
            'data' => 'required|array',
        ]);

        $documentBefore = DB::connection('mongodb')->getCollection($collectionName)
                            ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'deleted' => 0]);
        
        DB::connection('mongodb')->getCollection($collectionName)
            ->updateOne(['_id' => new \MongoDB\BSON\ObjectId($id)], ['$set' => $request->input('data')]);

        ActivityLogService::log('update', $collectionName, $documentBefore);

        return redirect()->route('collections.show', $collectionName)->with('success', 'Document updated successfully.');
    }

    public function destroy($collectionName, $id)
    {
        $documentBefore = DB::connection('mongodb')->getCollection($collectionName)
                            ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'deleted' => 0]);

        DB::connection('mongodb')->getCollection($collectionName)
            ->updateOne(['_id' => new \MongoDB\BSON\ObjectId($id)], ['$set' => ['deleted' => 1]]);

        ActivityLogService::log('delete', $collectionName, $documentBefore);

        return redirect()->route('collections.show', $collectionName)->with('success', 'Document deleted successfully.');
    }

    public function download($collectionName, $includeData = false)
    {
        $documents = DB::connection('mongodb')->getCollection($collectionName)
                        ->find(['deleted' => 0])
                        ->toArray();
        
        $headers = $documents ? array_keys($documents[0]->getArrayCopy()) : [];
        $headers = array_filter($headers, fn($key) => $key !== '_id');

        $excelData = [$headers];

        if ($includeData) {
            foreach ($documents as $document) {
                $row = [];
                foreach ($headers as $header) {
                    $row[] = $document[$header] ?? '';
                }
                $excelData[] = $row;
            }
        }

        return Excel::download(new \App\Exports\CollectionTemplateExport($excelData), $collectionName . ($includeData ? '_data.xlsx' : '_template.xlsx'));
    }

    public function bulkUpload(Request $request, $collectionName)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);
    
        $path = $request->file('file')->store('uploads');
        $data = Excel::toArray([], storage_path("app/{$path}"));
        $rows = $data[0];

        
    
        $headers = array_shift($rows); // Remove header row
    
        // $errors = [];
    
        // foreach ($rows as $index => $row) {
        //     if (strlen($row[0]) > 16) {
        //         $errors[] = "Row " . ($index + 2) . ": 'Brand' length exceeds 16 characters.";
        //     }
        //     if (strlen($row[1]) > 32) {
        //         $errors[] = "Row " . ($index + 2) . ": 'Model' length exceeds 32 characters.";
        //     }
        //     if (!is_numeric($row[2])) {
        //         $errors[] = "Row " . ($index + 2) . ": 'CC' must be an integer.";
        //     }
        // }
    
        // if (!empty($errors)) {
        //     Storage::delete($path);
        //     Log::error('Data import validation failed: ' . implode(', ', $errors));
        //     return redirect()->back()->with('error', 'Data import failed due to validation errors: <br>' . implode('<br>', $errors));
        // }
    
        try {
            $this->insertDataIntoCollection($collectionName, $headers, $rows);
            ActivityLogService::log('Insertion', $collectionName, 'Bulk Upload from Excel sheet');
        } catch (\Exception $e) {
            Log::error('Data import failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Data import failed: ' . $e->getMessage());
        } finally {
            Storage::delete($path);
        }
    
        return redirect()->back()->with('success', 'Data imported successfully.');
    }

    private function insertDataIntoCollection($collectionName, array $headers, array $rows)
    {
        $insertData = [];
        foreach ($rows as $row) {
            $insertRow = array_combine($headers, $row);
            $insertRow['deleted'] = 0; // Ensure deleted is set to 0 for new data
            $insertData[] = $insertRow;
        }

        if (!empty($insertData)) {
            DB::connection('mongodb')->getCollection($collectionName)->insertMany($insertData);
        }
    }

    public function activityLogs()
    {
        $logs = ActivityLog::orderBy('timestamp', 'desc')->get();
        return view('activity_logs.index', compact('logs'));
    }

    public function showWarning()
    {
        session()->forget('hide_warning');
        return response()->json(['show' => !session()->get('hide_warning', false)]);
    }
    
    public function hideWarning()
    {
        session(['hide_warning' => true]);
        return response()->json(['status' => 'success']);
    }
}
