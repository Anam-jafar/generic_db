@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        Create New Collection
    </div>
    <div class="card-body">
        <form action="{{ route('collections.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="collection_name">Collection Name</label>
                <input type="text" name="collection_name" id="collection_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="fields">Fields</label>
                <div id="fields-container">
                    <div class="field-group">
                        <input type="text" name="fields[0][name]" class="form-control mb-2" placeholder="Field Name" required>
                        <select name="fields[0][type]" class="form-control mb-2">
                            <option value="string">String</option>
                            <option value="integer">Integer</option>
                            <option value="date">Date</option>
                            <option value="boolean">Boolean</option>
                        </select>
                    </div>
                </div>
                <button type="button" id="add-field" class="btn btn-secondary">Add Field</button>
            </div>

            <button type="submit" class="btn btn-primary">Create Collection</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('add-field').addEventListener('click', function() {
        let index = document.querySelectorAll('.field-group').length;
        let fieldGroup = `
            <div class="field-group">
                <input type="text" name="fields[${index}][name]" class="form-control mb-2" placeholder="Field Name" required>
                <select name="fields[${index}][type]" class="form-control mb-2">
                    <option value="string">String</option>
                    <option value="integer">Integer</option>
                    <option value="date">Date</option>
                    <option value="boolean">Boolean</option>
                </select>
            </div>
        `;
        document.getElementById('fields-container').insertAdjacentHTML('beforeend', fieldGroup);
    });
</script>
@endsection
