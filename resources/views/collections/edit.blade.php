@extends('layouts.app')

@section('content')
    <h1>Edit Document in {{ $collectionName }}</h1>

    <form method="POST" action="{{ route('collections.update', [$collectionName, $document['_id']]) }}">
    @csrf
    <input type="hidden" name="_method" value="POST"> <!-- Spoof the PUT method -->

    @foreach ($document as $field => $value)
        @if ($field !== '_id') <!-- Skip the '_id' field as it shouldn't be editable -->
            <div>
                <label for="{{ $field }}">{{ ucfirst($field) }}</label>
                <input type="text" name="data[{{ $field }}]" 
                       id="{{ $field }}" 
                       value="{{ old('data.' . $field, $value) }}" 
                       placeholder="{{ ucfirst($field) }}">
            </div>
        @endif
    @endforeach

    <button type="submit">Update</button>
</form>

@endsection
