<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CollectionMetadata;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CollectionTemplateExport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MongoDB\Client as MongoDBClient;
use MongoDB\BSON\UTCDateTime;
use Carbon\Carbon;


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
    $perPage = $request->get('per_page', config('gdb_config.default_per_page'));
    
    // $mongoClient = DB::connection('mongodb')->getMongoClient();
    // $database = $mongoClient->selectDatabase('generic_data');
    $collections = $this->database->listCollections();

    $excludedCollections = config('gdb_config.excluded_collections');

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

    $total = count($collectionsArray);
    $currentPage = $request->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    $currentItems = array_slice($collectionsArray, $offset, $perPage);

    $pagination = [
        'current_page' => $currentPage,
        'last_page' => ceil($total / $perPage),
        'per_page' => $perPage,
        'total' => $total,
    ];

    $collections = new LengthAwarePaginator($currentItems, $total, $perPage, $currentPage, [
        'path' => LengthAwarePaginator::resolveCurrentPath(),
    ]);

    return view('collections.index', compact('collections', 'pagination', 'search', 'perPage'));
}

    public function create()
    {
        return view('collections.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'collection_name' => 'required|string|unique:collections,collection_name',
            'fields' => 'required|array|min:1',
            'fields.*.name' => 'required|string|distinct',
            'fields.*.type' => 'required|string|in:string,integer,date,boolean',
            'fields.*.unique' => 'sometimes|boolean',
            'fields.*.nullable' => 'sometimes|boolean',
            'fields.*.default' => 'nullable',
        ]);
    
        $collectionName = $validated['collection_name'];
        $fields = $validated['fields'];
    
        // Add default fields to the fields array
        $defaultFields = [
            ['name' => 'code', 'type' => 'string', 'unique' => true],
            ['name' => 'deleted', 'type' => 'integer'],
            ['name' => 'created_at', 'type' => 'date'],
            ['name' => 'updated_at', 'type' => 'date'],
        ];
    
        // Merge the default fields with user-defined fields
        $fieldDefinitions = array_merge($fields, $defaultFields);
    
        try {
            // $mongoClient = DB::connection('mongodb')->getMongoClient();
            // $database = $mongoClient->selectDatabase('generic_data');
    
            // Create the collection in MongoDB
            $this->database->createCollection($collectionName);
    
            // Store the collection metadata using Eloquent
            CollectionMetadata::create([
                'collection_name' => $collectionName,
                'fields' => $fieldDefinitions,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            return redirect()->route('collections.index')->with('success', 'Collection created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create collection: ' . $e->getMessage());
        }
    }
    
    public function show(Request $request, $collectionName)
    {
        $showAll = $request->get('show_all', false);
        $perPage = $request->get('per_page', 25);
        $search = $request->get('search', '');
    
        // Retrieve collection metadata
        $collectionMetadata = CollectionMetadata::where('collection_name', $collectionName)->first();
        $headers = $collectionMetadata ? $collectionMetadata->fields : [];
    
        $filter = $showAll ? [] : ['deleted' => 0];
        $query = DB::connection('mongodb')->getCollection($collectionName)->find($filter)->toArray();
    
        if ($search) {
            // Perform case-insensitive search
            $query = array_filter($query, function($document) use ($search) {
                foreach ($document as $key => $value) {
                    // Convert both the document value and search term to lowercase for case-insensitive comparison
                    if (strpos(strtolower((string)$value), strtolower($search)) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }
    
        $total = count($query);
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $documents = array_slice($query, $offset, $perPage);
    
        $pagination = [
            'current_page' => $currentPage,
            'last_page' => ceil($total / $perPage),
            'per_page' => $perPage,
            'total' => $total,
        ];
    
        return view('collections.show', compact('collectionName', 'documents', 'showAll', 'total', 'perPage', 'search', 'pagination', 'headers'));
    }
    
    
    
    private function insertDataIntoCollection($collectionName, array $headers, array $rows)
    {
        $collectionMetadata = CollectionMetadata::where('collection_name', $collectionName)->first();
        $fieldDefinitions = $collectionMetadata ? $collectionMetadata->fields : [];
    
        $fieldTypes = [];
        $fieldConstraints = [];
        foreach ($fieldDefinitions as $field) {
            $fieldTypes[$field['name']] = $field['type'];
            $fieldConstraints[$field['name']] = [
                'unique' => $field['unique'] ?? false,
                'nullable' => $field['nullable'] ?? false,
                'default' => $field['default'] ?? null,
            ];
        }
    
        $insertData = [];
        foreach ($rows as $row) {
            $insertRow = array_combine($headers, $row);
            $insertRow['deleted'] = 0;
    
            // Handle created_at and updated_at fields
            $createdAt = Carbon::now();
            $updatedAt = Carbon::now();
            $insertRow['created_at'] = new UTCDateTime($createdAt);
            $insertRow['updated_at'] = new UTCDateTime($updatedAt);
    
            // Apply constraints and validations
            foreach ($insertRow as $field => $value) {
                if (isset($fieldConstraints[$field])) {
                    $constraints = $fieldConstraints[$field];
    
                    // Check for 'nullable' constraint
                    if ($value === null && !$constraints['nullable']) {
                        throw new \Exception("Field '$field' cannot be null.");
                    }
    
                    // Apply 'default' value if necessary
                    if ($value === null && $constraints['nullable'] && $constraints['default'] !== null) {
                        $insertRow[$field] = $constraints['default'];
                    }
    
                    // Check for 'unique' constraint
                    if ($constraints['unique']) {
                        $existing = DB::connection('mongodb')->getCollection($collectionName)->findOne([$field => $value]);
                        if ($existing) {
                            throw new \Exception("Field '$field' must be unique. The value '$value' already exists.");
                        }
                    }
                }
    
                // Type validation
                if (isset($fieldTypes[$field])) {
                    $expectedType = $fieldTypes[$field];
                    if ($expectedType == 'integer' && !is_numeric($value)) {
                        throw new \Exception("Field '$field' must be an integer.");
                    } elseif ($expectedType == 'boolean' && !is_bool($value)) {
                        throw new \Exception("Field '$field' must be a boolean.");
                    } elseif ($expectedType == 'date' && !$value instanceof UTCDateTime) {
                        throw new \Exception("Field '$field' must be a valid date.");
                    }
                }
            }
    
            $insertData[] = $insertRow;
        }
    
        // Insert data if there is any valid data
        if (!empty($insertData)) {
            DB::connection('mongodb')->getCollection($collectionName)->insertMany($insertData);
        }
    }
    
    // Bulk upload data
    public function bulkUpload(Request $request, $collectionName)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        $path = $request->file('file')->store('uploads');
        $data = Excel::toArray([], storage_path("app/{$path}"));
        $rows = $data[0];

        $headers = array_shift($rows); // Remove header row

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

    public function download($collectionName, $includeData = false)
    {
        // Fetch the collection metadata
        $collectionMetadata = DB::connection('mongodb')->getCollection('collection_metadata')
                                ->findOne(['collection_name' => $collectionName]);
    
        if (!$collectionMetadata) {
            return redirect()->back()->with('error', 'Collection metadata not found.');
        }
    
        // Convert BSONArray to a plain PHP array using iterator_to_array
        $fields = iterator_to_array($collectionMetadata['fields']);
    
        // Extract the headers from the metadata fields
        $headers = array_map(function ($field) {
            return $field['name']; // Extract field names
        }, $fields);
    
        // Exclude certain fields (e.g., _id, deleted, created_at, updated_at)
        $headers = array_filter($headers, fn($key) => !in_array($key, ['_id', 'deleted', 'created_at', 'updated_at']), ARRAY_FILTER_USE_KEY);
    
        // If including data, fetch documents from the specified collection
        if ($includeData) {
            $documents = DB::connection('mongodb')->getCollection($collectionName)
                            ->find(['deleted' => 0])
                            ->toArray();
    
            $excelData = [$headers]; // Start with headers as the first row
    
            foreach ($documents as $document) {
                $row = [];
                foreach ($headers as $header) {
                    // Add the document field value or empty if not set
                    // Make sure the field exists in the document
                    $row[] = $document[$header] ?? '';
                }
                $excelData[] = $row;
            }
        } else {
            // If not including data, just provide the headers
            $excelData = [$headers];
        }
    
        // Generate the Excel export and download it
        return Excel::download(new CollectionTemplateExport($excelData), 
            $collectionName . ($includeData ? '_data.xlsx' : '_template.xlsx'));
    }
    

    public function activityLogs(Request $request)
    {
        // Define the number of records per page (default is 25 if not specified)
        $perPage = $request->get('per_page', 25);
    
        // Get the logs with pagination
        $logs = ActivityLog::orderBy('timestamp', 'desc')->paginate($perPage);
    
        // Pass pagination data to the view
        return view('activity_logs.index', [
            'logs' => $logs,
            'pagination' => $logs->toArray(),  // Convert the pagination object to an array
            'perPage' => $perPage,
        ]);
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
                $suggestions[] = $collectionName;
            }
        }
    
        return response()->json($suggestions);
    }

    public function edit($collectionName, $id)
    {
        // Fetch the document to be edited
        $document = DB::connection('mongodb')->getCollection($collectionName)
                          ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'deleted' => 0]);
    
        // Fetch the field metadata for the collection
        $collectionMetadata = CollectionMetadata::where('collection_name', $collectionName)->first();
        if (!$collectionMetadata) {
            return redirect()->route('collections.show', $collectionName)->with('error', 'Collection metadata not found.');
        }
    
        $fieldDefinitions = $collectionMetadata->fields;
        $fieldTypes = [];
        foreach ($fieldDefinitions as $field) {
            $fieldTypes[$field['name']] = $field['type'];
        }
    
        return view('collections.edit', compact('collectionName', 'document', 'fieldTypes'));
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


    public function restore(Request $request, $collectionName, $id)
    {

        $documentBefore = DB::connection('mongodb')->getCollection($collectionName)
                            ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        
        DB::connection('mongodb')->getCollection($collectionName)
        ->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($id)],
            [
                '$set' => [
                    'deleted' => 0,  // Restore the document (set 'deleted' to 0)
                    'updated_at' => new UTCDateTime(Carbon::now()),  // Update the 'updated_at' timestamp
                ]
            ]
        );

        ActivityLogService::log('restore', $collectionName, $documentBefore);

        return redirect()->route('collections.show', $collectionName)->with('success', 'Document restored successfully.');
    }
}
