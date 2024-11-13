<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->is_admin,
        ]);

        return redirect()->route('admin.users')->with('success', 'New user registration successful.');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
{
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Retrieve the user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and verify the password
        if ($user && password_verify($request->password, $user->password)) {
            Auth::login($user); // Log the user in
            return redirect()->route('collections.index'); // Redirect to collections page
        }

        return back()->with('error', 'Invalid credentials.');
    }
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with('warning', 'Logged out successfully.');
    }


    public function showUsers()
    {
        // Authorize if the user is an admin
        if (!Auth::user() || Auth::user()->is_admin !== '1') {
            return redirect()->route('collections.index')->with('error', 'Unauthorized access.');
        }

        // Fetch all users
        $users = User::all();

        // Show the view with user data
        return view('auth.users', compact('users'));
    }

    public function editUser($id)
{
    // Authorize if the user is an admin
    if (!Auth::user() || Auth::user()->is_admin !== '1') {
        return redirect()->route('collections.index')->with('error', 'Unauthorized access.');
    }

    $user = User::findOrFail($id);
    return view('auth.editUser', compact('user'));
}

public function updateUser(Request $request, $id)
{
    // Authorize if the user is an admin
    if (!Auth::user() || Auth::user()->is_admin !== '1') {
        return redirect()->route('collections.index')->with('error', 'Unauthorized access.');
    }

    // Validate the request
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,' . $id,
        'is_admin' => 'nullable|boolean',
    ]);

    $user = User::findOrFail($id);

    // Update the user's information
    $userData = $request->only('name', 'email', 'is_admin');

    // If a password is provided, hash it and add it to the update data
    if ($request->filled('password')) {
        if ($request->password === $request->password_confirmation) {
            $userData['password'] = Hash::make($request->password);  // Hash the password
        } else {
            return redirect()->back()->with('error', 'Passwords do not match.');
        }
    }

    // Update the user with the validated data
    $user->update($userData);

    return redirect()->route('admin.users')->with('success', 'User updated successfully.');
}


public function destroyUser($id)
{
    // Authorize if the user is an admin
    if (!Auth::user() || Auth::user()->is_admin !== '1') {
        return redirect()->route('collections.index')->with('error', 'Unauthorized access.');
    }

    $user = User::findOrFail($id);
    $user->delete();

    return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
}

}
