@extends('layouts.app')

@section('content')
    <!-- First Card: Collection name, upload, and download -->
    <div class="collection-card">
        <h1 class="collection-header">Collection: {{ $collectionName }}</h1>

            @if (session('success'))
                <div class="collection-alert">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="collection-warning">{{ session('warning') }}</div>
            @endif
            @if (session('error'))
                <div class="collection-danger">{{ session('error') }}</div>
            @endif

        <div class="flex-container">
            <!-- Upload form -->
            <form id="uploadForm" action="{{ route('collection.upload', $collectionName) }}" method="POST" enctype="multipart/form-data" class="collection-upload-form">
                @csrf
                <input type="file" name="file" required>
                <div>
                    <label for="autoGenerateCode">Auto Generate Code</label>
                    <input type="checkbox" name="autoGenerateCode" id="autoGenerateCode">
                </div>
                <button type="submit" id="uploadButton" class="btn gdb-button">Upload</button>
            </form>

            <!-- Download buttons -->
            <div class="collection-download-buttons">
                <span>Download</span>
                <a href="{{ route('collections.downloadTemplate', $collectionName) }}" class="download-btn">Template</a>
                <a href="{{ route('collections.downloadData', $collectionName) }}" class="download-btn">Data</a>
            </div>
        </div>
    </div>

    <!-- Second Card: Show All Entries, Search Form, and Table -->
    <div class="collection-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <!-- Left side: Show All Entries -->
            <div class="show-all-container">
                <label class="form-check-label">
                    <input type="checkbox" id="showAllCheckbox" {{ $showAll ? 'checked' : '' }} class="form-check-input"> Show All Entries
                </label>
            </div>

            <!-- Right side: Search Form -->
            <div class="collection-search-form">
                <form method="GET" action="{{ route('collections.show', $collectionName) }}" class="d-flex align-items-center">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm me-2" placeholder="Search...">
                    <button type="submit" class="btn gdb-button">Search</button>
                </form>
            </div>
        </div>

        <!-- Table -->
        <table class="collection-table">
            <thead>
                <tr>
                    <th>#</th>
                    @if (isset($headers))
                        @foreach ($headers as $field)
                                <th>{{ $field }}</th>
                        @endforeach
                    @endif
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @if(count($documents) > 0)
                    @foreach ($documents as $index => $document)
                        <tr>
                            <td>{{ ($pagination['current_page'] - 1) * $pagination['per_page'] + $index + 1 }}</td>

                            @foreach ($document as $key => $value)
                                @if ($key == 'translations')
                                        @foreach ($value as $translation)
                                            <td>
                                                {{ $translation }}
                                            </td>
                                        @endforeach
                                   
                                @elseif($key != '_id')
                                    <td>
                                        @if (is_array($value) || is_object($value))
                                            {{ json_encode($value) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endif
                            @endforeach


                            <td class="actions" style="text-align: center;">
                                @if (isset($document['is_deleted']) && $document['is_deleted'] == 1)
                                    <!-- Restore button (for deleted entries) -->
                                    <form method="POST" action="{{ route('collections.restore', [$collectionName, $document['_id']]) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Restore</button>
                                    </form>
                                @else
                                    <!-- Regular delete and edit actions -->
                                    <form method="POST" action="{{ route('collections.destroy', [$collectionName, $document['_id']]) }}" onsubmit="return confirm('Are you sure you want to delete this item?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                    <a href="{{ route('collections.edit', [$collectionName, $document['_id']]) }}" class="btn btn-sm btn-warning">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <!-- Display message if no data is found -->
                    <tr>
                        <td colspan="{{ count($headers) + 2}}" class="no-data-found" style="text-align: center;">
                            No data found.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="collection-pagination">
            <!-- Left side: Showing X to Y of Z entries -->
            <div class="pagination-info">
                Showing {{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }} to {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }} of {{ $pagination['total'] }} entries
            </div>

            <!-- Center: Pagination links (Previous/Next) -->
            <div class="pagination-controls">
                @if ($pagination['current_page'] > 1)
                    <a href="{{ route('collections.show', [$collectionName, 'page' => $pagination['current_page'] - 1, 'search' => $search, 'per_page' => $perPage]) }}" class="btn btn-sm pagination-btn">Previous</a>
                @endif
                @if ($pagination['current_page'] < $pagination['last_page'])
                    <a href="{{ route('collections.show', [$collectionName, 'page' => $pagination['current_page'] + 1, 'search' => $search, 'per_page' => $perPage]) }}" class="btn btn-sm pagination-btn">Next</a>
                @endif
            </div>

            <!-- Right side: Entries per page -->
            <div class="per-page-form">
                <label for="perPage">Entries</label>
                <select name="perPage" id="perPage" class="form-control form-control-sm" onchange="window.location.href='{{ route('collections.show', [$collectionName, 'search' => $search, 'page' => 1]) }}&per_page=' + this.value;">
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
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
            window.location.href = "{{ route('collections.show', $collectionName) }}?show_all=" + (showAll ? '1' : '0') + "&search={{ $search }}&per_page={{ $perPage }}";
        });
    </script>
@endsection
