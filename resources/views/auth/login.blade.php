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

            <p class="subtitle">
                Your Personal Digital Vault <br>
                and Productivity Companion
            </p>

            <h2>Ready to be productive?</h2>

            <p class="login-text">
                Everything you need all in one place.
            </p>

            <form>

                <x-ui.input
                    type="email"
                    name="email"
                    placeholder="Email Address"
                    icon="fa-regular fa-envelope"/>

                <x-ui.input
                    type="password"
                    name="password"
                    placeholder="Password"
                    icon="fa-solid fa-lock"
                    :eye="true"/>

                <div class="options">

                    <label class="remember">

                        <input type="checkbox">

                        <span></span>

                        Remember me

                    </label>

                    <a href="#">Forgot password?</a>

                </div>

                <x-ui.button>

                    Log In

                </x-ui.button>

            </form>

            <p class="register">

                Don't have an account?

                <a href="/register">

                    Sign Up

                </a>

            </p>

        </div>

    </div>

</x-auth.auth-card>

@endsection




