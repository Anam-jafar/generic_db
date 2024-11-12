@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="card shadow-lg rounded gdb-auth-card">
            <div class="generic-db-banner">
                <h1>Generic DB.</h1>
            </div>
            <hr>
            <h2 class="text-center mb-4">Edit User</h2>

            <form method="POST" action="{{ route('users.update', $user->id) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
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

                <!-- Checkbox to mark as admin -->
                <div class="form-group form-check">
                    <input type="hidden" name="is_admin" value="0">
                    <input type="checkbox" id="is_admin" name="is_admin" value="1" class="form-check-input" {{ $user->is_admin ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_admin">Mark this user as admin</label>
                </div>

                <button type="submit" class="btn w-100 gdb-auth-button">Update User</button>
            </form>

            <div class="text-center mt-3">
                <p><a href="{{ route('admin.users') }}" class="text-decoration-none">Back to User List</a></p>
            </div>
        </div>
    </div>
@endsection
