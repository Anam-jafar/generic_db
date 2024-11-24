// resources/js/suggestions.js

$(document).ready(function() {
    // Hardcode the URL of the suggestions route
    const suggestionsRoute = 'generic/suggestions'; // Hardcoded URL

    // Handle search input for suggestions
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

    // --- Activity Log Functionality ---
    document.querySelectorAll('.activity-log-row').forEach(row => {
        row.addEventListener('click', function() {
            const collapseRow = this.nextElementSibling; // Get the corresponding collapse row
            const documentBefore = this.dataset.log; // Retrieve the document before data
            const arrow = this.querySelector('.activity-log-arrow'); // Get the arrow element

            if (collapseRow.style.display === "none") {
                collapseRow.style.display = "table-row"; // Show the collapsible row
                collapseRow.querySelector('.activity-log-document-before').textContent = documentBefore; // Populate the card
                arrow.textContent = "▲"; // Change arrow to up
            } else {
                collapseRow.style.display = "none"; // Hide the collapsible row
                arrow.textContent = "▼"; // Change arrow to down
            }
        });
    });

    // --- Add Field Functionality ---
    // Attach event listeners to info icons
    function attachInfoListeners() {
        document.querySelectorAll('.info-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                // Find the next sibling with class "info-text" and toggle visibility
                let infoText = this.nextElementSibling;
                if (infoText && infoText.classList.contains('info-text')) {
                    infoText.style.display = (infoText.style.display === 'none' || !infoText.style.display) ? 'inline-block' : 'none';
                }
            });
        });
    }

    // Initial call to attach listeners on page load
    attachInfoListeners();

    document.getElementById('add-field').addEventListener('click', function() {
        // Calculate the next index based on the existing number of '.field-group' elements
        let index = document.querySelectorAll('.field-group').length;
    
        // Create a new field group with the correct index
        let fieldGroup = `
            <div class="field-group">
                <div class="field-inputs">
                    <input type="text" name="fields[${index}][name]" class="form-control field-name" placeholder="Field Name" required>
                    <select name="fields[${index}][type]" class="form-control field-type">
                        <option value="" disabled selected>Select Data Type</option>
                        <option value="string">String - Alphabets, Numbers, Special Characters</option>
                        <option value="integer">Integer - Numbers only</option>
                        <option value="date">Date</option>
                        <option value="boolean">Boolean - [True, False] - [0,1]</option>
                    </select>
                </div>
                <div class="field-options">
                    <div class="label-container">
                        <label>Options
                            <i class="fas fa-info-circle info-icon"></i>
                            <span class="info-text" style="display: none;">Set constraints for the field: unique, nullable, or a default value.</span>
                        </label>
                    </div>
                    <div class="form-check-inline-container">
                        <div class="form-check-inline">
                            <input type="checkbox" name="fields[${index}][unique]" value="1" class="form-check-input">
                            <label class="form-check-label">Unique</label>
                        </div>
                        <div class="form-check-inline">
                            <input type="checkbox" name="fields[${index}][nullable]" value="1" class="form-check-input">
                            <label class="form-check-label">Nullable</label>
                        </div>
                        <div class="form-check-inline">
                            <input type="text" name="fields[${index}][default]" class="form-control default-value" placeholder="Default Value">
                        </div>
                    </div>
                </div>
            </div>
        `;
    
        // Append the new field group to the container
        document.getElementById('fields-container').insertAdjacentHTML('beforeend', fieldGroup);
    });


    $('.translation-select').select2({
        placeholder: "Add languages",
        allowClear: true,
    });

    const translationsToggle = document.getElementById('translations-toggle');
    const languageOptions = document.getElementById('language-options');
    const firstFieldName = document.querySelector('input[name="fields[0][name]"]');

    translationsToggle.addEventListener('change', () => {
        if (translationsToggle.checked) {
            // Ensure the first field's name is "prm"
            if (firstFieldName && firstFieldName.value === 'prm') {
                languageOptions.style.display = 'block';
                firstFieldName.readOnly = true; // Disable editing the first field's name
            } else {
                alert('The first field name must have a value of "prm" to enable translations.');
                translationsToggle.checked = false;
            }
        } else {
            languageOptions.style.display = 'none';
            if (firstFieldName) {
                firstFieldName.readOnly = false; // Re-enable editing if checkbox is unchecked
            }
        }
    });
    
});
