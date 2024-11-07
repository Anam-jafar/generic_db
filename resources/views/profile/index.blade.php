@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="card p-4 shadow-lg rounded mx-auto" style="max-width: 500px;">
            <h2 class="text-center mb-4">
                @if($editMode) 
                    Edit Profile 
                @else 
                    Profile 
                @endif
            </h2>

            @if($editMode)
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ $user->name }}" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ $user->email }}" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-3">Update Profile</button>
                </form>
            @else
                <p><strong>Name:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>

                <a href="{{ route('profile.edit') }}" class="btn btn-primary w-100 mt-3">Edit Profile</a>
            @endif
        </div>
    </div>
@endsection
