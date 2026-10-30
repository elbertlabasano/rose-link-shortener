@extends('layouts.app')
@section('title', 'API access')
@section('content')
<div class="workspace"><div class="page-heading"><div><p class="eyebrow">CONNECT YOUR APPLICATIONS</p><h1>API access<span class="red-period">.</span></h1><p class="muted">Create short links from eSigned or your other applications.</p></div></div>@include('partials.alerts')
@if(session('new_token'))<div class="notice"><strong>Copy your new token now. It will only be shown once.</strong><div class="created-link"><code>{{ session('new_token') }}</code><button class="button button-small" data-copy="{{ session('new_token') }}">Copy token</button></div></div>@endif
<section class="panel"><h2>Create an API token</h2><p class="muted">Tokens manage your links and expire after one year. Revoke a token when an integration no longer needs access.</p><form class="token-form" method="post" action="{{ route('tokens.store') }}">@csrf<div><label for="name">Application name</label><input id="name" name="name" maxlength="80" required placeholder="e.g. eSigned production"></div><button class="button">Create token <span aria-hidden="true">＋</span></button></form></section>
<section class="panel"><h2>Your tokens</h2><div class="table-scroll"><table><thead><tr><th>Application</th><th>Last used</th><th>Expires</th><th>Action</th></tr></thead><tbody>@forelse($tokens as $token)<tr><td>{{ $token->name }}</td><td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td><td>{{ $token->expires_at?->format('M j, Y') ?? 'No expiry' }}</td><td><form action="{{ route('tokens.destroy', $token->id) }}" method="post" data-confirm="Revoke this token? Its application will immediately lose API access.">@csrf @method('DELETE')<button class="text-link danger">Revoke</button></form></td></tr>@empty<tr><td colspan="4" class="empty-state">No API tokens yet. Create one for your first integration.</td></tr>@endforelse</tbody></table></div></section>
<section class="panel api-example"><p class="eyebrow">YOUR FIRST REQUEST</p><h2>A shorter link, from your app.</h2><pre><code>POST https://sh.jathnier.com/api/links
Authorization: Bearer YOUR_TOKEN
Accept: application/json
Content-Type: application/json

{
  "url": "https://esigned.rosebakeshop.net/contract?id=123253252343",
  "title": "Contract for review"
}</code></pre><p class="muted">The response includes <code>data.short_url</code>. Keep tokens on your server.</p></section></div>
@endsection
