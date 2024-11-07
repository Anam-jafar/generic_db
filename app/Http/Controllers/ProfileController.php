<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        // Get the current authenticated user
        $user = Auth::user();
    
        // Return the profile page, passing the user and the editMode flag as false
        return view('profile.index', ['user' => $user, 'editMode' => false]);
    }
    
    public function edit()
    {
        // Get the current authenticated user
        $user = Auth::user();
    
        // Return the edit profile page, passing the user and the editMode flag as true
        return view('profile.index', ['user' => $user, 'editMode' => true]);
    }
    
    public function update(Request $request)
    {
        // Validate the updated profile data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ]);
    
        // Get the current authenticated user
        $user = Auth::user();
        
        // Update the user's profile data
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
    
        // Redirect back to the profile page with a success message
        return redirect()->route('profile.index')->with('success', 'Profile updated successfully.');
    }
    
}
