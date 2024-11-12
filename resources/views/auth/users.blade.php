@extends('layouts.app')

@section('content')
    <div class="custom-users-container mt-5">
        <div class="custom-btn-container">
            <h2>All Users</h2>
            <!-- Add New User Button -->
            <a href="{{ route('register') }}" class="btn gdb-button">Add New User</a>
        </div>

        <table class="custom-users-table mt-3">
            <thead>
                <tr>
                    <th class="custom-users-header">Name</th>
                    <th class="custom-users-header">Email</th>
                    <th class="custom-users-header">Is Admin</th>
                    <th class="custom-users-header">Created At</th>
                    <th class="custom-users-header">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="custom-users-row">
                        <td class="custom-users-name">{{ $user->name }}</td>
                        <td class="custom-users-email">{{ $user->email }}</td>
                        <td class="custom-users-admin">{{ $user->is_admin ? 'Yes' : 'No' }}</td>
                        <td class="custom-users-created">{{ $user->created_at }}</td>
                        <td class="custom-users-actions">
                            <a href="{{ route('users.edit', $user->id) }}" class="custom-btn-action custom-btn-edit">Edit</a>
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="custom-btn-action custom-btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
