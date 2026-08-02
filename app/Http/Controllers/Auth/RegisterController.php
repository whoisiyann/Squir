<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisterController extends Controller
{
    /**
     * Handle an incoming registration request.
     * Ito yung tinatawag pag na-submit yung register form mo sa auth.blade.php
     *
     * NOTE: Palitan mo na lang yung field names dito (name, email, password)
     * kung magkaiba yung "name" attributes ng inputs mo sa form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Auto login agad after mag-register
        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
