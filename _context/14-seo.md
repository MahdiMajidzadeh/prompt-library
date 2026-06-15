# SEO

What the app does to be crawlable, indexable, and shareable.

## Sitemap

[`public/sitemap.xml`](../public/sitemap.xml) is a real file on disk, served
directly by the web server (never hits PHP). It includes:

- Static landing pages — `/`, `/prompts/latest`, `/prompts/most-viewed`, `/tags`
- Every `/tags/{slug}` whose tag has at least one public prompt
- Every `/prompts/{slug}` with `is_public = true`

URLs are sorted: static first, then tags A-Z, then prompts newest-first. Each
`<url>` block carries `<lastmod>` (Atom-formatted from `updated_at`),
`<changefreq>`, and `<priority>`. Built by
[`SitemapService::generate()`](../app/Services/SitemapService.php).

### How it stays current

Model observers regenerate the file every time the public URL set could shift:

- [`PromptObserver`](../app/Observers/PromptObserver.php) fires on
  `created`/`updated`/`deleted`. Skips when the prompt is private and stays
  private — but always regenerates on a visibility flip.
- [`TagObserver`](../app/Observers/TagObserver.php) fires on `created`,
  rename/slug-change, and `deleted`.

Both observers are wired in
[`AppServiceProvider::boot()`](../app/Providers/AppServiceProvider.php:21-25)
via `Model::observe()`. Regen runs inline (no queue) and catches `Throwable`
— a sitemap-write failure can never break a save.

Manual rebuild: `php artisan sitemap:generate`. Useful after seeding,
restoring DB backups, or `APP_URL` changes.

### What's NOT in the sitemap

| Path | Reason |
| ---- | ------ |
| `/search`, `/m/search`, `/m/tags` | Tool / duplicate content — also blocked in robots.txt. |
| `/admin/*`, `/login`, `/logout` | Authenticated or transactional. |
| Private prompts | `is_public = false`. |

## robots.txt

[`public/robots.txt`](../public/robots.txt) — allows public pages, disallows
`/admin`, `/login`, `/search`, `/m/`, and points at `/sitemap.xml`. Edit if the
URL surface changes (e.g. adding a new public section).

## HTML meta

Both layouts ([`app.blade.php`](../resources/views/components/layouts/app.blade.php),
[`mobile.blade.php`](../resources/views/components/layouts/mobile.blade.php))
accept four props:

```blade
<x-layouts.app
    :title="$title"
    :description="$description"
    :og-type="$ogType"        {{-- defaults to 'website' --}}
    :og-image="$ogImage"      {{-- defaults to /favicon.ico --}}
>
```

They render:
- `<title>`
- `<meta name="description">`
- `<link rel="canonical" href="…">` — current URL on desktop layout; the
  mobile layout points its `/m/search` and `/m/tags` canonicals at the
  desktop `/search` and `/tags` equivalents (duplicate-content protection).
- Open Graph: `og:site_name`, `og:type`, `og:title`, `og:description`,
  `og:url`, `og:image`.
- Twitter Card: `summary` with title + description.
- `@stack('head')` slot for per-view extras (JSON-LD, custom meta).

## How a Livewire view sets its description

The recommended path is `->layoutData(['description' => '...'])` from the
component's `render()` method. Example:

```php
return view('livewire.prompts.show', [...])
    ->title($this->prompt->title)
    ->layoutData([
        'description' => Str::limit($this->prompt->body, 155),
        'ogType' => 'article',
    ]);
```

`title` is set via `#[Title]` for static pages or `->title(...)` for dynamic
ones — same as before.

## JSON-LD (structured data)

[`livewire/prompts/show.blade.php`](../resources/views/livewire/prompts/show.blade.php)
pushes a `CreativeWork` JSON-LD block into the layout's `@stack('head')`. It
carries the prompt title, body text, published/modified dates, tags as
keywords, and an `InteractionCounter` with the view count. Google reads this
to power rich results.

No JSON-LD on listing pages yet — `ItemList` blocks would be a reasonable next
step.

## Per-page descriptions

| Page | Component | Source of description |
| ---- | --------- | --------------------- |
| `/` | `Home` | Static, includes the total public-prompt count. |
| `/prompts/latest` | `Prompts\Latest` | Static, includes the total. |
| `/prompts/most-viewed` | `Prompts\MostViewed` | Static. |
| `/tags` | `Tags\Index` | Static. |
| `/tags/{slug}` | `Tags\Show` | Dynamic: count + tag name. |
| `/prompts/{slug}` | `Prompts\Show` | First 155 chars of the prompt body, whitespace-normalised. |

## Caveats / future work

- **Sitemap regen is synchronous.** At small scale that's fine; with ≫1k
  prompts the regen latency could become noticeable on each save. Wrap in a
  queued job (`ShouldQueue`) if that becomes a problem.
- **No `<image:image>` in the sitemap.** Add when prompts get hero images.
- **No `hreflang`.** App is English-only.
- **No FAQ / HowTo schema.** Worth considering for prompt detail pages if
  prompts trend toward instructional content.
