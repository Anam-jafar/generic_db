@extends('layouts.app')

@section('content')

<div class="container d-flex">
    <h1>Which collection are you looking for?</h1>

    <div class="search-container">
        <div class="search-wrapper">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <form method="GET" action="{{ route('collections.index') }}" id="search-form" class="search-form">
                <input type="search" placeholder="Search collections..." class="search-input" id="searchInput" name="search" value="{{ request('search') }}">
                <button class="search-button" id="searchButton" type="submit" form="search-form">Search</button>
            </form>
        </div>

            <!-- Suggestions dropdown -->
    <div id="suggestions" class="suggestions-container">
        <ul></ul>
    </div>
    </div>
    @if (session('success'))
            <div class="collection-alert">{{ session('success') }}</div>
        @endif


    <!-- Collections List -->
    <div class="search-res-card-list" id="cardList">
        @foreach ($collections as $collection)
            <a href="{{ route('collections.show', $collection['name']) }}" class="search-res-card">
                <h2>{{ Str::title(str_replace('_', ' ', $collection['name'])) }}</h2>
                <p>Entries: {{ $collection['size'] }}</p>
            </a>
        @endforeach
    </div>

<!-- Pagination Controls -->
<div class="collection-pagination">
    <!-- Left side: Showing X to Y of Z entries -->
    <div class="pagination-info">
        Showing {{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }} to {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }} of {{ $pagination['total'] }} entries
    </div>

    <!-- Center: Pagination links (Previous/Next) -->
    <div class="pagination-controls">
        @if ($pagination['current_page'] > 1)
            <a href="{{ route('collections.index', ['page' => $pagination['current_page'] - 1, 'search' => $search, 'per_page' => $perPage]) }}" class="btn btn-sm pagination-btn">Previous</a>
        @endif
        @if ($pagination['current_page'] < $pagination['last_page'])
            <a href="{{ route('collections.index', ['page' => $pagination['current_page'] + 1, 'search' => $search, 'per_page' => $perPage]) }}" class="btn btn-sm pagination-btn">Next</a>
        @endif
    </div>

<!-- Right side: Entries per page -->
<div class="per-page-form">
    <label for="perPage">Entries</label>
    <select name="perPage" id="perPage" class="form-control form-control-sm" onchange="window.location.href='{{ route('collections.index', ['search' => $search, 'page' => 1]) }}&per_page=' + this.value;">
        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
    </select>
</div>

</div>

</div>

@endsection
