@extends('layouts.app')
@section('title', 'Link unavailable')
@section('content')<section class="error-page"><p class="eyebrow">THIS CONNECTION HAS ENDED</p><h1>This link has<br><em>run its course.</em></h1><p>The link has expired or been disabled. Please ask the sender for a new link.</p><a class="button" href="{{ route('home') }}">Back to home →</a></section>@endsection
