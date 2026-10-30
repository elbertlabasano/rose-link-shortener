@extends('layouts.app')
@section('title', 'Small links, shared with care')
@section('body-class', 'home-page')
@section('content')
<section class="hero">
    <div class="hero-copy"><p class="eyebrow"><span class="little-line"></span> ROSE BAKESHOP · LINK SHORTENER</p>
        <h1>Good things come<br>in <em>little links.</em></h1>
        <p class="hero-description">From important contracts to everyday updates, make every connection a little simpler. Shorten, share, and manage your links in one place.</p>
        <div class="hero-actions"><a class="button" href="{{ auth()->check() ? route('dashboard') : route('login') }}">{{ auth()->check() ? 'Go to my links' : 'Get started' }} <span aria-hidden="true">→</span></a><span>For the Rose family.<br>With your Rose Account.</span></div>
        <div class="hero-caption"><span class="small-dot"></span> Your destination, just a little shorter.</div>
    </div>
    <div class="hero-art" aria-label="Example of a long contract URL becoming a short link">
        <span class="art-caption">LESS TO TYPE. MORE TO SHARE.</span>
        <div class="sample-long"><span class="sample-label">THE LONG WAY</span><p>esigned.rosebakeshop.net/<br>contract?id=123253252343</p><span class="muted">A lot to remember. A little hard to share.</span></div>
        <div class="art-connector" aria-hidden="true">↓</div>
        <div class="sample-short"><span class="sample-label">THE ROSE WAY</span><span class="sample-link">sh.jathnier.com/<strong>a7k2mx</strong></span><span class="sample-bottom">Same destination. A sweeter connection. <span aria-hidden="true">↗</span></span></div>
        <span class="art-note">A little link goes a long way.</span>
    </div>
</section>
<section class="home-details" aria-label="How it works"><div><span class="step-number">01</span><h2>Make it short.</h2><p>Paste a destination and give your link a name you’ll recognize.</p></div><div><span class="step-number">02</span><h2>Pass it along.</h2><p>Copy a compact link, ready for a message, email, or team update.</p></div><div><span class="step-number">03</span><h2>Keep it in hand.</h2><p>See clicks, update destinations, and decide when a link expires.</p></div></section>
<section class="home-bottom"><p class="script">Connections made a little sweeter.</p><p>One familiar account. All your links together.</p>@include('partials.waves')</section>
@endsection
