<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;

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
    
    $mongoClient = DB::connection('mongodb')->getMongoClient();
    $database = $mongoClient->selectDatabase('generic_data');
    $collections = $database->listCollections();

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
        $languages = config('gdb_config.languages', []);
        return view('collections.create', compact('languages'));
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
    
        $translations = $request->translations ?? []; 

        // Add default fields to the fields array
        $defaultFields = [
            ['name' => 'code', 'type' => 'string', 'unique' => true],
            ['name' => 'is_active', 'type' => 'integer'],
            ['name' => 'is_deleted', 'type' => 'integer'],
            
            // Add translations as fields dynamically (only if translations exist)
            [
                'name' => 'translations',
                'type' => 'array',
                'fields' => array_map(function($translation) {
                    return ['name' => $translation, 'type' => 'string'];
                }, $translations)
            ],
            
            [
                'name' => 'system_info',
                'type' => 'array',
                'fields' => [
                    ['name' => 'created_at', 'type' => 'date'],
                    ['name' => 'created_by', 'type' => 'string'],
                    ['name' => 'updated_at', 'type' => 'date'],
                    ['name' => 'updated_by', 'type' => 'string'],
                    ['name' => 'deleted_at', 'type' => 'date'],
                    ['name' => 'deleted_by', 'type' => 'string']
                ]
            ]
        ];
    
        // Merge the default fields with user-defined fields
        $fieldDefinitions = array_merge($fields, $defaultFields);
    
        try {
            // Connect to MongoDB
            $mongoClient = DB::connection('mongodb')->getMongoClient();
            $database = $mongoClient->selectDatabase('generic_data');
            $database->createCollection($collectionName);
    
            // Store metadata in the database
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
        $perPage = $request->get('per_page', config('gdb_config.default_per_page'));
        $search = $request->get('search', '');
        $autoGenerateCode = $request->has('autoGenerateCode'); // Check if the checkbox was checked
    
        $collectionMetadata = CollectionMetadata::where('collection_name', $collectionName)->first();
        $headers = $collectionMetadata ? $collectionMetadata->fields : [];
    
        $filter = $showAll ? [] : ['deleted' => 0];
        $query = DB::connection('mongodb')->getCollection($collectionName)->find($filter)->toArray();
    
        if ($search) {
            $query = array_filter($query, function($document) use ($search) {
                foreach ($document as $key => $value) {
                    if (strpos(strtolower((string)$value), strtolower($search)) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }
    
        if ($autoGenerateCode) {
            $nextCode = count($query) + 1; 
            foreach ($query as &$document) {
                if (!isset($document['code'])) {
                    $document['code'] = (string)$nextCode;
                    $nextCode++;
                }
            }
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
    
    
    
    
    private function insertDataIntoCollection($collectionName, array $headers, array $rows, $autoGenerateCode = false)
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
        $nextCode = 0;
        
        if ($autoGenerateCode) {
            // Get the current count of documents in the collection to set the starting point for code generation
            $existingCount = DB::connection('mongodb')->getCollection($collectionName)->count();
            $nextCode = $existingCount + 1; // Start from the length of the collection
        }
        
        foreach ($rows as $row) {
            $insertRow = array_combine($headers, $row);
            $insertRow['deleted'] = 0;
            
            // Handle Auto Generate Code
            if ($autoGenerateCode && !isset($insertRow['code'])) {
                $insertRow['code'] = (string) $nextCode; // Assign the generated code
                $nextCode++; // Increment the code for the next entry
            }
    
            // Handle timestamps
            $createdAt = Carbon::now();
            $updatedAt = Carbon::now();
            $insertRow['created_at'] = new UTCDateTime($createdAt);
            $insertRow['updated_at'] = new UTCDateTime($updatedAt);
            
            // Validate fields based on constraints
            foreach ($insertRow as $field => $value) {
                if (isset($fieldConstraints[$field])) {
                    $constraints = $fieldConstraints[$field];
        
                    if ($value === null && !$constraints['nullable']) {
                        throw new \Exception("Field '$field' cannot be null.");
                    }
        
                    if ($value === null && $constraints['nullable'] && $constraints['default'] !== null) {
                        $insertRow[$field] = $constraints['default'];
                    }
        
                    if ($constraints['unique']) {
                        $existing = DB::connection('mongodb')->getCollection($collectionName)->findOne([$field => $value]);
                        if ($existing) {
                            throw new \Exception("Field '$field' must be unique. The value '$value' already exists.");
                        }
                    }
                }
        
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
        
        if (!empty($insertData)) {
            DB::connection('mongodb')->getCollection($collectionName)->insertMany($insertData);
        }
    }
    
    
    public function bulkUpload(Request $request, $collectionName)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);
    
        // Check if the auto-generate code checkbox is selected
        $autoGenerateCode = $request->has('autoGenerateCode');
    
        $path = $request->file('file')->store('uploads');
        $data = Excel::toArray([], storage_path("app/{$path}"));
        $rows = $data[0];
    
        $uploadedHeaders = array_shift($rows);
    
        // Get collection metadata for header validation
        $collectionMetadata = DB::connection('mongodb')->getCollection('collection_metadata')
            ->findOne(['collection_name' => $collectionName]);
    
        if (!$collectionMetadata) {
            return redirect()->back()->with('error', 'Collection metadata not found.');
        }
    
        $fields = iterator_to_array($collectionMetadata['fields']);
        $expectedHeaders = array_map(function ($field) {
            return $field['name']; // Extract field names
        }, $fields);
        
        $excludedFields = config('gdb_config.excluded_columns');
        $uploadedHeaders = array_filter($uploadedHeaders, fn($field) => !in_array($field, $excludedFields));
        $expectedHeaders = array_filter($expectedHeaders, fn($field) => !in_array($field, $excludedFields));

        // Validate headers
        if ($uploadedHeaders !== $expectedHeaders) {
            return redirect()->back()->with('error', 'The uploaded sheet headers do not match the collection fields.');
        }
    
        try {
            // Pass the autoGenerateCode flag to the method
            $this->insertDataIntoCollection($collectionName, $uploadedHeaders, $rows, $autoGenerateCode);
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
        $collectionMetadata = DB::connection('mongodb')->getCollection('collection_metadata')
                                ->findOne(['collection_name' => $collectionName]);
    
        if (!$collectionMetadata) {
            return redirect()->back()->with('error', 'Collection metadata not found.');
        }
    
        $fields = iterator_to_array($collectionMetadata['fields']);
    
        $headers = array_map(function ($field) {
            return $field['name']; // Extract field names
        }, $fields);
    
        $excludedFields = config('gdb_config.excluded_columns');
        $headers = array_filter($headers, fn($field) => !in_array($field, $excludedFields));
        
        $headers = array_values($headers);
        
    
        if ($includeData) {
            $documents = DB::connection('mongodb')->getCollection($collectionName)
                            ->find(['deleted' => 0])
                            ->toArray();
    
            $excelData = [$headers]; // Start with headers as the first row
    
            foreach ($documents as $document) {
                $row = [];
                foreach ($headers as $header) {
                    $row[] = $document[$header] ?? '';
                }
                $excelData[] = $row;
            }
        } else {
            $excelData = [$headers];
        }
            return Excel::download(new CollectionTemplateExport($excelData), 
            $collectionName . ($includeData ? '_data.xlsx' : '_template.xlsx'));
    }
    

    public function activityLogs(Request $request)
    {
        $perPage = $request->get('per_page', config('gdb_config.default_per_page'));
    
        // Get selected user, if any; show all logs if none is selected
        $selectedUser = $request->get('user');
        $query = ActivityLog::orderBy('timestamp', 'desc');
    
        if ($selectedUser) {
            $query->where('user', $selectedUser);
        }
    
        $logs = $query->paginate($perPage);
    
        // Fetch all users for the dropdown (assuming User model is available)
        $users = User::all();
    
        return view('activity_logs.index', [
            'logs' => $logs,
            'pagination' => $logs->toArray(),
            'perPage' => $perPage,
            'users' => $users,
            'selectedUser' => $selectedUser,
        ]);
    }
    
    
    public function suggestCollections(Request $request)
    {
        $search = $request->get('search', '');
        $mongoClient = DB::connection('mongodb')->getMongoClient();
        $database = $mongoClient->selectDatabase('generic_data');
        $collections = $database->listCollections();
    
        $excludedCollections = config('gdb_config.excluded_collections');

    
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
        $document = DB::connection('mongodb')->getCollection($collectionName)
                          ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'deleted' => 0]);
    
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
                    'deleted' => 0, 
                    'updated_at' => new UTCDateTime(Carbon::now()), 
                ]
            ]
        );

        ActivityLogService::log('restore', $collectionName, $documentBefore);

        return redirect()->route('collections.show', $collectionName)->with('success', 'Document restored successfully.');
    }
}
