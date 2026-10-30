# Rose Shortener

Private URL management for **sh.jathnier.com**, with public redirects and Rose Account sign-in.

## Run locally

The Laravel application lives at the repository root. Requirements: PHP 8.2+, Composer, and MySQL or MariaDB. Enable PHP's `pdo_mysql` extension. The UI uses Blade, locally hosted fonts, CSS, and vanilla JavaScript; no Node build or frontend server is required.

```powershell
cd C:\apps\jathnier\rose-link-shortener
composer install
Copy-Item .env.example .env # Only on a fresh checkout; preserve an existing .env
php artisan key:generate  # Only on a fresh installation
# Create the MySQL database and application user, then set DB_* in .env.
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8012
```

Open `http://127.0.0.1:8012`. The existing local `.env` is already configured; do not replace it or regenerate its application key.

The local database is **`rose_shortener`** at `127.0.0.1:3306`, using the dedicated `rose_shortener` account. XAMPP provides MariaDB 10.4, accessed through Laravel?s MySQL driver. The generated database password is stored only in the ignored `.env`. The previous `database/database.sqlite` is retained as a backup and is no longer used by the running application. Existing user profiles and SSO identity links were copied across; sign in again after switching databases.

For a fresh setup, create a database named `rose_shortener` with `utf8mb4` / `utf8mb4_unicode_ci`, create a dedicated database user with privileges on that database, and fill in `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` before migrating.

The seeder creates employee `20260211` as an active user. It does not overwrite an existing account or reactivate one that was disabled. Its email is a non-deliverable placeholder, and its password is random. Password sign-in and public registration are not exposed. All active, locally provisioned users can manage their own links and API tokens; no other accounts are created automatically through SSO.

## Rose Account SSO

Local environment settings:

```dotenv
SSO_ENABLED=true
KEYCLOAK_BASE_URL=https://sso.rosebakeshop.net
KEYCLOAK_REALM=jathnier
KEYCLOAK_CLIENT_ID=shortener
KEYCLOAK_CLIENT_SECRET=your-client-secret
KEYCLOAK_REDIRECT_URI=http://127.0.0.1:8012/auth/keycloak/callback
SESSION_COOKIE=rose_shortener_session
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
```

Store the actual client secret only in `.env` or the deployment secret manager. `.env` is ignored by Git.

Configure Keycloak client **shortener** as a confidential OpenID Connect client: client authentication On, Standard flow On, implicit flow Off, direct access grants Off. Use exact allowed callbacks:

- `http://127.0.0.1:8012/auth/keycloak/callback`
- `https://sh.jathnier.com/auth/keycloak/callback`

Enable S256 PKCE. Add an `idcode` user-attribute mapper returning a single **string** in UserInfo. The employee's attribute must be `20260211` for the seeded account. Employees must not be able to edit this managed attribute. No browser Web origins or post-logout redirect URI is required for this server-side flow.

The application uses authorization code flow with session state and PKCE, requests UserInfo over TLS, matches an active local account by exact `idcode`, and binds issuer plus subject. It rejects unknown, inactive, malformed, or conflicting accounts. It does not provision users, silently relink identities, or replace local email addresses from unverified claims.

Logout uses a CSRF-protected local POST and leaves the central Rose SSO session active. Returning to the login page does not automatically launch SSO. Central Keycloak logout/account disabling does not immediately revoke existing Laravel sessions or application API tokens. Disable the local account to deny subsequent protected requests. Keycloak backchannel logout is not implemented.

The public issuer/discovery endpoint was verified. A real employee browser sign-in remains necessary to validate the client secret, client settings, UserInfo mapper, and production proxy/cookies together.

## API

Sign in, open **API access**, and create a token for your application. It is shown once, stored hashed, scoped to `links:manage`, expires in one year, and can be revoked. API requests require:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
Content-Type: application/json
```

| Method | Endpoint | Behavior |
| --- | --- | --- |
| POST | `/api/links` | Create a short link |
| GET | `/api/links?q=contract&status=active` | Paginated own links, 15 per page |
| GET | `/api/links/{id}` | Get an owned link |
| PATCH | `/api/links/{id}` | Update title, destination, expiry, or active status |
| DELETE | `/api/links/{id}` | Disable a link, retaining its code |
| GET | `/{code}` | Public 302 redirect |

Example create body:

```json
{
  "url": "https://esigned.rosebakeshop.net/contract?id=123253252343",
  "title": "Contract for review",
  "alias": "contract-review",
  "expires_at": "2026-12-31T23:59:00+08:00"
}
```

Only `url` is required. Without an alias, a random six-character lowercase alphanumeric code is allocated with database uniqueness and collision retries. An alias is 3–40 lowercase letters/numbers/hyphens/underscores, starts with a letter or number, and cannot use application paths. Codes are immutable after creation. Repeating a destination without an alias or expiry reuses the current user's active, non-expiring link; the existing link's title is preserved. This is sequential reuse, not an idempotency guarantee for simultaneous requests.

Created responses use HTTP 201 and `{ "data": { "id": 1, "short_url": "https://sh.jathnier.com/abc123", ... } }`. Reused links return 200. API list responses use Laravel pagination (`data`, `current_page`, `last_page`, etc.). Invalid input returns 422; unauthenticated API access returns 401; missing permissions return 403. Requests are limited to 120 per minute per user.

Set `expires_at` to null to remove expiry; use `is_active: false` to disable or `true` to enable. DELETE retains the record and reserves its code so a previously shared contract URL cannot be reassigned. Disabled and expired links return 410; unknown links return 404. Destination URLs retain their query strings; only HTTP/HTTPS URLs without embedded credentials are allowed. The shortener does not fetch destination content.

Counts record successful GET redirects, including automated link previews/bots; HEAD requests do not count. They are not unique-person analytics. No visitor IP or user-agent history is stored. The destination application remains responsible for contract authentication and authorization.

## Deploy to sh.jathnier.com

Point DNS and your HTTPS web server at the deployment server. Set the document root to **`public`**, never the repository root. Keep `storage` and `bootstrap/cache` writable by PHP. Route requests through `public/index.php` (the Laravel Apache `.htaccess` is included).

Production environment changes:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sh.jathnier.com
SHORT_URL_BASE=https://sh.jathnier.com
KEYCLOAK_REDIRECT_URI=https://sh.jathnier.com/auth/keycloak/callback
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=rose_shortener_session
SESSION_SAME_SITE=lax
```

