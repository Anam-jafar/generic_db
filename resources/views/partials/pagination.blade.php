<div class="collection-pagination">
    <!-- Left side: Showing X to Y of Z entries -->
    <div class="pagination-info">
        Showing {{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }} to 
        {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }} of {{ $pagination['total'] }} entries
    </div>

    <!-- Center: Pagination links (Previous/Next) -->
    <div class="pagination-controls">
        @if ($pagination['current_page'] > 1)
            <a href="{{ $prevPageUrl }}" class="btn btn-sm pagination-btn">Previous</a>
        @endif
        @if ($pagination['current_page'] < $pagination['last_page'])
            <a href="{{ $nextPageUrl }}" class="btn btn-sm pagination-btn">Next</a>
        @endif
    </div>

    <!-- Right side: Entries per page -->
    <div class="per-page-form">
        <label for="perPage">Entries</label>
        <select name="perPage" id="perPage" class="form-control form-control-sm" onchange="window.location.href='{{ $perPageUrl }}' + this.value;">
            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
        </select>
    </div>
</div>
