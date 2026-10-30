@if ($errors->any())
    <div class="notice notice-error" role="alert"><strong>Please check the following:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if (session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if (session('short_url'))<div class="created-link"><code>{{ session('short_url') }}</code><button class="button button-small" data-copy="{{ session('short_url') }}">Copy link</button></div>@endif
