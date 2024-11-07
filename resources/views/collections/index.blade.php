@extends('layouts.app')

@section('content')

<style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
 
body {
    font-family: 'Poppins', sans-serif;
    line-height: 1.6;
    color: #333;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
}

.container {
    max-width: 800px;
    width: 100%;
}

h1 {
    text-align: center;
    margin-bottom: 30px;
    color: #2c3e50;
    font-size: 2.5rem;
    font-weight: 600;
}

.search-container {
    position: relative;
    margin-bottom: 40px;
}

.search-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 8px;
    border-radius: 40px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.search-icon {
    width: 20px;
    height: 20px;
    margin-left: 12px;
    color: #666;
}

.search-input {
    flex: 1;
    padding: 12px;
    font-size: 16px;
    border: none;
    background: none;
    outline: none;
}

.search-button {
    padding: 12px 32px;
    background-color: #3498db;
    color: #fff;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.search-button:hover {
    background-color: #2980b9;
}

.suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 10;
    display: none;
}

.suggestions ul {
    list-style-type: none;
    padding: 0;
}

.suggestions li {
    padding: 10px 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.suggestions li:hover {
    background-color: #f0f0f0;
}

.card-list {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.card {
    background-color: #fff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
    min-height: 200px;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.card h2 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #2c3e50;
    font-size: 1.5rem;
}

.card p {
    margin-bottom: 25px;
    color: #7f8c8d;
}

.select-button {
    position: absolute;
    bottom: 25px;
    right: 25px;
    padding: 12px 24px;
    background-color: #3498db;
    color: #fff;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-decoration: none;
}

.select-button:hover {
    background-color: #2980b9;
}

.pagination {
    display: flex;
    justify-content: center;
    margin-top: 40px;
    gap: 10px;
}

.pagination > * {
    padding: 10px 15px;
    background-color: #3498db;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.pagination > *:hover:not(.active) {
    background-color: #2980b9;
}

.pagination > .active {
    background-color: #2980b9;
    cursor: default;
}

.pagination > .disabled {
    background-color: #bdc3c7;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    h1 {
        font-size: 2rem;
    }
}

#search-form {
    display: flex;
    margin: 0;
    padding: 0;
    flex-grow: 1; /* Allows the form to grow to fill the available space */
}
</style>
<div class="container">
    <h1>Which collection are you looking for?</h1>
    
    <div class="search-container">
        <div class="search-wrapper">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>

            <form method="GET" action="{{ route('collections.index') }}" id="search-form">
                <input type="search" placeholder="Search collections..." class="search-input" id="searchInput" name="search"  value="{{ request('search') }}">
                <button class="search-button" id="searchButton" type="submit">Search</button>
            </form>
        </div>
        <div class="suggestions" id="suggestions">
            <ul></ul>
        </div>
    </div>
    
    <div class="card-list" id="cardList">
        @foreach ($collections as $collection)
            <div class="card">
                <h2>{{ Str::title(str_replace('_', ' ', $collection['name'])) }}</h2>
                <p>Size: {{ $collection['size'] }} items</p>
                <a href="{{ route('collections.show', $collection['name']) }}" class="select-button">Select this collection</a>
            </div>
        @endforeach
    </div>

    <div class="pagination">
        {{ $collections->links() }}
    </div>
</div>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        $('#searchInput').on('keyup', function() {
            let query = $(this).val();
            
            if (query.length > 1) {
                $.ajax({
                    url: '{{ route("collections.suggestions") }}',
                    type: 'GET',
                    data: { search: query },
                    success: function(data) {
                        $('#suggestions ul').empty();
                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(function(item) {
                                $('#suggestions ul').append('<li class="suggestion-item">' + item + '</li>');
                            });
                            $('#suggestions').show();
                        } else {
                            $('#suggestions').hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                    }
                });
            } else {
                $('#suggestions').hide();
            }
        });

        $(document).on('click', '.suggestion-item', function(event) {
            event.preventDefault(); // Prevent the default action
            let selectedItem = $(this).text();
            $('#searchInput').val(selectedItem);
            $('#suggestions').hide();
        });


        $(document).on('click', function(event) {
            if (!$(event.target).closest('.search-container').length) {
                $('#suggestions').hide();
            }
        });
    });
</script>

@endsection