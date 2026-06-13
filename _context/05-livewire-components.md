# Livewire components

Every interactive page is a Livewire 3 component. Patterns are uniform across the codebase — once you've seen one component, the rest follow.

## Component list

### Public

| Component | Path | View | Notes |
| --------- | ---- | ---- | ----- |
| `Home` | `app/Livewire/Home.php` | `livewire/home.blade.php` | Three sections: 6 most-viewed, 6 latest, tag tile grid. |
| `Prompts\Latest` | `app/Livewire/Prompts/Latest.php` | `livewire/prompts/latest.blade.php` | Uses `WithInfiniteScroll`. |
| `Prompts\MostViewed` | `app/Livewire/Prompts/MostViewed.php` | `livewire/prompts/most-viewed.blade.php` | Hard cap of 20, **no** infinite scroll. |
| `Prompts\Show` | `app/Livewire/Prompts/Show.php` | `livewire/prompts/show.blade.php` | `mount()` aborts 404 if private and records a view. |
| `Tags\Show` | `app/Livewire/Tags/Show.php` | `livewire/tags/show.blade.php` | Uses `WithInfiniteScroll`. |
| `Search` | `app/Livewire/Search.php` | `livewire/search.blade.php` | `#[Url(as: 'q')]` keeps `?q=` in sync. Uses `WithInfiniteScroll`. Matches title + tag name (never body). |
| `Auth\Login` | `app/Livewire/Auth/Login.php` | `livewire/auth/login.blade.php` | Rate-limited 5/min per `(email, ip)`. |

### Admin

| Component | Path | View | Notes |
| --------- | ---- | ---- | ----- |
| `Admin\Dashboard` | `app/Livewire/Admin/Dashboard.php` | `livewire/admin/dashboard.blade.php` | Read-only counts. |
| `Admin\Prompts\Index` | `app/Livewire/Admin/Prompts/Index.php` | `livewire/admin/prompts/index.blade.php` | Search + visibility filter + paginated 20/page. `togglePublic` and `delete` are Livewire actions. |
| `Admin\Prompts\Form` | `app/Livewire/Admin/Prompts/Form.php` | `livewire/admin/prompts/form.blade.php` | Same component for create and edit (detected by `$prompt?->exists`). Tag picker uses `$toggle('tagIds.{id}')`. |
| `Admin\Tags\Index` | `app/Livewire/Admin/Tags/Index.php` | `livewire/admin/tags/index.blade.php` | Paginated 50/page. No delete. |
| `Admin\Tags\Form` | `app/Livewire/Admin/Tags/Form.php` | `livewire/admin/tags/form.blade.php` | Same component for create and edit. |

## Conventions you'll see repeated

### Layout attribute
Every component declares the global layout explicitly:

```php
#[Layout('components.layouts.app')]
```

Admin components use the same outer layout — the admin shell wraps via the `<x-admin-shell>` Blade component in the view (not via a different layout).

### Title attribute
Static titles use `#[Title('...')]`. Dynamic titles return from `render()`:

```php
return view('livewire.prompts.show', [...])->title($this->prompt->title);
```

### URL-synced state
Search query and admin filters use `#[Url]`:

```php
#[Url(as: 'q', except: '')]
public string $q = '';
```

The `except: ''` strips the param when empty so the URL stays clean.

### Validation
Inline `#[Validate(...)]` attributes — no FormRequest classes:

```php
#[Validate('required|string|max:255')]
public string $title = '';
```

For dynamic rules (e.g. `Rule::unique`), pass an array to `$this->validate()` directly — see `app/Livewire/Admin/Tags/Form.php:27-34`.

### Infinite scroll trait
`app/Livewire/Concerns/WithInfiniteScroll.php` is a 21-line trait:

```php
public int $perPage = 12;
public int $pageSize = 12;
public function loadMore(): void { $this->perPage += $this->pageSize; }
protected function resetInfiniteScroll(): void { $this->perPage = $this->pageSize; }
```

The view drops in `<x-load-more-sentinel :has-more="$hasMore" />` (an IntersectionObserver that calls `$wire.loadMore()` when scrolled into view).

Components using it: `Prompts\Latest`, `Tags\Show`, `Search`. **Not** `MostViewed` (hard-capped at 20) and **not** `Home` (fixed sections of 6 each).

### Pagination trait
Admin index pages use Livewire's built-in `WithPagination` trait instead — they need page numbers, not an infinite scroll.

### Flash → redirect pattern
After save/delete in admin, components flash a status message and redirect:

```php
session()->flash('status', "Updated \"{$prompt->title}\".");
$this->redirect(route('admin.prompts.index'), navigate: false);
```

The admin shell (`resources/views/components/admin-shell.blade.php:23-27`) renders the flash banner.

### Action confirmation
Destructive actions use Livewire's `wire:confirm`:

```blade
<button wire:click="delete" wire:confirm="Delete \"{{ $prompt->title }}\"? This cannot be undone.">
```

### Setting `user_id` on create
The form never accepts `user_id` from the request — it's set server-side from `auth()->id()`:

```php
// app/Livewire/Admin/Prompts/Form.php:51-56
$prompt = Prompt::create([
    'title' => $data['title'],
    'body' => $data['body'],
    'is_public' => $this->is_public,
    'user_id' => auth()->id(),
]);
```

Asserted by `tests/Feature/AdminAccessTest.php:52`.

## Pages with side effects

Only one component has side effects on render: `Prompts\Show::mount()` calls `recordView()`. Everything else is read-only.

## Tag picker UX

The admin form's tag selector is a flex of clickable tag pills. State is a `public array $tagIds` of selected ids, manipulated by `wire:click="$toggle('tagIds.{{ $tag->id }}')"`. There's no Select2 or multi-select widget — just buttons. See `resources/views/livewire/admin/prompts/form.blade.php:33-46`.
