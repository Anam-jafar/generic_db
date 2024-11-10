// resources/js/suggestions.js


    // Hardcode the URL of the suggestions route
    const suggestionsRoute = '/suggestions'; // Hardcoded URL

    $('#searchInput').on('keyup', function() {
        let query = $(this).val();
        if (query.length > 1) {
            $.ajax({
                url: suggestionsRoute, // Use the hardcoded URL
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
            $('#suggestions').hide(); // Hide suggestions if the query is too short
        }
    });

    $(document).on('click', '.suggestion-item', function(event) {
        event.preventDefault(); // Prevent the default action
        let selectedItem = $(this).text();
        $('#searchInput').val(selectedItem);
        $('#suggestions').hide(); // Hide suggestions after selecting
    });

    $(document).on('click', function(event) {
        if (!$(event.target).closest('.search-container').length) {
            $('#suggestions').hide(); // Hide suggestions if clicked outside
        }
    });

