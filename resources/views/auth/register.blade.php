@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="card shadow-lg rounded gdb-auth-card">
            <div class="generic-db-banner">
                    <h1>Generic DB.</h1>
            </div>
            <hr>
            <h2 class="text-center mb-4">Register</h2>

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

                <button type="submit" class="btn w-100 gdb-auth-button">Register</button>
            </form>

            <div class="text-center mt-3">
                <p>Already have an account? <a href="{{ route('login') }}" class="text-decoration-none">Login</a></p>
            </div>
        </div>
    </div>
@endsection
