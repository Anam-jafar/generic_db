// resources/js/add_field.js

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
document.addEventListener('DOMContentLoaded', function () {
    attachInfoListeners();
});

// Add new field group logic
document.getElementById('add-field').addEventListener('click', function() {
    let index = document.querySelectorAll('.field-group').length;
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
    document.getElementById('fields-container').insertAdjacentHTML('beforeend', fieldGroup);
    attachInfoListeners(); // Re-attach listeners to new icons
});
