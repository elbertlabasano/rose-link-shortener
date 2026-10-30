<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class KeycloakController extends Controller
{
    public function redirect()
    {
        abort_unless(config('sso.enabled'), 404);

        return Socialite::driver('keycloak')->scopes(['openid', 'profile', 'email'])->enablePKCE()->redirect();
    }

    public function callback(Request $request)
    {
        abort_unless(config('sso.enabled'), 404);
        try {
            $remote = Socialite::driver('keycloak')->enablePKCE()->user();
        } catch (Throwable $error) {
            Log::warning('SSO callback failed', ['exception_type' => get_class($error)]);

            return to_route('login')->withErrors(['sso' => 'Sign-in could not complete. Please try again.']);
        }

        $code = $remote->getRaw()[config('sso.idcode_claim')] ?? null;
        $subject = $remote->getId();
        $issuer = rtrim(config('services.keycloak.base_url'), '/').'/realms/'.config('services.keycloak.realms');
        abort_unless(is_string($code) && $code !== '' && trim($code) === $code
            && is_string($subject) && $subject !== '', 403, 'Your Rose account needs a valid employee ID code.');

        $user = DB::transaction(function () use ($issuer, $subject, $code) {
            $user = User::where('keycloak_issuer', $issuer)->where('keycloak_subject', $subject)->lockForUpdate()->first();
            if (! $user) {
                $user = User::where('idcode', $code)->lockForUpdate()->first();
                abort_unless($user && $user->keycloak_issuer === null && $user->keycloak_subject === null,
                    403, 'Your account is not authorized. Contact your administrator.');
            }
            abort_unless($user->is_active && $user->idcode === $code, 403, 'Your account is not authorized.');
            if ($user->keycloak_subject === null) {
                $user->forceFill(['keycloak_issuer' => $issuer, 'keycloak_subject' => $subject])->save();
            }

            // Do not automatically replace local profile fields or trust unverified email claims.
            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('status', 'You have signed out of Shortener.');
    }
}
