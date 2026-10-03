<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-story" aria-label="Search performance">
            <a class="brand brand-light" href="{{ route('login') }}">
                <span class="brand-mark" aria-hidden="true">360</span>
                <span>360<span class="brand-muted">/</span>SEO</span>
            </a>
            <div class="story-copy">
                <span class="eyebrow eyebrow-light"><span class="status-dot"></span> YOUR SEARCH, IN FOCUS</span>
                <h1>Make every<br>search count.</h1>
                <p>Your monthly SEO performance, brought together in one clear view.</p>
            </div>
            <div class="story-foot"><span>360 Degree Info</span><span>REPORTING SUITE&nbsp; / &nbsp;01</span></div>
        </section>

        <section class="auth-form-side">
            <div class="auth-form-wrap">
                <div class="auth-mobile-brand brand">
                    <span class="brand-mark" aria-hidden="true">360</span>
                    <span>360<span class="brand-muted">/</span>SEO</span>
                </div>
                <span class="eyebrow">ADMINISTRATOR ACCESS</span>
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
                    <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password">

                    <label class="remember-option" for="remember">
                        <input id="remember" name="remember" type="checkbox" value="1">
                        <span>Keep me signed in</span>
                    </label>

                    <button class="button button-primary login-submit" type="submit">
                        <span>Sign in to dashboard</span>
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </form>
                <p class="auth-note">Access is limited to authorized administrators.</p>
            </div>
            <footer class="auth-footer"><span>&copy; {{ date('Y') }} 360 Degree Info</span><span>Private workspace</span></footer>
        </section>
    </main>
</body>
</html>