@extends('layouts.app')
@section('title', 'Link not found')
@section('content')<section class="error-page"><p class="eyebrow">A LITTLE DETOUR</p><h1>We couldn’t find<br><em>that link.</em></h1><p>Check the address or ask the sender to share the link again.</p><a class="button" href="{{ route('home') }}">Back to home →</a></section>@endsection
