<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use SocialiteProviders\Keycloak\Provider;
use SocialiteProviders\Manager\OAuth2\User as RemoteUser;
use Tests\TestCase;

class SsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sso.enabled' => true, 'services.keycloak.client_id' => 'shortener', 'services.keycloak.client_secret' => 'test-secret',
            'services.keycloak.redirect' => 'http://127.0.0.1:8008/auth/keycloak/callback']);
    }

    private function remote(mixed $code = '20260211', string $subject = 'employee-subject'): void
    {
        $remote = (new RemoteUser)->setRaw(['sub' => $subject, 'idcode' => $code])->map(['id' => $subject, 'email' => 'unverified@example.org']);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('enablePKCE')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($remote);
        Socialite::shouldReceive('driver')->with('keycloak')->once()->andReturn($provider);
    }

    public function test_real_authorization_redirect_has_state_and_s256_pkce(): void
    {
        $response = $this->get('/auth/keycloak/redirect')->assertRedirect();
        $url = $response->headers->get('Location');
        $this->assertStringStartsWith('https://sso.rosebakeshop.net/realms/jathnier/protocol/openid-connect/auth?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('shortener', $query['client_id']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotEmpty($query['state']);
        $this->assertNotEmpty($query['code_challenge']);
        $this->assertArrayNotHasKey('prompt', $query);
    }

    public function test_eligible_employee_is_linked_without_changing_profile(): void
    {
        $user = User::factory()->create(['idcode' => '20260211', 'is_active' => true]);
        $email = $user->email;
        $this->remote();
        $this->get('/auth/keycloak/callback?code=example')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('employee-subject', $user->fresh()->keycloak_subject);
        $this->assertSame($email, $user->fresh()->email);
        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/')->assertOk()->assertSee('Go to my links');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_unknown_employee_does_not_create_an_account(): void
    {
        $this->remote();
        $this->get('/auth/keycloak/callback?code=example')->assertForbidden();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_array_employee_claim_is_rejected(): void
    {
        $this->remote(['20260211']);
        $this->get('/auth/keycloak/callback?code=example')->assertForbidden();
        $this->assertGuest();
    }

    public function test_inactive_employee_cannot_sign_in(): void
    {
        User::factory()->create(['idcode' => '20260211', 'is_active' => false]);
        $this->remote();
        $this->get('/auth/keycloak/callback?code=example')->assertForbidden();
        $this->assertGuest();
    }

    public function test_conflicting_identity_is_not_silently_relinked(): void
    {
        $user = User::factory()->create(['idcode' => '20260211', 'is_active' => true, 'keycloak_issuer' => 'https://sso.rosebakeshop.net/realms/jathnier', 'keycloak_subject' => 'original-subject']);
        $this->remote();
        $this->get('/auth/keycloak/callback?code=example')->assertForbidden();
        $this->assertSame('original-subject', $user->fresh()->keycloak_subject);
        $this->assertGuest();
    }

    public function test_callback_without_valid_state_is_rejected(): void
    {
        $this->get('/auth/keycloak/callback?code=example&state=forged')->assertRedirect('/login')->assertSessionHasErrors('sso');
        $this->assertGuest();
    }

    public function test_disabled_sso_has_no_login_entry_point(): void
    {
        config(['sso.enabled' => false]);
        $this->get('/auth/keycloak/redirect')->assertNotFound();
        $this->get('/login')->assertDontSee('Sign in with Rose Account');
    }

    public function test_missing_employee_code_is_rejected(): void
    {
        $this->remote(null);
        $this->get('/auth/keycloak/callback?code=example')->assertForbidden();
        $this->assertGuest();
    }

    public function test_employee_code_with_leading_zeros_is_preserved(): void
    {
        $user = User::factory()->create(['idcode' => '001234', 'is_active' => true]);
        $this->remote('001234');
        $this->get('/auth/keycloak/callback?code=example')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('001234', $user->fresh()->idcode);
    }

    public function test_changed_code_on_existing_identity_is_rejected(): void
    {
        User::factory()->create(['idcode' => 'old-code', 'is_active' => true,
            'keycloak_issuer' => 'https://sso.rosebakeshop.net/realms/jathnier', 'keycloak_subject' => 'employee-subject']);
        $this->remote();
        $this->get('/auth/keycloak/callback?code=example')->assertForbidden();
        $this->assertGuest();
    }
}
