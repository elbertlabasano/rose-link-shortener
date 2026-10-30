<?php

namespace App\Providers;

use App\Support\SafeTestConnectionFactory;
use App\Support\TestDatabaseSafety;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Keycloak\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (TestDatabaseSafety::isTestProcess($this->app)) {
            $this->app->singleton('db.factory', fn ($app) => new SafeTestConnectionFactory($app));

            $name = $this->app['config']->get('database.default');
            TestDatabaseSafety::assertSafe($this->app['config']->get("database.connections.{$name}", []));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // --force does not bypass Laravel's destructive-command prohibition.
        DB::prohibitDestructiveCommands(! TestDatabaseSafety::isTestProcess($this->app));

        Event::listen(
            SocialiteWasCalled::class,
            fn ($event) => $event->extendSocialite('keycloak', Provider::class),
        );

        RateLimiter::for('api', fn ($request) => Limit::perMinute(120)->by($request->user()?->id ?? $request->ip())
        );
    }
}
