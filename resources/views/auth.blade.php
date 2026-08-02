<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Squir - Log In / Sign Up</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jockey+One&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">


<style>
    :root {
        --squir-brown: #6b3f2a;
        --squir-brown-dark: #4a2b1c;
        --squir-cream: #faf6f0;
        --squir-tan: #e8985a;
        --squir-green: #8a9a5b;
    }

    .jockey-one-regular {
        font-family: "Jockey One", sans-serif;
        font-weight: 400;
        font-style: normal;
    }


    html, body {
        height: 100%;
    }

    body {
        font-family: 'Segoe UI', sans-serif;
        color: #333;

        background-color: var(--squir-cream);
        background-image:
            radial-gradient(circle at 8% 15%, rgba(232, 152, 90, 0.25) 0%, rgba(232, 152, 90, 0) 45%),
            radial-gradient(circle at 92% 20%, rgba(138, 154, 91, 0.22) 0%, rgba(138, 154, 91, 0) 40%),
            radial-gradient(circle at 15% 85%, rgba(107, 63, 42, 0.18) 0%, rgba(107, 63, 42, 0) 45%),
            radial-gradient(circle at 88% 90%, rgba(232, 152, 90, 0.2) 0%, rgba(232, 152, 90, 0) 40%),
            radial-gradient(circle at 50% 50%, rgba(138, 154, 91, 0.08) 0%, rgba(138, 154, 91, 0) 60%);
        background-attachment: fixed;
        background-repeat: no-repeat;
        position: relative;
        overflow-x: hidden;
    }

    body::before {
        content: "";
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
        opacity: 0.35;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160'%3E%3Cg fill='%236b3f2a' fill-opacity='0.10'%3E%3Cpath d='M40 20c-8 0-14 6-14 14 0 10 14 26 14 26s14-16 14-26c0-8-6-14-14-14z'/%3E%3Cpath d='M120 70c-6 0-11 5-11 11 0 8 11 20 11 20s11-12 11-20c0-6-5-11-11-11z'/%3E%3Cpath d='M25 110c-5 0-9 4-9 9 0 6 9 16 9 16s9-10 9-16c0-5-4-9-9-9z'/%3E%3C/g%3E%3C/svg%3E");
        background-size: 160px 160px;
        background-repeat: repeat;
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 15px;
        position: relative;
        z-index: 1;
    }

    /* ============ VIEW STAGE (fade cross between login/signup) ============ */
    .auth-stage {
        position: relative;
        width: 100%;
        max-width: 900px;
    }

    .view {
        width: 100%;
        transition: opacity 0.25s ease;
    }

    .view.hidden {
        opacity: 0;
        pointer-events: none;
        position: absolute;
        top: 0;
        left: 0;
    }

    .view.visible {
        opacity: 1;
        position: relative;
    }

    .auth-card {
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        width: 100%;
        min-height: 610px;
    }

    /* Image panel — shared styling for both login and signup (always left) */
    .auth-image-panel {
        background-color: var(--squir-brown-dark);
        display: flex;
        align-items: flex-end;
        justify-content: center;
        overflow: hidden;
        min-height: 550px;
    }

    .auth-image-panel img {
        display: block;
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        opacity: 0.9;
    }

    .auth-form-panel {
        padding: 50px 45px;
    }

    .brand-logo {
        font-family: "Jockey One", sans-serif;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-align: center;
        color: var(--squir-brown-dark);
        font-size: 2.8rem;
        font-weight: bold;
    }

    .brand-logo img {
        max-width: 50px;
        height: auto;
    }

    .brand-tagline {
        font-family: "Jockey One", sans-serif;
        text-align: center;
        color: #777;
        font-size: 1.5rem;
        margin-bottom: 25px;
    }

    .input-group {
        border: 1px solid #6b3f2a;
        padding: 0.375rem 0.75rem;
        border-radius: 12px;
        overflow: hidden;
    }

    .input-group-text,
    .input-group .form-control {
        border: none;
        box-shadow: none;
    }

    .input-group .toggle-btn {
        border-left: 1px solid #6b3f2a;
        border-radius: 0;
        background-color: #fff;
        cursor: pointer;
        padding-left: 12px;
    }

    .input-group:focus-within {
        border-color: #6b3f2a;
        box-shadow: 0 0 0 .2rem rgba(111, 78, 55, 0.425);
    }

    .input-group.has-error {
        border-color: #dc3545;
    }

    .field-error {
        color: #dc3545;
        font-size: 0.8rem;
        margin-top: 4px;
    }

    .alert-squir-error {
        background-color: #fdecec;
        color: #b02a2a;
        border: 1px solid #f3c2c2;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.85rem;
        margin-bottom: 16px;
    }

    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear {
        display: none;
    }

    input::-webkit-credentials-auto-fill-button,
    input::-webkit-caps-lock-indicator {
        display: none !important;
    }

    .btn-squir {
        background-color: var(--squir-brown-dark);
        color: #fff;
        border: none;
        padding: 12px;
        border-radius: 10px;
        font-weight: 600;
        width: 100%;
    }

    .btn-squir:hover {
        background-color: var(--squir-brown);
        color: #fff;
    }

    .link-squir {
        color: var(--squir-brown-dark);
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .link-squir:hover {
        text-decoration: underline;
    }

    .form-check-input {
        border-color: var(--squir-brown-dark);
    }

    .form-check-input:checked {
        background-color: var(--squir-brown-dark);
        border-color: var(--squir-brown-dark);
    }

    .form-check-input:focus {
        border-color: var(--squir-brown);
        box-shadow: 0 0 0 0.2rem rgba(107, 63, 42, 0.25);
    }

    @media (max-width: 767.98px) {
        .auth-image-panel {
            display: none;
        }
    }

    /* ============ CASCADE (staggered) ENTRANCE ANIMATION ============ */
    .cascade-item {
        opacity: 0;
        transform: translateY(16px);
    }

    .cascade-item.play {
        animation: cascadeIn 0.5s cubic-bezier(.2, .8, .2, 1) forwards;
        animation-delay: calc(var(--i, 0) * 0.08s);
    }

    @keyframes cascadeIn {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
</head>
<body>

<div class="auth-wrapper">
  <div class="auth-stage" id="authStage">

    <!-- ============ LOG IN VIEW (image left) ============ -->
    <div class="view visible" id="loginView">
    <div class="auth-card row g-0">

        <div class="col-md-6 auth-image-panel">
            <img src="{{ asset('assets/images/squir.png') }}" alt="Squir illustration">
        </div>

        <div class="col-md-6 auth-form-panel">
            <div class="brand-logo cascade-item" style="--i:0">
                <img src="{{ asset('assets/images/squir1.png') }}" alt="Squir Logo">
                Squir
            </div>
            <p class="brand-tagline cascade-item" style="--i:1">Your Personal Digital Vault and Productivity Companion</p>

            <h4 class="fw-bold mb-1 cascade-item" style="--i:2">Welcome back!</h4>
            <p class="text-muted mb-4 cascade-item" style="--i:3">Log in to continue to your account.</p>

            @if ($errors->any() && ! $errors->has('fullname') && ! $errors->has('terms'))
                <div class="alert-squir-error cascade-item" style="--i:3">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" autocomplete="off">
                @csrf
                <div class="mb-3 cascade-item" style="--i:4">
                    <div class="input-group {{ $errors->has('email') && ! $errors->has('fullname') ? 'has-error' : '' }}">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Email address" value="{{ old('email') }}" required>
                    </div>
                    @error('email')
                        @if (! $errors->has('fullname'))
                            <div class="field-error">{{ $message }}</div>
                        @endif
                    @enderror
                </div>

                <div class="mb-3 cascade-item" style="--i:5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Password" required autocomplete="new-password">
                        <span class="input-group-text toggle-btn" onclick="togglePassword('loginPassword','loginToggleIcon')">
                            <i class="bi bi-eye" id="loginToggleIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 cascade-item" style="--i:6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="link-squir small">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-squir cascade-item" style="--i:7">Log In</button>

                <p class="text-center mt-4 mb-0 small cascade-item" style="--i:8">
                    Don't have an account? <a class="link-squir" onclick="showSignup()">Sign up</a>
                </p>
            </form>
        </div>

    </div>
    </div>

    <!-- ============ SIGN UP VIEW (image stays left) ============ -->
    <div class="view hidden" id="signupView">
    <div class="auth-card row g-0">

        <div class="col-md-6 auth-image-panel">
            <img src="{{ asset('assets/images/squir.png') }}" alt="Squir illustration">
        </div>

        <div class="col-md-6 auth-form-panel">
            <div class="brand-logo cascade-item" style="--i:0">
                <img src="{{ asset('assets/images/squir1.png') }}" alt="Squir Logo">
                Squir
            </div>
            <p class="brand-tagline cascade-item" style="--i:1">Your Personal Digital Vault and Productivity Companion</p>

            <h4 class="fw-bold mb-1 cascade-item" style="--i:2">Create your account</h4>
            <p class="text-muted mb-4 cascade-item" style="--i:3">Start organizing your digital life.</p>

            <form action="{{ route('register') }}" method="POST" autocomplete="off">
                @csrf
                <div class="mb-3 cascade-item" style="--i:4">
                    <div class="input-group {{ $errors->has('fullname') ? 'has-error' : '' }}">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="fullname" class="form-control" placeholder="Full Name" value="{{ old('fullname') }}" required>
                    </div>
                    @error('fullname')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 cascade-item" style="--i:5">
                    <div class="input-group {{ $errors->has('email') && $errors->has('fullname') ? 'has-error' : '' }}">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Email address" value="{{ old('email') }}" required>
                    </div>
                    @error('email')
                        @if ($errors->has('fullname'))
                            <div class="field-error">{{ $message }}</div>
                        @endif
                    @enderror
                </div>

                <div class="mb-3 cascade-item" style="--i:6">
                    <div class="input-group {{ $errors->has('password') && $errors->has('fullname') ? 'has-error' : '' }}">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="signupPassword" class="form-control" placeholder="Password" required autocomplete="new-password">
                        <span class="input-group-text toggle-btn" onclick="togglePassword('signupPassword','signupToggleIcon')">
                            <i class="bi bi-eye" id="signupToggleIcon"></i>
                        </span>
                    </div>
                    @error('password')
                        @if ($errors->has('fullname'))
                            <div class="field-error">{{ $message }}</div>
                        @endif
                    @enderror
                </div>

                <div class="mb-3 cascade-item" style="--i:7">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input type="password" name="password_confirmation" id="confirmPassword" class="form-control" placeholder="Confirm Password" required autocomplete="new-password">
                        <span class="input-group-text toggle-btn" onclick="togglePassword('confirmPassword','confirmToggleIcon')">
                            <i class="bi bi-eye" id="confirmToggleIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="form-check mb-4 cascade-item" style="--i:8">
                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                    <label class="form-check-label small" for="terms">
                        I agree to the <a href="#" class="link-squir">Terms of Service</a> and <a href="#" class="link-squir">Privacy Policy</a>
                    </label>
                    @error('terms')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-squir cascade-item" style="--i:9">Create Account</button>

                <p class="text-center mt-4 mb-0 small cascade-item" style="--i:10">
                    Already have an account? <a class="link-squir" onclick="showLogin()">Log in</a>
                </p>
            </form>
        </div>

    </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function playCascade(viewEl) {
        const items = viewEl.querySelectorAll('.cascade-item');
        items.forEach(el => {
            el.classList.remove('play');
            void el.offsetWidth; // force reflow para ma-restart yung animation
            el.classList.add('play');
        });
    }

    function showSignup() {
        const loginView = document.getElementById('loginView');
        const signupView = document.getElementById('signupView');

        loginView.classList.remove('visible');
        loginView.classList.add('hidden');

        signupView.classList.remove('hidden');
        signupView.classList.add('visible');

        playCascade(signupView);
    }

    function showLogin() {
        const loginView = document.getElementById('loginView');
        const signupView = document.getElementById('signupView');

        signupView.classList.remove('visible');
        signupView.classList.add('hidden');

        loginView.classList.remove('hidden');
        loginView.classList.add('visible');

        playCascade(loginView);
    }

    function togglePassword(fieldId, iconId) {
        const pw = document.getElementById(fieldId);
        const icon = document.getElementById(iconId);
        if (pw.type === 'password') {
            pw.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            pw.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    // Paglabas ng page, i-play yung cascade animation sa unang view (Login)
    document.addEventListener('DOMContentLoaded', () => {
        playCascade(document.getElementById('loginView'));
    });

    // Kung may validation error mula sa signup submission, panatilihin ang
    // Sign Up view ang nakikita sa halip na bumalik sa Login
    @if ($errors->has('fullname') || $errors->has('terms') || old('fullname'))
        showSignup();
    @endif
</script>
</body>
</html>
