@extends('layouts.app')

@section('content')
    <h1>Activity Logs</h1>

<!-- Updated User Filter Dropdown Section -->
@if (Auth::user() && Auth::user()->is_admin == '1')
    <div class="activity-log-filter-container mt-4">
        <form method="GET" action="{{ route('activity.logs') }}">
            <label for="user" class="activity-log-label">Select User:</label>
            <select name="user" id="user" class="activity-log-select">
                <option value="">View All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->email }}" {{ $selectedUser == $user->email ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
            <button type="submit" class="activity-log-filter-btn">Filter</button>
        </form>
    </div>
@endif


    <div class="activity-log-container w-80">
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

        @if($pagination['total'] > 0)
        <!-- Pagination Controls -->
        <div class="collection-pagination">
            <div class="pagination-info">
                Showing {{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }} to 
                {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }} of {{ $pagination['total'] }} entries
            </div>

            <div class="pagination-controls">
                @if ($pagination['current_page'] > 1)
                    <a href="{{ route('activity.logs', ['page' => $pagination['current_page'] - 1, 'per_page' => $perPage, 'user_id' => $selectedUser]) }}" class="btn btn-sm pagination-btn">Previous</a>
                @endif
                @if ($pagination['current_page'] < $pagination['last_page'])
                    <a href="{{ route('activity.logs', ['page' => $pagination['current_page'] + 1, 'per_page' => $perPage, 'user_id' => $selectedUser]) }}" class="btn btn-sm pagination-btn">Next</a>
                @endif
            </div>

            <div class="per-page-form">
                <label for="perPage">Entries</label>
                <select name="perPage" id="perPage" class="form-control form-control-sm" onchange="window.location.href='{{ route('activity.logs', ['page' => 1, 'user_id' => $selectedUser]) }}&per_page=' + this.value;">
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
        @endif
    </div>
@endsection
