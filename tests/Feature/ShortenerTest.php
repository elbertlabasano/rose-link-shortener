<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShortenerTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    public function test_home_and_login_are_public_and_dashboard_requires_login(): void
    {
        config(['sso.enabled' => true]);
        $this->get('/')->assertOk()->assertSee('little links.');
        $this->get('/login')->assertOk()->assertSee('Sign in with Rose Account');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->getJson('/api/links')->assertUnauthorized();
    }

    public function test_seed_is_repeatable_and_employee_code_is_a_string(): void
    {
        $this->seed(DatabaseSeeder::class);
        $password = User::where('idcode', '20260211')->firstOrFail()->password;
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(1, User::where('idcode', '20260211')->count());
        $user = User::where('idcode', '20260211')->firstOrFail();
        $this->assertSame('20260211', $user->idcode);
        $this->assertSame($password, $user->password);
        $this->assertTrue($user->is_active);
        $other = User::factory()->create(['idcode' => '001234']);
        $this->assertSame('001234', $other->fresh()->idcode);
    }

    public function test_web_form_creates_a_link_and_returns_to_dashboard(): void
    {
        $this->actingAs($this->employee())->post(route('links.store'), [
            'url' => 'https://esigned.rosebakeshop.net/contract?id=123253252343',
            'title' => 'Contract',
        ])->assertRedirect(route('dashboard'))->assertSessionHas('short_url');
        $this->get('/dashboard')->assertOk()->assertSee('Contract')->assertSee('1 link');
        $this->assertDatabaseCount('links', 1);
    }

    public function test_api_creates_and_reuses_link_and_redirect_preserves_query(): void
    {
        Sanctum::actingAs($this->employee(), ['links:manage']);
        $url = 'https://esigned.rosebakeshop.net/contract?id=123253252343&view=sign';
        $response = $this->postJson('/api/links', ['url' => $url])->assertCreated();
        $code = $response->json('data.code');
        $this->assertMatchesRegularExpression('/^[a-z0-9]{6}$/', $code);
        $this->assertSame('https://sh.jathnier.com/'.$code, $response->json('data.short_url'));
        $this->postJson('/api/links', ['url' => $url])->assertOk()->assertJsonPath('data.code', $code);
        $this->get('/'.$code)->assertRedirect($url)->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertDatabaseHas('links', ['code' => $code, 'clicks' => 1]);
        $this->head('/'.$code)->assertRedirect($url);
        $this->assertSame(1, Link::first()->clicks);
    }

    public function test_alias_validation_and_unsafe_destinations(): void
    {
        Sanctum::actingAs($this->employee(), ['links:manage']);
        foreach (['dashboard', 'login', 'api', 'Bad Alias', '../foo', 'up'] as $alias) {
            $this->postJson('/api/links', ['url' => 'https://example.com', 'alias' => $alias])->assertUnprocessable()->assertJsonValidationErrors('alias');
        }
        foreach (['javascript:alert(1)', 'ftp://example.com', 'https://user:pass@example.com', 'https://sh.jathnier.com/abc123'] as $url) {
            $this->postJson('/api/links', ['url' => $url])->assertUnprocessable()->assertJsonValidationErrors('url');
        }
        $this->postJson('/api/links', ['url' => 'https://example.com', 'alias' => 'contract-123'])->assertCreated();
        $this->postJson('/api/links', ['url' => 'https://example.org', 'alias' => 'contract-123'])->assertUnprocessable();
    }

    public function test_disabled_and_expired_links_return_gone_without_counting_clicks(): void
    {
        Sanctum::actingAs($this->employee(), ['links:manage']);
        $this->postJson('/api/links', ['url' => 'https://example.com', 'alias' => 'contract'])->assertCreated();
        $link = Link::first();
        $this->deleteJson('/api/links/'.$link->id)->assertNoContent();
        $this->get('/contract')->assertGone()->assertSee('run its course.');
        $this->assertSame(0, $link->fresh()->clicks);
        $this->assertDatabaseHas('links', ['code' => 'contract']);
        $link->update(['is_active' => true, 'expires_at' => now()->subMinute()]);
        $this->get('/contract')->assertGone();
        $this->get('/missing')->assertNotFound();
    }

    public function test_users_cannot_read_edit_or_disable_another_users_links(): void
    {
        Sanctum::actingAs($this->employee(), ['links:manage']);
        $this->postJson('/api/links', ['url' => 'https://example.com'])->assertCreated();
        $id = Link::first()->id;
        Sanctum::actingAs($this->employee(), ['links:manage']);
        $this->getJson('/api/links')->assertJsonCount(0, 'data');
        $this->getJson('/api/links/'.$id)->assertNotFound();
        $this->patchJson('/api/links/'.$id, ['url' => 'https://example.org'])->assertNotFound();
        $this->deleteJson('/api/links/'.$id)->assertNotFound();
    }

    public function test_updates_search_expiry_validation_and_immutable_alias(): void
    {
        Sanctum::actingAs($this->employee(), ['links:manage']);
        $this->postJson('/api/links', ['url' => 'https://example.com', 'alias' => 'sample'])->assertCreated();
        $id = Link::first()->id;
        $this->patchJson('/api/links/'.$id, ['title' => 'Updated contract', 'url' => 'https://example.org/new?id=1', 'expires_at' => now()->addDay()->toIso8601String()])->assertOk();
        $this->getJson('/api/links?q=Updated&status=active')->assertJsonCount(1, 'data');
        $this->getJson('/api/links?q=missing')->assertJsonCount(0, 'data');
        $this->patchJson('/api/links/'.$id, ['alias' => 'renamed'])->assertUnprocessable();
        $this->patchJson('/api/links/'.$id, ['expires_at' => now()->subDay()->toIso8601String()])->assertUnprocessable();
        $this->patchJson('/api/links/'.$id, ['expires_at' => null])->assertOk()->assertJsonPath('data.expires_at', null);
        $this->get('/sample')->assertRedirect('https://example.org/new?id=1');
    }

    public function test_inactive_users_and_wrong_token_abilities_are_denied(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['links:manage']);
        $this->getJson('/api/links')->assertForbidden();
        Sanctum::actingAs($this->employee(), ['unrelated']);
        $this->getJson('/api/links')->assertForbidden();
    }

    public function test_api_token_creation_and_owner_only_revocation(): void
    {
        $user = $this->employee();
        $this->actingAs($user)->get('/dashboard/tokens')->assertOk();
        $this->post('/dashboard/tokens', ['name' => 'eSigned'])->assertRedirect('/dashboard/tokens')->assertSessionHas('new_token');
        $token = $user->tokens()->firstOrFail();
        $this->assertTrue($token->can('links:manage'));
        $this->assertNotNull($token->expires_at);
        $this->actingAs($this->employee())->delete('/dashboard/tokens/'.$token->id)->assertNotFound();
        $this->actingAs($user)->delete('/dashboard/tokens/'.$token->id)->assertRedirect();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_seeding_does_not_reactivate_a_disabled_employee(): void
    {
        $this->seed(DatabaseSeeder::class);
        User::where('idcode', '20260211')->update(['is_active' => false]);
        $this->seed(DatabaseSeeder::class);
        $this->assertFalse(User::where('idcode', '20260211')->firstOrFail()->is_active);
    }

    public function test_non_string_destination_returns_validation_error(): void
    {
        Sanctum::actingAs($this->employee(), ['links:manage']);
        $this->postJson('/api/links', ['url' => ['https://example.com']])->assertUnprocessable()->assertJsonValidationErrors('url');
    }

    public function test_real_bearer_token_is_rejected_after_revocation(): void
    {
        $user = $this->employee();
        $token = $user->createToken('Integration', ['links:manage'], now()->addYear());
        $this->withToken($token->plainTextToken)->getJson('/api/links')->assertOk();
        $token->accessToken->delete();
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)->getJson('/api/links')->assertUnauthorized();
    }
}
