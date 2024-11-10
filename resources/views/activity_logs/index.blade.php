@extends('layouts.app')

@section('content')
    <h1>Activity Logs</h1>

    <div class="activity-log-container mt-4 w-80">

        <table class="activity-log-table">
            <thead>
                <tr>
                    <th class="activity-log-header">User</th>
                    <th class="activity-log-header">Action</th>
                    <th class="activity-log-header">Collection Name</th>
                    <th class="activity-log-header">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr class="activity-log-row" data-log="{{ json_encode($log->document_before) }}">
                        <td class="activity-log-user">
                            <span class="activity-log-arrow" style="cursor: pointer;">▼</span> <!-- Arrow indicator -->
                            {{ $log->user }}
                        </td>
                        <td class="activity-log-action">{{ $log->action }}</td>
                        <td class="activity-log-collection">{{ $log->collection_name }}</td>
                        <td class="activity-log-timestamp">{{ $log->timestamp }}</td>
                    </tr>
                    <tr class="activity-log-collapse-row" style="display: none;">
                        <td colspan="4">
                            <div class="activity-log-card">
                                <div class="activity-log-card-body">
                                    <pre id="documentBefore-{{ $loop->index }}" class="activity-log-document-before"></pre>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="collection-pagination">
            <!-- Left side: Showing X to Y of Z entries -->
            <div class="pagination-info">
                Showing {{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }} to 
                {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }} of {{ $pagination['total'] }} entries
            </div>

            <!-- Center: Pagination links (Previous/Next) -->
            <div class="pagination-controls">
                @if ($pagination['current_page'] > 1)
                    <a href="{{ route('activity.logs', ['page' => $pagination['current_page'] - 1, 'per_page' => $perPage]) }}" class="btn btn-sm pagination-btn">Previous</a>
                @endif
                @if ($pagination['current_page'] < $pagination['last_page'])
                    <a href="{{ route('activity.logs', ['page' => $pagination['current_page'] + 1, 'per_page' => $perPage]) }}" class="btn btn-sm pagination-btn">Next</a>
                @endif
            </div>

            <!-- Right side: Entries per page -->
            <div class="per-page-form">
                <label for="perPage">Entries</label>
                <select name="perPage" id="perPage" class="form-control form-control-sm" onchange="window.location.href='{{ route('activity.logs', ['page' => 1]) }}&per_page=' + this.value;">
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>

    </div>

@endsection