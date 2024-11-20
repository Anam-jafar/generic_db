@extends('layouts.app')

@section('content')
<div class="collection-form-container">
    <div class="collection-form-header">
        Create New Object
    </div>
    <div class="collection-form-body">
            @if (session('success'))
                <div class="collection-alert">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="collection-warning">{{ session('warning') }}</div>
            @endif
            @if (session('error'))
                <div class="collection-danger">{{ session('error') }}</div>
            @endif
        <form action="{{ route('collections.store') }}" method="POST">
            @csrf

            <!-- Collection Name Section -->
            <div class="form-group">
                <div class="label-container">
                    <label for="collection_name">Object Name
                        <i class="fas fa-info-circle info-icon"></i>
                        <span class="info-text">Enter a unique name for your object.</span>
                    </label>
                </div>
                <input type="text" name="collection_name" id="collection_name" class="form-control" required>
            </div>

            <!-- Fields Section -->
            <div class="form-group">
                <div class="label-container">
                    <label for="fields">Fields
                        <i class="fas fa-info-circle info-icon"></i>
                        <span class="info-text">Define fields for your Object. Each field requires a name and a data type.</span>
                    </label>
                </div>
                <div id="fields-container">
                    <!-- First Field Group -->
                    <div class="field-group">
                        <div class="field-inputs">
                            <input type="text" name="fields[0][name]" class="form-control field-name"  Value="prm" >
                            <select name="fields[0][type]" class="form-control field-type">
                                <option value="" disabled selected>Select Data Type</option>
                                <option value="string">String - Alphabets, Numbers, Special Characters</option>
                                <option value="integer">Integer - Numbers only</option>
                                <option value="date">Date</option>
                                <option value="boolean">Boolean - [True, False] - [0,1]</option>
                            </select>
                        </div>

                        <!-- Translations Section -->
                        <div class="form-group mt-4">
                            <div class="label-container">
                                <label for="translations">Translations
                                    <i class="fas fa-info-circle info-icon"></i>
                                    <span class="info-text">Add translations in multiple languages. Search and select languages to add translations as tags.</span>
                                </label>
                            </div>
                            <div class="translation-container">
                                <select name="translations[]" id="translation-select-0" class="form-control translation-select" multiple>
                                    @if (is_array($languages) && count($languages))
                                        @foreach($languages as $language => $code)
                                            <option value="{{ $code }}">{{ $language }}</option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No languages available</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="field-options">
                            <div class="label-container">
                                <label>Options
                                    <i class="fas fa-info-circle info-icon"></i>
                                    <span class="info-text">Set constraints for the field: unique, nullable, or a default value.</span>
                                </label>
                            </div>
                            <div class="form-check-inline-container">
                                <div class="form-check-inline">
                                    <input type="checkbox" name="fields[0][unique]" value="1" class="form-check-input">
                                    <label class="form-check-label">Unique</label>
                                </div>
                                <div class="form-check-inline">
                                    <input type="checkbox" name="fields[0][nullable]" value="1" class="form-check-input">
                                    <label class="form-check-label">Nullable</label>
                                </div>
                                <div class="form-check-inline">
                                    <input type="text" name="fields[0][default]" class="form-control default-value" placeholder="Default Value">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second Field Group -->
                    <div class="field-group">
                        <div class="field-inputs">
                            <input type="text" name="fields[1][name]" class="form-control field-name" placeholder="Field Name" required>
                            <select name="fields[1][type]" class="form-control field-type">
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
                                    <span class="info-text">Set constraints for the field: unique, nullable, or a default value.</span>
                                </label>
                            </div>
                            <div class="form-check-inline-container">
                                <div class="form-check-inline">
                                    <input type="checkbox" name="fields[1][unique]" value="1" class="form-check-input">
                                    <label class="form-check-label">Unique</label>
                                </div>
                                <div class="form-check-inline">
                                    <input type="checkbox" name="fields[1][nullable]" value="1" class="form-check-input">
                                    <label class="form-check-label">Nullable</label>
                                </div>
                                <div class="form-check-inline">
                                    <input type="text" name="fields[1][default]" class="form-control default-value" placeholder="Default Value">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add New Field Button -->
                <button type="button" id="add-field" class="add-field-button">
                    <span>+</span>
                </button>
            </div>

            <!-- Create Collection Button -->
            <button type="submit" class="btn w-100 mt-5 gdb-button">Create Object</button>
        </form>
    </div>
</div>


@endsection
