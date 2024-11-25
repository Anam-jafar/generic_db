<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Display the user registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Add new users by admin
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Display the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle the user login request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && password_verify($request->password, $user->password)) {
            Auth::login($user);
            return redirect()->route('collections.index');
        }

        return back()->with('error', 'Invalid credentials.');
    }

    /**
     * Log out the authenticated user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with('warning', 'Logged out successfully.');
    }

    /**
     * Display a list of all users for admin.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showUsers()
    {
        if (!Auth::user() || Auth::user()->is_admin !== '1') {
            return redirect()->route('collections.index')->with('error', 'Unauthorized access.');
        }

        $users = User::all();

        return view('auth.users', compact('users'));
    }

    /**
     * Display the form to edit a specific user's details.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function editUser($id)
    {
        if (!Auth::user() || Auth::user()->is_admin !== '1') {
            return redirect()->route('collections.index')->with('error', 'Unauthorized access.');
        }

        $user = User::findOrFail($id);
        return view('auth.editUser', compact('user'));
    }

    /**
     * Update a specific user's details.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateUser(Request $request, $id)
    {
        if (!Auth::user() || Auth::user()->is_admin !== '1') {
            return redirect()->route('collections.index')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'is_admin' => 'nullable|boolean',
        ]);

        $user = User::findOrFail($id);

        $userData = $request->only('name', 'email', 'is_admin');

        if ($request->filled('password')) {
            if ($request->password === $request->password_confirmation) {
                $userData['password'] = Hash::make($request->password);
            } else {
                return redirect()->back()->with('error', 'Passwords do not match.');
            }
        }

        $user->update($userData);

        return redirect()->route('admin.users')->with('success', 'User updated successfully.');
    }

    /**
     * Delete a specific user from the database.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyUser($id)
    {
        if (!Auth::user() || Auth::user()->is_admin !== '1') {
            return redirect()->route('collections.index')->with('error', 'Unauthorized access.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users')->with('warning', 'User deleted successfully.');
    }
}
