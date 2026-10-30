@if (config('sso.enabled'))
    <a class="sso-button" href="{{ route('sso.redirect') }}"><img src="{{ asset('brand/rose-logo.png') }}" width="44" height="32" alt=""><span>Sign in with Rose Account</span><span class="sso-arrow" aria-hidden="true">→</span></a>
@else
    <div class="notice">Rose Account sign-in is being configured. Please check back shortly.</div>
@endif
