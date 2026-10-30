@extends('layouts.app')
@section('title', 'Sign in')
@section('body-class', 'login-page')
@section('content')
<section class="login-shell">
    <div class="login-story"><p class="eyebrow">A LITTLE LINK. A LASTING CONNECTION.</p><h1>Welcome to<br>the <em>Rose family.</em></h1><p>Your everyday links, all in one familiar place.<br>Made simple. Shared with care.</p><img class="login-logo" src="{{ asset('brand/rose-logo.png') }}" alt="Rose Bakeshop, since 1983" width="160" height="115">@include('partials.waves')</div>
    <div class="login-form"><a class="text-link" href="{{ route('home') }}">← Back to home</a><p class="eyebrow">ROSE SHORTENER</p><h2>A familiar way in.</h2><p class="muted">Sign in with your Rose Account to manage your links.</p>@include('partials.alerts')
    @include('partials.sso')
    <p class="login-help">Use the same account you use for other Rose applications. Access is available to authorized team members.</p><div class="login-divider"></div><p class="login-support">Having trouble signing in?<br><strong>Contact your system administrator.</strong></p></div>
</section>
@endsection
