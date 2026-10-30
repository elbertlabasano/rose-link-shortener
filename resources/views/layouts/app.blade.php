<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Rose Shortener') · Rose Bakeshop</title>
    <link rel="icon" href="{{ asset('brand/rose-logo.png') }}">
    <link rel="preload" href="{{ asset('brand/outfit.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="{{ asset('app.css') }}">
    <script src="{{ asset('app.js') }}" defer></script>
</head>
<body class="@yield('body-class')">
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
    <a class="brand" href="{{ route('home') }}" aria-label="Rose Shortener home">
        <img src="{{ asset('brand/rose-logo.png') }}" width="80" height="58" alt="Rose Bakeshop">
        <span class="brand-divider"></span><span>Shortener<small>A little link. A lasting connection.</small></span>
    </a>
    <nav aria-label="Main navigation">
        @auth
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'selected' : '' }}" href="{{ route('dashboard') }}">My links</a>
            <a class="nav-link {{ request()->routeIs('tokens.*') ? 'selected' : '' }}" href="{{ route('tokens.index') }}">API access</a>
            <form action="{{ route('logout') }}" method="post">@csrf<button class="button button-outline button-small">Sign out <span aria-hidden="true">↗</span></button></form>
        @else
            <span class="header-note">Made for the Rose family</span>
            <a class="button button-small" href="{{ route('login') }}">Sign in <span aria-hidden="true">→</span></a>
        @endauth
    </nav>
</header>
<main id="main">@yield('content')</main>
<footer class="site-footer"><span>© {{ date('Y') }} Rose Bakeshop</span><span>Small links. Shared with care.</span><a href="{{ route('home') }}">sh.jathnier.com <span aria-hidden="true">↗</span></a></footer>
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
</body>
</html>
