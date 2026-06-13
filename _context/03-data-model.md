# Data model

Four project tables (`users`, `prompts`, `tags`, `prompt_tag`, `prompt_views`) plus Laravel defaults (`cache`, `jobs`, `sessions`, etc.).

## Tables

### `users` — `database/migrations/0001_01_01_000000_create_users_table.php` + `…_add_is_admin_to_users_table.php`

| Column | Type | Notes |
| ------ | ---- | ----- |
| `id` | bigint pk | |
| `name`, `email`, `password`, `email_verified_at`, `remember_token` | Laravel defaults | |
| `is_admin` | bool, default `false`, **indexed** | Added by `2026_06_12_124242_add_is_admin_to_users_table.php`. Gate at `app/Http/Middleware/EnsureUserIsAdmin.php:13`. |

### `prompts` — `database/migrations/2026_06_12_124243_create_prompts_table.php`

| Column | Type | Notes |
| ------ | ---- | ----- |
| `id` | bigint pk | |
| `title` | string(255) | |
| `slug` | string(16), **unique** | Random 16-char string generated on `creating` (`app/Models/Prompt.php:35-51`). Used for `getRouteKeyName()`. |
| `body` | longText | Plain text. Line breaks preserved on render. |
| `is_public` | bool, default `false`, **indexed** | Public read-side filter pivot. |
| `view_count` | unsigned bigint, default `0`, **indexed** | Denormalized cache; folded from `prompt_views` by `prompts:aggregate-views`. |
| `user_id` | foreign → `users(id)`, `restrictOnDelete` | Set to `auth()->id()` on admin create. |
| `created_at`, `updated_at` | timestamps | |

Composite indexes (matter for the public listings):
- `(is_public, view_count)` — for most-viewed sort
- `(is_public, created_at)` — for latest sort

### `tags` — `database/migrations/2026_06_12_124244_create_tags_table.php`

| Column | Type | Notes |
| ------ | ---- | ----- |
| `id` | bigint pk | |
| `name` | string(100), **unique** | |
| `slug` | string(120), **unique** | Auto-generated on `saving` if name changed (`app/Models/Tag.php:22-45`). |
| `created_at`, `updated_at` | timestamps | |

### `prompt_tag` (pivot) — `database/migrations/2026_06_12_124245_create_prompt_tag_table.php`

| Column | Type | Notes |
| ------ | ---- | ----- |
| `prompt_id` | foreign → `prompts(id)`, `cascadeOnDelete` | |
| `tag_id` | foreign → `tags(id)`, `cascadeOnDelete` | |
| **unique** `(prompt_id, tag_id)` | | Prevents duplicate attachments. |

No primary key, no timestamps — it's a clean junction table.

### `prompt_views` — `database/migrations/2026_06_12_124246_create_prompt_views_table.php`

| Column | Type | Notes |
| ------ | ---- | ----- |
| `id` | bigint pk | |
| `prompt_id` | foreign → `prompts(id)`, `cascadeOnDelete`, **indexed** | |
| `counted` | bool, default `false`, **indexed** | Flipped to true by `prompts:aggregate-views` after the row is folded into `prompts.view_count`. |
| `visitor_hash` | string(64), nullable | `sha256(ip + '|' + user_agent)` — see `app/Livewire/Prompts/Show.php:22-25`. |
| `user_id` | foreign → `users(id)`, `nullOnDelete`, nullable | Currently always null (recorded views don't pass a user). |
| `created_at` | timestamp, defaults to `useCurrent()`. `UPDATED_AT = null` on the model (`app/Models/PromptView.php:10`). | |

Composite index `(prompt_id, visitor_hash)` supports the 30-second dedupe lookup.

## Relationships

| Model | Method | Type | Notes |
| ----- | ------ | ---- | ----- |
| `User` | `prompts()` | hasMany Prompt | `app/Models/User.php:53` |
| `Prompt` | `user()` | belongsTo User | `app/Models/Prompt.php:68` |
| `Prompt` | `tags()` | belongsToMany Tag (via `prompt_tag`) | `app/Models/Prompt.php:58` |
| `Prompt` | `views()` | hasMany PromptView | `app/Models/Prompt.php:63` |
| `Tag` | `prompts()` | belongsToMany Prompt | `app/Models/Tag.php:47` |
| `PromptView` | `prompt()` | belongsTo Prompt | `app/Models/PromptView.php:24` |
| `PromptView` | `user()` | belongsTo User | `app/Models/PromptView.php:29` |

## Casts

- `Prompt::$casts` — `is_public:bool`, `view_count:integer`.
- `PromptView::$casts` — `counted:bool`, `created_at:datetime`.
- `User::casts()` — `is_admin:bool`, `password:hashed`, `email_verified_at:datetime`.

## Public scope

```php
// app/Models/Prompt.php:53
public function scopePublic(Builder $query): Builder
{
    return $query->where('is_public', true);
}
```

**Every** public-facing list must use `Prompt::public()` somewhere in the chain. The whole purpose of `PublicScopeTest.php` is to guard this — see [09-testing.md](./09-testing.md).

## Foreign-key behaviors (deletion cascade map)

- Delete a `User` → blocked if any prompts reference them (`restrictOnDelete`). Admins are typically never deleted.
- Delete a `Prompt` → cascades into `prompt_tag` and `prompt_views`.
- Delete a `Tag` → cascades into `prompt_tag`. **But there's no admin route to delete tags** — `tests/Feature/AdminAccessTest.php:144` asserts no DELETE handler exists.
- Delete a `User` referenced by `prompt_views` → sets `prompt_views.user_id` to null.
