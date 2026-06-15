# Auth & admin

There is exactly **one** way to authenticate (admin login) and **one** way to authorize (the `is_admin` boolean). No registration, no password reset, no role hierarchy.

## Login flow

`app/Livewire/Auth/Login.php`. Properties:

```php
#[Validate('required|email')] public string $email = '';
#[Validate('required|string')] public string $password = '';
public bool $remember = false;
```

`submit()` (lines 26-54):

1. Validate inputs.
2. Build a rate-limit key: `'login:'.strtolower($email).'|'.request()->ip()`.
3. If `RateLimiter::tooManyAttempts($key, 5)` → throw `ValidationException` mentioning the wait time.
4. Otherwise `Auth::attempt(['email' => …, 'password' => …], $this->remember)`.
5. On failure: `RateLimiter::hit($key, 60)` (1-minute decay), throw with "These credentials do not match our records."
6. On success: clear the limiter, `request()->session()->regenerate()` (session fixation defense), `$this->redirect(route('admin.dashboard'), navigate: false)`.

`navigate: false` forces a full page reload after login — required because we're transitioning out of the public/cached scope into admin-only routes that should not be served from any cache.

## Admin gate

`app/Http/Middleware/EnsureUserIsAdmin.php`:

```php
public function handle(Request $request, Closure $next): Response
{
    if (! $request->user()?->is_admin) {
        abort(403);
    }
    return $next($request);
}
```

Aliased as `admin` in `bootstrap/app.php:18`. Applied to the admin route group together with `auth`:

```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () { … });
```

Test coverage (`tests/Feature/AdminAccessTest.php`):
- Guest → redirected to `/login` for every admin path.
- Authenticated non-admin → 403.
- Admin → 200.

## Logout

`routes/web.php:36-42` — inline closure:

```php
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
```

Triggered by the form-wrapped button in `resources/views/components/admin-shell.blade.php:16-19`.

## Seeded admin

`database/seeders/AdminUserSeeder.php`:

- Reads `ADMIN_EMAIL` and `ADMIN_PASSWORD` from `.env` — both required, no defaults.
- Throws `RuntimeException` if either is missing.
- `updateOrCreate(['email' => $email], ['name' => 'Admin', 'password' => Hash::make($password), 'is_admin' => true, 'email_verified_at' => now()])`.

Run via the seeder chain in `DatabaseSeeder.php`:

```php
$this->call([AdminUserSeeder::class]);
```

`AdminUserSeeder` is the only seeder shipped with the project. Tags and prompts are created exclusively through the admin UI.

Local default in `.env.example:67-69`:

```env
ADMIN_EMAIL=admin@example.test
ADMIN_PASSWORD=password
```

## CSRF

- The global layout (`resources/views/components/layouts/app.blade.php:7`) sets `<meta name="csrf-token" content="...">`.
- Livewire embeds its own CSRF token in each page payload; this is what enforces the `Vary: Cookie` in the public cache middleware (see [06-domain-logic.md](./06-domain-logic.md#http-caching)).
- POST endpoints (only `/logout`) require the token — the form renders it via `@csrf`.

## Session storage

`SESSION_DRIVER=database` (`.env.example:30`). The `sessions` table comes from the default Laravel user-table migration.

## What's intentionally missing

- **No password reset.** If the admin forgets their password, reset it via tinker:

  ```bash
  php artisan tinker
  >>> User::where('email','admin@example.test')->first()->update(['password' => Hash::make('newpass')]);
  ```

- **No multi-user / roles.** `is_admin` is a single boolean. Adding a second admin works (re-run the seeder with different env vars, or insert manually), but there's no UI for it.

- **No email verification flow / "remember me" cookie cleanup beyond Laravel defaults.**

- **No two-factor auth.**
