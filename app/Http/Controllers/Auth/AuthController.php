<?php

// namespace App\Http\Controllers\Auth;

// use App\Http\Controllers\Controller;
// use App\Models\User;
// use Illuminate\Http\RedirectResponse;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Validation\Rules;
// use Illuminate\View\View;

// class AuthController extends Controller
// {
//     /**
//      * Ipapakita yung auth.blade.php (login + signup sliding panel)
//      */
//     public function showAuth(): View
//     {
//         return view('auth');
//     }

//     /**
//      * NOTE: base sa disenyo mo (Email address, Password, Remember me),
//      * ito yung field names na ginamit ko: email, password, remember.
//      * Kung magkaiba yung name="..." attributes mo, sabihin mo lang.
//      */
//     public function login(Request $request): RedirectResponse
//     {
//         $credentials = $request->validate([
//             'email'    => ['required', 'string', 'email'],
//             'password' => ['required', 'string'],
//         ]);

//         if (! Auth::attempt($credentials, $request->boolean('remember'))) {
//             return back()->withErrors([
//                 'email' => 'Mali ang email o password na inilagay mo.',
//             ])->onlyInput('email');
//         }

//         $request->session()->regenerate();

//         return redirect()->intended(route('dashboard'));
//     }

//     /**
//      * NOTE: base sa disenyo mo (Full Name, Email address, Password,
//      * Confirm Password, Terms checkbox), ito yung field names ginamit ko:
//      * name, email, password, password_confirmation, terms.
//      */
//     public function register(Request $request): RedirectResponse
//     {
//         $validated = $request->validate([
//             'name'     => ['required', 'string', 'max:255'],
//             'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
//             'password' => ['required', 'confirmed', Rules\Password::defaults()],
//             'terms'    => ['accepted'],
//         ]);

//         $user = User::create([
//             'name'     => $validated['name'],
//             'email'    => $validated['email'],
//             'password' => Hash::make($validated['password']),
//         ]);

//         Auth::login($user);
//         $request->session()->regenerate();

//         return redirect()->route('dashboard');
//     }

//     public function logout(Request $request): RedirectResponse
//     {
//         Auth::logout();

//         $request->session()->invalidate();
//         $request->session()->regenerateToken();

//         return redirect()->route('auth');
//     }
// }











namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showAuth(): View
    {
        return view('auth');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Mali ang email o password na inilagay mo.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        // FIX: "fullname" na yung ginamit dito, tugma na sa name="fullname"
        // ng input mo sa auth.blade.php (dati "name" ang hinahanap, kaya
        // laging nagfa-fail yung validation)
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms'    => ['accepted'],
        ]);

        $user = User::create([
            // "name" pa rin yung column sa users table, kaya dito lang
            // nag-map papunta mula sa "fullname" field ng form mo
            'name'     => $validated['fullname'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth');
    }
}
