@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="card shadow-lg rounded gdb-auth-card">
            <h2 class="text-center mb-4">
                @if($editMode) 
                    Edit Profile 
                @else 
                    Profile 
                @endif
            </h2>
            @if (session('success'))
                <div class="collection-alert">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="collection-warning">{{ session('warning') }}</div>
            @endif
            @if (session('error'))
                <div class="collection-danger">{{ session('error') }}</div>
            @endif

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

                    <!-- Password is optional in edit form -->
                    <div class="form-group">
                        <label for="password">Password (leave blank to keep current password)</label>
                        <input type="password" id="password" name="password" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                    </div>

                    <button type="submit" class="btn w-100 gdb-auth-button">Update Profile</button>
                </form>
            @else
                <form>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ $user->name }}" disabled>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ $user->email }}" disabled>
                    </div>

                    <button type="button" onclick="window.location='{{ route('profile.edit') }}'" class="btn w-100 gdb-auth-button">Edit </button>
                </form>
            @endif
        </div>
    </div>
@endsection
