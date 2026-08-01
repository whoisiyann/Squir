@extends('layouts.auth')

@section('content')

<x-auth.auth-card>

    <div class="left-panel">

        <img src="{{ asset('assets/images/squir.png') }}" alt="Squir Mascot">

    </div>

    <div class="right-panel">

        <div class="login-content">

            <div class="logo">

                <img src="{{ asset('assets/images/squir1.png') }}" alt="Squir Logo">

                <h1>Squir</h1>

            </div>

            <p class="subtitle1">
                Start organizing your digital life.
            </p>

            <h2>Create your account</h2>

            <p class="login-text">
                Create a secure account to access your digital vault.
            </p>

            <form method="POST" action="{{ route('register') }}">

                @csrf

                <x-ui.input
                    type="text"
                    name="name"
                    placeholder="Full Name"
                    icon="fa-regular fa-user"
                    value="{{ old('name') }}" />

                @error('name')
                    <div class="input-error">{{ $message }}</div>
                @enderror

                <x-ui.input
                    type="email"
                    name="email"
                    placeholder="Email address"
                    icon="fa-regular fa-envelope"
                    value="{{ old('email') }}" />

                @error('email')
                    <div class="input-error">{{ $message }}</div>
                @enderror

                <x-ui.input
                    type="password"
                    name="password"
                    placeholder="Password"
                    icon="fa-solid fa-lock"
                    :eye="true" />

                @error('password')
                    <div class="input-error">{{ $message }}</div>
                @enderror

                <x-ui.input
                    type="password"
                    name="password_confirmation"
                    placeholder="Confirm Password"
                    icon="fa-solid fa-lock"
                    :eye="true" />

                @error('password_confirmation')
                    <div class="input-error">{{ $message }}</div>
                @enderror

                <div class="options">

                    <label class="remember">

                        <input type="checkbox" name="terms" {{ old('terms') ? 'checked' : '' }}>

                        <span></span>

                        I agree to the Terms of Service and Privacy Policy

                    </label>

                </div>

                @error('terms')
                    <div class="input-error">{{ $message }}</div>
                @enderror

                <x-ui.button>

                    Create Account

                </x-ui.button>

            </form>

            <p class="register">

                Already have an account?

                <a href="{{ route('login') }}">

                    Log in

                </a>

            </p>

        </div>

    </div>

</x-auth.auth-card>

@endsection