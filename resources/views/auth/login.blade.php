@extends('layouts.app')

@section('content')
    <!-- New Div with "Generic DB" -->


    <div class="container mt-5">
        
        <div class="card shadow-lg rounded gdb-auth-card">
            <div class="generic-db-banner">
                <h1>Generic DB.</h1>
            </div>
            <hr>
            <h2 class="text-center mb-4">Login</h2>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn w-100 gdb-auth-button">Login</button>
            </form>
        </div>
    </div>
@endsection
