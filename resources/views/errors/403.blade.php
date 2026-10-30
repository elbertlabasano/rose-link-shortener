@extends('layouts.app')
@section('title', 'Access unavailable')
@section('content')<section class="error-page"><p class="eyebrow">ROSE ACCOUNT ACCESS</p><h1>A little help<br><em>getting in.</em></h1><p>Your account is not authorized for this application. Ask your administrator to check your employee ID code and account status.</p><a class="button" href="{{ route('login') }}">Back to sign in →</a>@auth<form action="{{ route('logout') }}" method="post">@csrf<button class="text-link">Sign out</button></form>@endauth</section>@endsection
