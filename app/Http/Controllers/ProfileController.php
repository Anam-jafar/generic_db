<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', ['user' => $user, 'editMode' => false]);
    }
    
    /**
     * Display the form to edit the authenticated user's profile.
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.index', ['user' => $user, 'editMode' => true]);
    }
    
    /**
     * Update the authenticated user's profile data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ]);
    
        $user = Auth::user();
        $user->name = $request->name;
        $user->email = $request->email;
        
        if (isset($request->password)) {
            if ($request->password == $request->password_confirmation) {
                $user->password = Hash::make($request->password);
            }
        }

        $user->save();
    
        return redirect()->route('profile.index')->with('success', 'Profile updated successfully.');
    }
}
