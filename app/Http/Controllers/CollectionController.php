<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


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
                            ->countDocuments(['is_deleted' => 0]);

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

        $collectionName = strtolower(str_replace(' ', '_', $validated['collection_name']));
        $fields = $validated['fields'];
    
        $translations = $request->translations ?? []; 

        // Add default fields to the fields array
        $defaultFields = [
            ['name' => 'code', 'type' => 'string', 'unique' => true],
            ['name' => 'is_active', 'type' => 'integer'],
            ['name' => 'is_deleted', 'type' => 'integer'],

            // Add translations only if $translations is not empty
            // Assuming $translations is an array like ['en', 'bn', 'ms']
        ];

        // Only add translations field if translations array is not empty
        if (!empty($translations)) {
            $defaultFields[] = [
                'name' => 'translations',
                'type' => 'array',
                'fields' => array_map(function($translation) {
                    return ['name' => $translation, 'type' => 'string'];
                }, $translations)
            ];
        }

        $defaultFields[] = [
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
        $fields = $collectionMetadata ? $collectionMetadata->fields : [];
    
        // Extract the 'name' from each header field and exclude 'system_info' and 'is_deleted'
        $headers = array_map(function ($field) {
            return $field['name'];
        }, $headers);

        
        // Remove 'system_info' and 'is_deleted' from the headers
        $headers = array_filter($headers, function ($header) {
            return !in_array($header, ['system_info', 'is_deleted']);
        });

        $headers = array_values($headers);
        
        $filter = $showAll ? [] : ['is_deleted' => 0];
        $query = DB::connection('mongodb')->getCollection($collectionName)->find($filter)->toArray();
        
        if ($search) {
            $query = array_filter($query, function ($document) use ($search) {
                foreach ($document as $key => $value) {
                    // Convert non-string types to a string for comparison
                    if (is_array($value) || $value instanceof \MongoDB\Model\BSONDocument) {
                        $value = json_encode($value); // Serialize complex types
                    }
                    if (is_string($value) && strpos(strtolower($value), strtolower($search)) !== false) {
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
    
        // Remove unwanted fields (e.g., system_info, is_deleted) from the documents
        $documents = array_map(function($document) {
            unset($document['system_info']);
            return $document;
        }, $query);


        
        $total = count($documents);
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $documents = array_slice($documents, $offset, $perPage);
    
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
            $insertRow['is_active'] = 1;
            $insertRow['is_deleted'] = 0;
            
            // Handle Auto Generate Code
            if ($autoGenerateCode && !isset($insertRow['code'])) {
                $insertRow['code'] = str_pad((string) $nextCode, 4, '0', STR_PAD_LEFT);// Assign the generated code
                $nextCode++; // Increment the code for the next entry
            }


            $collectionMetadata = DB::connection('mongodb')->getCollection('collection_metadata')
            ->findOne(['collection_name' => $collectionName]);

            $fieldsArray = iterator_to_array($collectionMetadata['fields']);

            // Check if 'translations' exists in the field names
            if (in_array('translations', array_column($fieldsArray, 'name'))) {

                // Create the translations field as an object (not an array)
                $translations = new \stdClass();

                $en = 'en';
                $translations->$en = $insertRow['prm'];
        
                // Loop through the insertRow and identify 'lan_' prefixed fields
                foreach ($insertRow as $field => $value) {
                    // If the field starts with 'lan_' we will move it to the translations object
                    if (strpos($field, 'lan_') === 0) {
                        $langCode = substr($field, 4); // Remove 'lan_' prefix
                        $translations->$langCode = $value;
                        unset($insertRow[$field]); // Remove the original 'lan_' field
                    }
                }
                
        
                // Add the translations object to the row if it has any data
                if (!empty((array) $translations)) {
                    $insertRow['translations'] = $translations; // Store translations as an object
                }
            }    
    
            // Handle timestamps
            $createdAt = Carbon::now();
            $createdBy = Auth::user()->email;
            $updatedAt = Carbon::now();
            $updatedBy = Auth::user()->email;
            $deletedAt = null;
            $deletedBy = null;
            
            // Manually add system_info field with the necessary information
            $insertRow['system_info'] = (object)[
                'created_at' => new UTCDateTime($createdAt),
                'created_by' => $createdBy,
                'updated_at' => new UTCDateTime($updatedAt),
                'updated_by' => $updatedBy,
                'deleted_at' => $deletedAt,
                'deleted_by' => $deletedBy
            ];
    
            // Validate fields based on constraints
            foreach ($insertRow as $field => $value) {
                if (isset($fieldConstraints[$field])) {
                    $constraints = $fieldConstraints[$field];
            
                    // Check for nullable constraint
                    if ($value === null && !$constraints['nullable']) {
                        throw new \Exception("Field '$field' cannot be null.");
                    }
            
                    // Apply default value if nullable and value is null
                    if ($value === null && $constraints['nullable'] && $constraints['default'] !== null) {
                        $insertRow[$field] = $constraints['default'];
                    }
            
                    // Check for unique constraint
                    if ($constraints['unique']) {
                        // Check for duplicates in the existing database
                        $existing = DB::connection('mongodb')->getCollection($collectionName)->findOne([$field => $value]);
                        if ($existing) {
                            throw new \Exception("Field '$field' must be unique. The value '$value' already exists in the database.");
                        }
            
                        // Check for duplicates within the current insert data
                        foreach ($insertData as $existingRow) {
                            if (isset($existingRow[$field]) && $existingRow[$field] === $value) {
                                throw new \Exception("Field '$field' must be unique. The value '$value' already exists in the uploaded sheet.");
                            }
                        }
                    }
                }
            
                // Validate field type
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
            
            $insertData[] = $insertRow; // Add the validated row to the insert data
            
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
    
        // Extract fields from collection metadata
        $fields = iterator_to_array($collectionMetadata['fields']);

        // Build the expectedHeaders array from field names
        $expectedHeaders = array_map(function ($field) {
            return $field['name']; // Extract field names
        }, $fields);

        // Exclude fields specified in the configuration
        $excludedFields = config('gdb_config.excluded_columns');
        $expectedHeaders = array_filter($expectedHeaders, fn($field) => !in_array($field, $excludedFields));
        $expectedHeaders = array_values($expectedHeaders); // Re-index the array

        // Handle translations field and add lan_ prefix for each language, excluding 'en'
        $translations = array_filter($fields, fn($field) => $field['name'] == 'translations');

        foreach ($translations as $translationField) {
            if (isset($translationField['fields'])) {
                foreach ($translationField['fields'] as $language) {
                    if ($language['name'] !== 'en') {
                        $expectedHeaders[] = 'lan_' . $language['name']; // Add prefix for each language
                    }
                }
            }
        }

        // Remove the original 'translations' field from expectedHeaders
        $expectedHeaders = array_filter($expectedHeaders, fn($field) => $field !== 'translations');
        $expectedHeaders = array_values($expectedHeaders); // Re-index the array

        // The $expectedHeaders array now contains the processed headers

        $uploadedHeaders = array_filter($uploadedHeaders, fn($field) => !in_array($field, $excludedFields));


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
    
        // Convert BSONArray to a regular array
        $fields = iterator_to_array($collectionMetadata['fields']);
        
        // Extract field names for the headers
        $headers = array_map(function ($field) {
            return $field['name']; // Extract field names
        }, $fields);
    
        // Exclude any fields specified in the config
        $excludedFields = config('gdb_config.excluded_columns');
        $headers = array_filter($headers, fn($field) => !in_array($field, $excludedFields));
        
        $headers = array_values($headers);
        
        // Handle translations field and add lan_ prefix, remove original 'translation' field
        $translations = array_filter($fields, fn($field) => $field['name'] == 'translations');
        
        foreach ($translations as $translationField) {
            if (isset($translationField['fields'])) {
                foreach ($translationField['fields'] as $language) {
                    if($language['name'] !=='en'){
                        $headers[] = 'lan_' . $language['name']; // Add prefix for each language
                    }

                }
            }
        }
    
        // Remove the original 'translation' field from headers
        $headers = array_filter($headers, fn($field) => $field !== 'translations');
        $headers = array_values($headers); // Re-index the array
    
        if ($includeData) {
            $documents = DB::connection('mongodb')->getCollection($collectionName)
                            ->find(['is_deleted' => 0])
                            ->toArray();
    
            $excelData = [$headers]; // Start with headers as the first row
    
            foreach ($documents as $document) {
                $row = [];
                foreach ($headers as $header) {
                    if (strpos($header, 'lan_') === 0) {
                        $langCode = substr($header, 4); // Extract language code from the prefix
                        $row[] = $document['translations'][$langCode] ?? ''; // Get translation value for the language
                    } else {
                        $row[] = $document[$header] ?? ''; // Standard field value
                    }
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
                          ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'is_deleted' => 0]);
    
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
        // Validate that data is provided as an array
        $request->validate([
            'data' => 'required|array',
        ]);
    
        $data = $request->input('data');
    
        // Retrieve the existing document
        $documentBefore = DB::connection('mongodb')->getCollection($collectionName)
            ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'is_deleted' => 0]);
    
        // Extract existing translations to preserve unchanged ones
        $collectionMetadata = DB::connection('mongodb')->getCollection('collection_metadata')
            ->findOne(['collection_name' => $collectionName]);
    
        $fieldsArray = iterator_to_array($collectionMetadata['fields']);
    
        // Check if 'translations' exists in the field names
        if (in_array('translations', array_column($fieldsArray, 'name'))) {
            $existingTranslations = $documentBefore['translations'] ?? [];
    
            // Extract translations from lan_* fields
            $translations = $existingTranslations; // Start with existing translations
    
            foreach ($data as $key => $value) {
                if (str_starts_with($key, 'lan_')) {
                    $langCode = substr($key, 4); // Remove 'lan_' prefix
                    $translations[$langCode] = $value; // Add/overwrite the translation
                    unset($data[$key]); // Remove the lan_* field from main data
                }
            }
    
            // Add translations back into the data array
            $data['translations'] = $translations;
        }
    
        // Add system_info fields for updated_at and updated_by
        $data['system_info.updated_at'] = new \MongoDB\BSON\UTCDateTime(now());
        $data['system_info.updated_by'] = Auth::user()->email;
    
        // Perform the update operation
        DB::connection('mongodb')->getCollection($collectionName)
            ->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($id)],
                ['$set' => $data]
            );
    
        // Log the update operation
        ActivityLogService::log('update', $collectionName, $documentBefore);
    
        // Redirect back with success message
        return redirect()->route('collections.show', $collectionName)
            ->with('success', 'Document updated successfully.');
    }
    
    

    

    // destroy function
    public function destroy($collectionName, $id)
    {
        // Retrieve the document before making changes
        $documentBefore = DB::connection('mongodb')->getCollection($collectionName)
            ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'is_deleted' => 0]);

        if ($documentBefore) {
            // Update the document to mark it as deleted, and add deletion metadata
            DB::connection('mongodb')->getCollection($collectionName)
                ->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($id)],
                    [
                        '$set' => [
                            'is_deleted' => 1,
                            'system_info.deleted_at' => new \MongoDB\BSON\UTCDateTime(now()),
                            'system_info.deleted_by' => Auth::user()->email,
                        ]
                    ]
                );

            // Log the delete activity
            ActivityLogService::log('delete', $collectionName, $documentBefore);
        }

        return redirect()->route('collections.show', $collectionName)->with('success', 'Document deleted successfully.');
    }

    // restore function
    public function restore(Request $request, $collectionName, $id)
    {
        // Retrieve the document before making changes
        $documentBefore = DB::connection('mongodb')->getCollection($collectionName)
            ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

        if ($documentBefore) {
            // Update the document to mark it as restored, and reset deletion metadata
            DB::connection('mongodb')->getCollection($collectionName)
                ->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($id)],
                    [
                        '$set' => [
                            'is_deleted' => 0,
                            'system_info.deleted_at' => null,
                            'system_info.deleted_by' => null,
                        ]
                    ]
                );

            // Log the restore activity
            ActivityLogService::log('restore', $collectionName, $documentBefore);
        }

        return redirect()->route('collections.show', $collectionName)->with('success', 'Document restored successfully.');
    }

}
