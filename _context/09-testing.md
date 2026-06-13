# Testing

Run with `php artisan test` (or `composer test`). The suite is feature-level — no Unit tests exist yet.

## Setup

[`phpunit.xml`](../phpunit.xml) overrides env for the test run:

```xml
<env name="APP_ENV" value="testing"/>
<env name="BCRYPT_ROUNDS" value="4"/>            <!-- fast hashing -->
<env name="CACHE_STORE" value="array"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>       <!-- no MySQL contact -->
<env name="MAIL_MAILER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
```

In-memory SQLite means migrations run per test class (no `.sqlite` file to clean), and the database is gone the instant the process exits.

Every test uses:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class FooTest extends TestCase
{
    use RefreshDatabase;
    …
}
```

`RefreshDatabase` migrates fresh per test, so tests share no state.

## Test files

| File | Covers |
| ---- | ------ |
| [`tests/Feature/PublicScopeTest.php`](../tests/Feature/PublicScopeTest.php) | Every public page hides `is_public = false` prompts. Private detail URL 404s. |
| [`tests/Feature/SearchTest.php`](../tests/Feature/SearchTest.php) | Title matches; tag matches; body never matches; `?q=` round-trips. |
| [`tests/Feature/ViewTrackingTest.php`](../tests/Feature/ViewTrackingTest.php) | One row per qualifying visit; 30-second dedupe per `(prompt_id, visitor_hash)`; window expires; private 404 doesn't record. |
| [`tests/Feature/AggregateViewsTest.php`](../tests/Feature/AggregateViewsTest.php) | `prompts:aggregate-views` correctness and idempotency. |
| [`tests/Feature/AdminAccessTest.php`](../tests/Feature/AdminAccessTest.php) | Guest redirect; non-admin 403; admin CRUD; `user_id` set from `auth()->id()`; no tag delete route. |

`tests/TestCase.php` is the bare default (no shared setup beyond `CreatesApplication`).

## Factory helpers

### `UserFactory` ([`database/factories/UserFactory.php`](../database/factories/UserFactory.php))

```php
User::factory()->create();           // ordinary user, is_admin = false (default)
User::factory()->admin()->create();  // is_admin = true
User::factory()->unverified()->create();
```

Password hash is computed once and cached in a static — runs ~once across the whole suite.

### `PromptFactory` ([`database/factories/PromptFactory.php`](../database/factories/PromptFactory.php))

```php
Prompt::factory()->for($admin, 'user')->create();          // mixed visibility (80% public)
Prompt::factory()->public()->for($admin, 'user')->create();
Prompt::factory()->private()->for($admin, 'user')->create();
```

You must always pass the `user`. If you forget, the factory's default `user_id` calls `User::factory()->admin()` and you'll get a throwaway admin.

### `TagFactory` ([`database/factories/TagFactory.php`](../database/factories/TagFactory.php))

```php
Tag::factory()->create(['name' => 'Coding']);
```

The model's `saving` hook auto-generates the slug.

## Livewire test pattern

```php
use Livewire\Livewire;

Livewire::test(SearchComponent::class)
    ->set('q', 'something')
    ->assertSee('Match title')
    ->assertDontSee('Should be hidden');
```

For actions:

```php
Livewire::actingAs($admin)
    ->test(AdminPromptForm::class)
    ->set('title', 'New')
    ->call('save');

$this->assertDatabaseHas('prompts', ['title' => 'New']);
```

For component mount with a route-bound model:

```php
Livewire::actingAs($admin)
    ->test(AdminPromptForm::class, ['prompt' => $prompt])
    ->set('title', 'Renamed')
    ->call('save');
```

## What's NOT tested

- View-level CSS / interactive JS (theme toggle, IntersectionObserver).
- The `CachePublicPage` middleware (no assertion on response headers).
- The login throttler counter resetting on success.
- Concurrent aggregation behavior (e.g. interleaved view inserts mid-run).
- Any UI workflow (no browser/Dusk tests).

If you add tests for any of these, they belong under `tests/Feature/` unless they're pure unit (e.g. testing a helper class in isolation), which would go under `tests/Unit/`.
