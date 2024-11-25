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


    /**
     * Display a paginated list of collections with optional search filtering.
     *
     * This function retrieves all collections from the 'generic_data' database, excluding those listed in
     * the `gdb_config.excluded_collections` configuration. It also allows for an optional search query 
     * that filters collections by name. The function then paginates the results and passes them to the view.
     *
     * @param Request $request The HTTP request containing parameters for search, pagination, etc.
     * 
     * @return \Illuminate\View\View The view displaying the paginated list of collections.
     * 
     * @throws \Illuminate\Database\QueryException If there is an issue with database operations.
     */
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

    
    /**
     * Show the form for creating a new collection.
     *
     * This function prepares and returns the view for creating a new collection. It retrieves a list of 
     * available languages from the `gdb_config.languages` configuration and passes it to the view to 
     * allow the user to select a language during the creation process.
     *
     * @return \Illuminate\View\View The view displaying the form for creating a new collection.
     */
    public function create()
    {
        $languages = config('gdb_config.languages', []);
        return view('collections.create', compact('languages'));
    }



    /**
     * Store a newly created collection in the database.
     *
     * This method processes the creation of a new collection. It first validates the incoming request data,
     * including the collection name and fields. The fields are processed and standardized (e.g., converting 
     * spaces to underscores and ensuring uniqueness). Default fields such as `code`, `is_active`, `is_deleted`, 
     * `translations`, and `system_info` are added to the collection. The collection is then created in MongoDB, 
     * and metadata for the collection is stored in the `CollectionMetadata` model. If the collection creation 
     * is successful, the user is redirected to the collection index with a success message. If there is an error, 
     * the user is redirected back with an error message.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing collection data.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects back to the collection index with a success or error message.
     */
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

        $fields = array_map(function ($field) {
            $field['name'] = strtolower(str_replace(' ', '_', $field['name']));
            return $field;
        }, $validated['fields']);
    
        $translations = $request->translations ?? []; 

        $defaultFields = [
            ['name' => 'code', 'type' => 'string', 'unique' => true],
            ['name' => 'is_active', 'type' => 'integer'],
            ['name' => 'is_deleted', 'type' => 'integer'],
        ];

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
    
        $fieldDefinitions = array_merge($fields, $defaultFields);
    
        try {

            $mongoClient = DB::connection('mongodb')->getMongoClient();
            $database = $mongoClient->selectDatabase('generic_data');
            $database->createCollection($collectionName);
    
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


    /**
     * Display the documents of a specific collection.
     *
     * This method retrieves and displays the documents of a specified collection from MongoDB. It allows the user
     * to filter and search through the documents based on the `search` query parameter. The user can also choose
     * to view all documents, including those marked as deleted, using the `show_all` parameter. Additionally, if the
     * `autoGenerateCode` checkbox is checked, the method automatically generates a unique `code` field for each document.
     * The results are paginated based on the `per_page` parameter, and the collection's metadata (fields) are used to 
     * format the document headers.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing the filtering and pagination data.
     * @param string $collectionName The name of the collection to display.
     *
     * @return \Illuminate\View\View The view displaying the collection's documents, pagination, and metadata.
     */
    public function show(Request $request, $collectionName)
    {
        $showAll = $request->get('show_all', false);
        $perPage = $request->get('per_page', config('gdb_config.default_per_page'));
        $search = $request->get('search', '');
        $autoGenerateCode = $request->has('autoGenerateCode'); // Check if the checkbox was checked
    
        $collectionMetadata = CollectionMetadata::where('collection_name', $collectionName)->first();
        $headers = $collectionMetadata ? $collectionMetadata->fields : [];
        $fields = $collectionMetadata ? $collectionMetadata->fields : [];
    
        $headers = array_map(function ($field) {
            return $field['name'];
        }, $headers);

        
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
    
    /**
     * Insert data into a specified MongoDB collection.
     *
     * This method processes the insertion of multiple rows of data into a MongoDB collection. It validates the fields 
     * based on the collection's metadata, including constraints like `unique`, `nullable`, and field types. The method 
     * also supports auto-generating a `code` field for the documents if the `autoGenerateCode` flag is set. Additionally,
     * it handles the creation of a `translations` field, moving language-specific fields (e.g., `lan_en`, `lan_fr`) into 
     * the `translations` object. System fields such as `is_active`, `is_deleted`, and `system_info` (timestamps and user info) 
     * are automatically added to each row. The rows are then inserted into the specified collection in MongoDB.
     *
     * @param string $collectionName The name of the MongoDB collection where the data should be inserted.
     * @param array $headers An array of field names (headers) for the data to be inserted.
     * @param array $rows An array of data rows, where each row is an array of field values corresponding to the headers.
     * @param bool $autoGenerateCode Flag to determine if the `code` field should be automatically generated.
     *
     * @return void This method does not return any value but performs the data insertion in the MongoDB collection.
     * 
     * @throws \Exception If any validation constraint is violated (e.g., unique, nullable, field type mismatch).
     */
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
            $existingCount = DB::connection('mongodb')->getCollection($collectionName)->count();
            $nextCode = $existingCount + 1; 
        }
        
        foreach ($rows as $row) {
            $insertRow = array_combine($headers, $row);
            $insertRow['is_active'] = 1;
            $insertRow['is_deleted'] = 0;
            
            if ($autoGenerateCode && !isset($insertRow['code'])) {
                $insertRow['code'] = str_pad((string) $nextCode, 4, '0', STR_PAD_LEFT);
                $nextCode++;
            }


            $collectionMetadata = DB::connection('mongodb')->getCollection('collection_metadata')
            ->findOne(['collection_name' => $collectionName]);

            $fieldsArray = iterator_to_array($collectionMetadata['fields']);

            if (in_array('translations', array_column($fieldsArray, 'name'))) {

                $translations = new \stdClass();

                $en = 'en';
                $translations->$en = $insertRow['prm'];
        
                foreach ($insertRow as $field => $value) {
                    if (strpos($field, 'lan_') === 0) {
                        $langCode = substr($field, 4); 
                        $translations->$langCode = $value;
                        unset($insertRow[$field]); 
                    }
                }
        
                if (!empty((array) $translations)) {
                    $insertRow['translations'] = $translations; 
                }
            }    
    
            $createdAt = Carbon::now();
            $createdBy = Auth::user()->email;
            $updatedAt = Carbon::now();
            $updatedBy = Auth::user()->email;
            $deletedAt = null;
            $deletedBy = null;
            
            $insertRow['system_info'] = (object)[
                'created_at' => new UTCDateTime($createdAt),
                'created_by' => $createdBy,
                'updated_at' => new UTCDateTime($updatedAt),
                'updated_by' => $updatedBy,
                'deleted_at' => $deletedAt,
                'deleted_by' => $deletedBy
            ];
    
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
                            throw new \Exception("Field '$field' must be unique. The value '$value' already exists in the database.");
                        }
            
                        foreach ($insertData as $existingRow) {
                            if (isset($existingRow[$field]) && $existingRow[$field] === $value) {
                                throw new \Exception("Field '$field' must be unique. The value '$value' already exists in the uploaded sheet.");
                            }
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
    
    /**
     * Handle bulk data upload and insertion into a specified collection.
     *
     * This method processes a bulk upload request, where an Excel or CSV file containing data is uploaded by the user.
     * The file is validated, and the headers are checked against the collection's metadata to ensure they match. If 
     * the headers are valid, the data is inserted into the MongoDB collection. The method also supports auto-generating 
     * a `code` field for the documents if the `autoGenerateCode` flag is set. Additionally, it handles the inclusion 
     * of language-specific translation fields by adding a prefix for each language (excluding 'en'). After the upload, 
     * any temporary files are deleted, and appropriate success or error messages are returned.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing the file and additional parameters.
     * @param string $collectionName The name of the MongoDB collection to which the data should be uploaded.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects back with a success or error message based on the outcome of the bulk upload.
     * 
     * @throws \Exception If the uploaded file's headers do not match the expected collection fields or if an error occurs during data insertion.
     */ 
    public function bulkUpload(Request $request, $collectionName)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);
    
        $autoGenerateCode = $request->has('autoGenerateCode');
    
        $path = $request->file('file')->store('uploads');
        $data = Excel::toArray([], storage_path("app/{$path}"));
        $rows = $data[0];
    
        $uploadedHeaders = array_shift($rows);
    
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
        $expectedHeaders = array_filter($expectedHeaders, fn($field) => !in_array($field, $excludedFields));
        $expectedHeaders = array_values($expectedHeaders); // Re-index the array

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
        $expectedHeaders = array_filter($expectedHeaders, fn($field) => $field !== 'translations');
        $expectedHeaders = array_values($expectedHeaders); // Re-index the array

        $uploadedHeaders = array_filter($uploadedHeaders, fn($field) => !in_array($field, $excludedFields));

        if ($uploadedHeaders !== $expectedHeaders) {
            return redirect()->back()->with('error', 'The uploaded sheet headers do not match the collection fields.');
        }
    
        try {
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
    
    
    /**
     * Download a template or data file for a specific collection.
     *
     * This method generates a downloadable Excel file for a specified collection in MongoDB. The file can either be 
     * a template containing only the field headers or a full file including both the headers and data. The method 
     * first validates the collection metadata and constructs the appropriate headers by extracting field names 
     * from the collection's metadata. It also handles special fields, such as the 'translations' field, by adding 
     * language-specific columns (e.g., `lan_{language_code}`). If the `includeData` flag is set to true, the method 
     * retrieves the collection's documents, excluding deleted records, and adds the data rows to the Excel file. 
     * The file is then returned as a download with a dynamic filename based on the collection name and whether 
     * data is included.
     *
     * @param string $collectionName The name of the MongoDB collection.
     * @param bool $includeData Flag to determine if data should be included in the export (defaults to false).
     *
     * @return \Maatwebsite\Excel\Excel The Excel file for download.
     * 
     * @throws \Exception If the collection metadata cannot be found in the database.
     */
    public function download($collectionName, $includeData = false)
    {
        $collectionMetadata = DB::connection('mongodb')->getCollection('collection_metadata')
                                    ->findOne(['collection_name' => $collectionName]);
        
        if (!$collectionMetadata) {
            return redirect()->back()->with('error', 'Collection metadata not found.');
        }
    
        // Convert BSONArray to a regular array
        $fields = iterator_to_array($collectionMetadata['fields']);
        
        $headers = array_map(function ($field) {
            return $field['name']; // Extract field names
        }, $fields);
    
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
                        $row[] = $document[$header] ?? ''; 
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
    
    
    /**
     * Display the activity logs for the application.
     *
     * This method retrieves the activity logs from the `ActivityLog` model and allows filtering by user. It uses 
     * pagination to limit the number of logs displayed per page, based on the `per_page` parameter in the request 
     * (with a default value from the configuration). If a specific user is selected, only logs related to that user 
     * are shown; otherwise, all logs are displayed. The method also fetches all users from the `User` model to populate 
     * a dropdown for user filtering. The logs are displayed in descending order based on the timestamp.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing the `per_page` and `user` filters.
     *
     * @return \Illuminate\View\View The view displaying the paginated activity logs, user filters, and pagination information.
     */
    public function activityLogs(Request $request)
    {
        $perPage = $request->get('per_page', config('gdb_config.default_per_page'));
    
        $selectedUser = $request->get('user');
        $query = ActivityLog::orderBy('timestamp', 'desc');
    
        if ($selectedUser) {
            $query->where('user', $selectedUser);
        }
    
        $logs = $query->paginate($perPage);
    
        $users = User::all();
    
        return view('activity_logs.index', [
            'logs' => $logs,
            'pagination' => $logs->toArray(),
            'perPage' => $perPage,
            'users' => $users,
            'selectedUser' => $selectedUser,
        ]);
    }
    
    /**
     * Suggest collections from the MongoDB database based on a search query.
     *
     * This method retrieves a list of collection names from the `generic_data` MongoDB database, excluding collections 
     * that are specified in the configuration's `excluded_collections`. It then filters the collections based on the 
     * provided `search` query. If the `search` is empty, all collections (except the excluded ones) are suggested. The 
     * collection names that match the search query (case-insensitive) are returned as suggestions in a JSON response.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing the search query.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing a list of suggested collection names.
     */
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

    /**
     * Display the form for editing a document in a specific collection.
     *
     * This method retrieves a document from the specified collection by its ID, excluding soft-deleted documents. 
     * It also fetches the metadata for the collection (field definitions) and prepares the necessary data to 
     * display an edit form. If the collection metadata is not found, the user is redirected back with an error message.
     *
     * @param string $collectionName The name of the collection where the document exists.
     * @param string $id The ID of the document to be edited.
     *
     * @return \Illuminate\View\View A view to display the document edit form.
     */
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
    
    /**
     * Update an existing document in a specific collection.
     *
     * This method updates an existing document in the specified collection by ID. The method first validates the 
     * provided data, ensuring that it is an array. It retrieves the existing document and its translations to preserve 
     * any unchanged translation fields. Then, it updates the document, including the necessary system fields for tracking 
     * updates (like `updated_at` and `updated_by`). After updating, the operation is logged and the user is redirected 
     * back with a success message.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing the data to update.
     * @param string $collectionName The name of the collection to update the document in.
     * @param string $id The ID of the document to update.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects back to the collection view with a success message.
     */
    public function update(Request $request, $collectionName, $id)
    {
        $request->validate([
            'data' => 'required|array',
        ]);
    
        $data = $request->input('data');
    
        $documentBefore = DB::connection('mongodb')->getCollection($collectionName)
            ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'is_deleted' => 0]);
    
        $collectionMetadata = DB::connection('mongodb')->getCollection('collection_metadata')
            ->findOne(['collection_name' => $collectionName]);
    
        $fieldsArray = iterator_to_array($collectionMetadata['fields']);
    
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
    
            $data['translations'] = $translations;
        }
    
        $data['system_info.updated_at'] = new \MongoDB\BSON\UTCDateTime(now());
        $data['system_info.updated_by'] = Auth::user()->email;
    
        DB::connection('mongodb')->getCollection($collectionName)
            ->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($id)],
                ['$set' => $data]
            );
    
        ActivityLogService::log('update', $collectionName, $documentBefore);
    
        return redirect()->route('collections.show', $collectionName)
            ->with('success', 'Document updated successfully.');
    }
    
    

    

    /**
     * Soft delete a document from a specific collection.
     *
     * This method performs a soft delete on a document in the specified collection by marking the `is_deleted` 
     * field as `1`. It also adds deletion metadata, including the `deleted_at` timestamp and the `deleted_by` 
     * user. If the document is successfully found and marked as deleted, an activity log is created to record 
     * the deletion action.
     *
     * @param string $collectionName The name of the collection to delete the document from.
     * @param string $id The ID of the document to delete.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects back to the collection view with a success message.
     */

    public function destroy($collectionName, $id)
    {
        $documentBefore = DB::connection('mongodb')->getCollection($collectionName)
            ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id), 'is_deleted' => 0]);

        if ($documentBefore) {
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

            ActivityLogService::log('delete', $collectionName, $documentBefore);
        }

        return redirect()->route('collections.show', $collectionName)->with('success', 'Document deleted successfully.');
    }

    /**
     * Restore a soft-deleted document from a specific collection.
     *
     * This method restores a soft-deleted document in the specified collection by setting the `is_deleted` 
     * field to `0` and resetting the `deleted_at` and `deleted_by` fields. If the document is successfully 
     * restored, an activity log is created to record the restoration action.
     *
     * @param \Illuminate\Http\Request $request The request object.
     * @param string $collectionName The name of the collection where the document is located.
     * @param string $id The ID of the document to restore.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects back to the collection view with a success message.
     */
    public function restore(Request $request, $collectionName, $id)
    {
        $documentBefore = DB::connection('mongodb')->getCollection($collectionName)
            ->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

        if ($documentBefore) {
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

            ActivityLogService::log('restore', $collectionName, $documentBefore);
        }

        return redirect()->route('collections.show', $collectionName)->with('success', 'Document restored successfully.');
    }

}