Use a stable `APP_KEY`, a persistent database, and host-only session cookies. Set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for the production database. The local application already uses MySQL-compatible MariaDB; its credentials should be replaced with the deployment server?s database credentials. If using a reverse proxy, configure Laravel to trust only your actual proxy so it recognizes HTTPS.

```powershell
cd C:\apps\jathnier\rose-link-shortener
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

DNS, server configuration, and Keycloak administration have not been changed by this implementation. Before rollout, complete real SSO sign-in and verify that a generated production short URL redirects correctly over HTTPS.

### Cloudflare Tunnel on Windows

Publish `sh.jathnier.com` to `http://127.0.0.1:8012`. The application trusts forwarded headers only from the loopback connection used by the tunnel, and the origin server binds only to loopback.

Install the origin as a Windows startup task from an elevated PowerShell window:

```powershell
cd D:\productions\rose-link-shortener
.\scripts\install-startup-task.ps1
```

The task is named **Rose Link Shortener**, restarts on failure, and starts immediately when installed. When the installer is elevated it runs as `SYSTEM` at computer startup. Without elevation, it installs a user-level task that runs at Windows sign-in. Cloudflared must also be installed as a Windows service or otherwise configured to start automatically.

## Verification

```powershell
cd C:\apps\jathnier\rose-link-shortener
php artisan test
php vendor/bin/pint --test app bootstrap/app.php config database routes tests
```

The 37-test suite runs only against SQLite in memory. MySQL (including databases named as test databases) and file-based SQLite connections are intentionally blocked during automated testing. Tests cover employee seeding, account linking/denials, real authorization URL state/PKCE, invalid callback state, ownership, URL validation, destination preservation, expiry, click counting, API permissions, and token controls. Local Chrome checks also cover desktop/mobile pages, form submission, clipboard feedback, edits, disabled redirects, token creation/revocation, and local logout.

## Database safety safeguards

`phpunit.xml` forces `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, and an empty `DB_URL`, overriding inherited shell database settings.

During testing, `AppServiceProvider` checks the default connection before Laravel?s test traits run. A guarded connection factory also checks every Laravel-created database connection before creating PDO, so selecting a secondary MySQL connection or changing the default during a test is rejected. URL and read/write overrides are checked too. PHPUnit detection remains active even if cached configuration says the environment is local or production.

`RefreshDatabase` is permitted only on SQLite `:memory:`. No persistent database is allowlisted, and there is no environment switch to bypass this restriction. The earlier one-off MariaDB test configuration is no longer permitted. Run tests with `php artisan test`. If cached configuration triggers the safety error, run `php artisan config:clear` from the project root and retry; do not disable the guard.

Outside tests, Laravel?s destructive commands `db:wipe`, `migrate:fresh`, `migrate:refresh`, `migrate:reset`, and `migrate:rollback` are prohibited, including with `--force`. Normal `migrate` and seeding remain available. These safeguards protect Laravel-managed connections and commands; they do not prevent raw PDO, external database clients, or deliberately destructive custom migration/SQL code. Review such code and keep database backups.

Verification covers unsafe default/secondary connections, a SQLite file, URL and read/write overrides, and an actual misconfigured PHPUnit subprocess. Application record snapshots were unchanged after the blocked subprocess. No destructive command was executed against the application database.

## Brand assets

The logo was extracted from the supplied **Rose Bakeshop Brand Book.pdf**. Colors: Rose Red `#E31B17`, Deep Burgundy `#5D1616`, Cream `#FFEDDC`, Honey Gold `#FFC765`, Pastel Pink `#FE97B9`, and Pastel Lavender `#8F91D4`. Courgette headings, Outfit interface typography, and flowing waves follow the book. The fonts are hosted locally; their OFL license notices are included in `public/brand/`.

SSO implementation references: [Keycloak Socialite provider](https://socialiteproviders.com/Keycloak/), [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum), and the supplied Rose SSO guide at `C:\apps\jathnier\rose-doc\sso.md`.
