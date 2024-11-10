@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="card shadow-lg rounded gdb-auth-card">
            <h2 class="text-center mb-4">Edit Document in {{ $collectionName }}</h2>

            <form method="POST" action="{{ route('collections.update', [$collectionName, $document['_id']]) }}">
                @csrf
                @foreach ($document as $field => $value)
                    @if ($field !== '_id' && $field !== 'deleted' && $field !== 'created_at' && $field !== 'updated_at') <!-- Skip non-editable fields -->
                        <div class="form-group">
                            <label for="{{ $field }}">{{ ucfirst($field) }}</label>

                            <!-- Dynamically set the input type based on field type -->
                            @if (isset($fieldTypes[$field]))
                                @switch($fieldTypes[$field])
                                    @case('integer')
                                        <input type="number" name="data[{{ $field }}]" 
                                               id="{{ $field }}" 
                                               class="form-control" 
                                               value="{{ old('data.' . $field, $value) }}" 
                                               placeholder="{{ ucfirst($field) }}">
                                        @break
                                    @case('boolean')
                                        <input type="checkbox" name="data[{{ $field }}]" 
                                               id="{{ $field }}" 
                                               class="form-check-input"
                                               {{ old('data.' . $field, $value) ? 'checked' : '' }}>
                                        @break
                                    @case('date')
                                        <input type="date" name="data[{{ $field }}]" 
                                               id="{{ $field }}" 
                                               class="form-control"
                                               value="{{ old('data.' . $field, $value ? $value->toDateTime()->format('Y-m-d') : '') }}" 
                                               placeholder="{{ ucfirst($field) }}">
                                        @break
                                    @default
                                        <input type="text" name="data[{{ $field }}]" 
                                               id="{{ $field }}" 
                                               class="form-control"
                                               value="{{ old('data.' . $field, $value) }}" 
                                               placeholder="{{ ucfirst($field) }}">
                                @endswitch
                            @else
                                <!-- Default to text input if no specific type is found -->
                                <input type="text" name="data[{{ $field }}]" 
                                       id="{{ $field }}" 
                                       class="form-control"
                                       value="{{ old('data.' . $field, $value) }}" 
                                       placeholder="{{ ucfirst($field) }}">
                            @endif
                        </div>
                    @endif
                @endforeach

                <button type="submit" class="btn w-100 gdb-auth-button">Update Document</button>
            </form>
        </div>
    </div>
@endsection
