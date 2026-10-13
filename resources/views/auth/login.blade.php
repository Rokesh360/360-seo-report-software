<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/360degreeinfo.webp') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-story" aria-label="Search performance">
            <a class="brand brand-light" href="{{ route('login') }}">
                <img src="{{ asset('assets/360degreeinfo.webp') }}" alt="360 Info">
            </a>
            <div class="story-copy">
                <span class="eyebrow eyebrow-light"><span class="status-dot"></span> YOUR SEARCH, IN FOCUS</span>
                <h1>Make every<br>search count.</h1>
                <p>Your monthly SEO performance, brought together in one clear view.</p>
            </div>
            <div class="story-foot"><span>360 Degree Info</span></div>
        </section>

        <section class="auth-form-side">
            <div class="auth-form-wrap">
                <div class="auth-mobile-brand brand">
                    <span class="brand-mark" aria-hidden="true">360</span>
                    <span>360<span class="brand-muted">/</span>SEO</span>
                </div>
                <span class="eyebrow">ADMIN ACCESS</span>
                <h2>Welcome back</h2>
                <p class="auth-intro">Sign in to continue to your report.</p>

                @if ($errors->any())
                <div class="form-alert" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="login-form">
                    @csrf
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="you@company.com">

                    <div class="field-heading">
                        <label for="password">Password</label>
                    </div>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password">
                        <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password" aria-pressed="false">
                            <!-- eye (shown when password is hidden) -->
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <!-- eye-off (shown when password is visible) -->
                            <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6.5 0-10-7-10-7a18.5 18.5 0 0 1 5.06-5.94" />
                                <path d="M9.9 4.24A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19" />
                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        </button>
                    </div>

                    <!-- <label class="remember-option" for="remember">
                        <input id="remember" name="remember" type="checkbox" value="1">
                        <span>Keep me signed in</span>
                    </label> -->

                    <button class="button button-primary login-submit mt-4" type="submit">
                        <span>Sign in to dashboard</span>
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </form>
                <p class="auth-note">Access is limited to authorized administrators.</p>
            </div>
            <footer class="auth-footer"><span>&copy; {{ date('Y') }} 360 Degree Info</span><span>Private workspace</span></footer>
        </section>
    </main>
    <script>
        (function() {
            var input = document.getElementById('password');
            var btn = document.getElementById('togglePassword');
            if (!input || !btn) return;

            btn.addEventListener('click', function() {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-pressed', show);
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                btn.querySelector('.icon-eye').style.display = show ? 'none' : '';
                btn.querySelector('.icon-eye-off').style.display = show ? '' : 'none';
                input.focus();
            });
        })();
    </script>
</body>

</html>