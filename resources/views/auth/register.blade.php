@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="card shadow-lg rounded gdb-auth-card">
            <h2 class="text-center mb-4">Register new user</h2>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                </div>

                <!-- New checkbox field -->
                <div class="form-group form-check">
                    <input type="hidden" name="is_admin" value=0>
                    <input type="checkbox" id="is_admin" name="is_admin" value=1 class="form-check-input">
                    <label class="form-check-label" for="is_admin">Mark this user as admin</label>
                </div>

                <button type="submit" class="btn w-100 gdb-auth-button">Register</button>
            </form>

        </div>
    </div>
@endsection
