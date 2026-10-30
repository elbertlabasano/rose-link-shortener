@extends('layouts.app')
@section('title', 'Edit link')
@section('content')
<div class="workspace narrow"><a class="text-link" href="{{ route('dashboard') }}">← Back to my links</a><div class="page-heading"><div><p class="eyebrow">A LITTLE UPDATE</p><h1>Edit link<span class="red-period">.</span></h1><p class="muted">{{ $link->short_url }}</p></div></div>@include('partials.alerts')
<section class="panel"><form method="post" action="{{ route('links.update', $link) }}" class="stack-form">@csrf @method('PATCH')
<div><label for="title">Title</label><input id="title" name="title" maxlength="160" value="{{ old('title', $link->title) }}"></div>
<div><label for="url">Destination URL</label><input id="url" name="url" type="url" maxlength="8192" required value="{{ old('url', $link->url) }}"></div>
<div><label for="expires_at">Expiry · Asia/Manila</label><input id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at', $link->expires_at?->format('Y-m-d\TH:i')) }}"><small>Clear this field to remove the expiry.</small></div>
<div><label for="is_active">Status</label><select name="is_active" id="is_active"><option value="1" @selected(old('is_active', $link->is_active) == 1)>Active</option><option value="0" @selected(old('is_active', $link->is_active) == 0)>Disabled</option></select></div>
<div class="form-actions"><button class="button">Save changes <span aria-hidden="true">→</span></button><a class="text-link" href="{{ route('dashboard') }}">Cancel</a></div>
</form></section></div>
@endsection
