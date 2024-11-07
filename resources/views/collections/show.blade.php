@extends('layouts.app')

@section('content')
    <h1>Collection: {{ $collectionName }}</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-3">
        <!-- Left side: Upload field and button -->
        <div class="col-md-6">
            <form id="uploadForm" action="{{ route('upload.data', $collectionName) }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center">
                @csrf
                <input type="file" name="file" required class="form-control me-3" style="width: auto;">
                <button type="submit" id="uploadButton" class="btn btn-success">Upload</button>
            </form>
        </div>

        <!-- Right side: Download buttons -->
        <div class="col-md-6 d-flex justify-content-end align-items-center">
            <span class="me-2">Download:</span>
            <a href="{{ route('collections.downloadTemplate', $collectionName) }}" class="btn btn-primary me-3">
                Template
            </a>
            <a href="{{ route('collections.downloadData', $collectionName) }}" class="btn btn-secondary">
                Data
            </a>
        </div>
    </div>

    <!-- Checkbox for showing all entries -->
    <div class="d-flex justify-content-end mb-3">
        <label class="form-check-label me-2">
            <input type="checkbox" id="showAllCheckbox" {{ $showAll ? 'checked' : '' }} class="form-check-input"> Show All Entries
        </label>
    </div>

    <!-- Search form -->
    <div class="col-md-5 p-0">
        <form method="GET" action="{{ route('collections.show', $collectionName) }}" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" value="{{ $search }}" class="form-control me-2" placeholder="Search..." style="width: 50%;">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>#</th> <!-- Index column -->

                @if (isset($headers))
                    @foreach ($headers as $field)
                        @if ($field['name'] !== 'created_at' && $field['name'] !== 'updated_at') <!-- Exclude created_at and updated_at -->
                            <th>{{ $field['name'] }}</th>
                        @endif
                    @endforeach
                @endif

                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($documents as $index => $document)
                <tr>
                    <td>{{ ($pagination['current_page'] - 1) * $pagination['per_page'] + $index + 1 }}</td> <!-- Index value -->

                    @foreach ($document->getArrayCopy() as $key => $value)
                        @if ($key !== '_id' && $key !== 'created_at' && $key !== 'updated_at') <!-- Exclude created_at and updated_at -->
                            <td>
                                @if (is_array($value) || is_object($value))
                                    <!-- If the value is an array or object, convert it to JSON string -->
                                    {{ json_encode($value) }}
                                @else
                                    <!-- Otherwise, just print the value -->
                                    {{ $value }}
                                @endif
                            </td>
                        @endif
                    @endforeach

                    <td>
                        <form method="POST" action="{{ route('collections.destroy', [$collectionName, $document['_id']]) }}" onsubmit="return confirm('Are you sure you want to delete this item?');" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        <a href="{{ route('collections.edit', [$collectionName, $document['_id']]) }}" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination controls -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            Showing {{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }} to {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }} of {{ $pagination['total'] }} entries
        </div>
        <div>
            @if ($pagination['current_page'] > 1)
                <a href="{{ route('collections.show', [$collectionName, 'page' => $pagination['current_page'] - 1, 'search' => $search, 'per_page' => $perPage]) }}" class="btn btn-secondary">Previous</a>
            @endif
            @if ($pagination['current_page'] < $pagination['last_page'])
                <a href="{{ route('collections.show', [$collectionName, 'page' => $pagination['current_page'] + 1, 'search' => $search, 'per_page' => $perPage]) }}" class="btn btn-secondary">Next</a>
            @endif
        </div>
    </div>

    <!-- Per-page dropdown -->
    <div class="d-flex justify-content-end mb-3">
        <form method="GET" action="{{ route('collections.show', $collectionName) }}" class="form-inline">
            <label for="perPage" class="me-2">Items per page:</label>
            <select name="per_page" class="form-select me-2" id="perPage">
                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
            </select>
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="page" value="{{ $pagination['current_page'] }}">
            <button type="submit" class="btn btn-primary">Set</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        @if (session('error'))
            Swal.fire({
                title: 'Error!',
                html: {!! json_encode(htmlspecialchars_decode(session('error'))) !!},
                icon: 'error',
                confirmButtonText: 'Close'
            });
        @endif

        document.getElementById("showAllCheckbox").addEventListener("change", function() {
            const showAll = this.checked;

            // Redirect to the show route with the show_all parameter
            window.location.href = "{{ route('collections.show', $collectionName) }}?show_all=" + (showAll ? '1' : '0') + "&search={{ $search }}&per_page={{ $perPage }}";
        });
    </script>
@endsection
