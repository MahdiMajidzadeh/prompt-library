# Prompt Library — Requirements & Step-by-Step Build Plan

> **For:** Claude Code · **Stack:** Laravel 12 + Livewire 3 + Bootstrap 5
> **Mode:** Execute the **Step-by-Step Task List** at the bottom top-to-bottom. Each step is small and atomic with its own Definition of Done. Do not skip ahead; later steps assume earlier ones are complete and migrated.

---

## 0. Assumptions (correct before starting if wrong)

- Laravel **12**, Livewire **3**, Bootstrap **5**. PHP 8.2+. MySQL/MariaDB.
- **Design comes entirely from the `claude-design/` folder** in the project root. Do not invent colors, typography, spacing, or components. Read that folder first (Step 1.x) and reuse it for the layout shell and every page.
- **Views are recorded on the prompt detail page** (one detail page per prompt is in scope). The detail page is also where the full prompt text and the copy-to-clipboard action live.
- **View counts are denormalized**: a `prompts.view_count` column is the source of truth for "most viewed" ordering, kept fresh by a scheduled command that aggregates raw rows from a separate `prompt_views` table.
- **No public sign-up in this phase.** There is exactly one privileged role — **admin** — seeded manually. The data model is shaped so end-user sign-up can be added later with no destructive migration.
- Infinite pagination = **append-on-scroll** via an Intersection Observer sentinel, with a "Load more" button fallback.

---

## 1. Problem & Scope

### Problem
There is no single place to browse, search, and copy a curated set of reusable prompts. Prompts live scattered across notes and chats, so they are hard to find and reuse. This app is a clean, fast, public-facing **prompt library** with a private admin back office for content management.

### In scope (P0)
- Public read-only site: home, two listing pages (most-viewed, latest), per-tag pages, search, and a prompt detail page. **Only `is_public = true` prompts appear anywhere on the public side.**
- Admin back office (behind login): full CRUD for prompts; create/edit for tags (tags are never deleted); plus a public/private toggle per prompt.
- View tracking: raw view events in a separate table, aggregated into a cached count by a scheduled (cron) command.

### Non-goals (this phase)
- **Public user sign-up / accounts** — not built. Data model is prepared for it (see §3.5).
- Ratings, comments, favorites/bookmarks, prompt versioning, collections/folders, prompt variables/templating, an API, or i18n — out of scope. Some are pre-modeled but **not** built.
- Full-text search on the prompt **body** — search matches **title and tag only** by explicit requirement.
- Rich text / WYSIWYG for prompt body — body is stored and displayed as plain text (preserve line breaks). Markdown rendering is a future consideration, not built.

---

## 2. Pages & Routes

Public side shows **public prompts only**. Admin side sees all prompts.

| Area | Path | Component | Behaviour |
|---|---|---|---|
| Public | `GET /` | `Home` | Most-viewed (top 6), latest (6 most recent), and **tags that have at least one public prompt** (empty tags are hidden so no tag link leads to an empty page). No pagination — fixed-size sections with links to full listings. |
| Public | `GET /prompts/most-viewed` | `Prompts\MostViewed` | All public prompts, ordered by `view_count` desc. Infinite pagination. |
| Public | `GET /prompts/latest` | `Prompts\Latest` | All public prompts, ordered by `created_at` desc. Infinite pagination. |
| Public | `GET /tags/{tag:slug}` | `Tags\Show` | All public prompts carrying this tag. Infinite pagination. 404 on unknown tag. |
| Public | `GET /search` | `Search` | Search by **title and tag only**, public prompts. Infinite pagination. Debounced input; `q` reflected in the URL. Empty state when no `q`; "no results" state otherwise. |
| Public | `GET /prompts/{prompt:slug}` | `Prompts\Show` | Full prompt: title, body, tags (each links to its tag page), view count, copy button. **Records a view on mount.** 404 if the prompt is private. |
| Admin | `GET /login` | `Auth\Login` | Email + password. Redirects to admin dashboard on success. No registration link. |
| Admin | `POST /logout` | — | Logs out, redirects to `/login`. |
| Admin | `GET /admin` | `Admin\Dashboard` | Lightweight: counts of prompts (public/private), tags, total views. |
| Admin | `GET /admin/prompts` | `Admin\Prompts\Index` | Table of all prompts; search/filter; public/private badge + inline toggle; edit/delete; create button. Standard paginator (admin can use simple page links). |
| Admin | `GET /admin/prompts/create` | `Admin\Prompts\Form` | Create prompt: title, body, tag multi-select (create-on-the-fly optional, P1), public/private. |
| Admin | `GET /admin/prompts/{prompt}/edit` | `Admin\Prompts\Form` | Edit prompt (same form). |
| Admin | `GET /admin/tags` | `Admin\Tags\Index` | Table of tags with prompt counts; create/edit only. **Tags are never deleted** — no delete action. |
| Admin | `GET /admin/tags/create` | `Admin\Tags\Form` | Create tag. |
| Admin | `GET /admin/tags/{tag}/edit` | `Admin\Tags\Form` | Edit tag. |

**Route protection:** wrap all `/admin/*` routes in `['auth', 'admin']` middleware (see §6). `/login` is guest-only.

---

## 3. Data Model

Use `bigIncrements` PKs, `timestamps()` everywhere, and `foreignId(...)->constrained()->cascadeOnDelete()` for FKs unless noted.

### 3.1 `prompts`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `title` | string(255) | Searchable. |
| `slug` | string(16), unique | A random 16-character alphanumeric string (digits + letters), generated on create and regenerated on the rare collision. Used in URLs. Not derived from the title. |
| `body` | longText | The prompt text. Plain text; preserve whitespace/line breaks on display. |
| `is_public` | boolean, default `false`, indexed | Only `true` rows show publicly. New prompts default to private. |
| `view_count` | unsignedBigInteger, default `0`, indexed | **Denormalized cache** maintained by the cron command. Never written on a page request. |
| `user_id` | foreignId, **not null**, `restrictOnDelete` | Author/owner. Set to the **admin who created the prompt** (`auth()->id()`). FK to `users`. `restrictOnDelete` prevents deleting a user who still owns prompts — reassign first. In a later phase this same column can point to a signed-up end user. |
| `timestamps` | | `created_at` drives the "latest" ordering. |

Index `(is_public, view_count)` and `(is_public, created_at)` to keep the two public listings fast.

### 3.2 `tags`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string(100), unique | Display name. Searchable. |
| `slug` | string(120), unique | URL key; generated from name. |
| `timestamps` | | |

### 3.3 `prompt_tag` (pivot — many-to-many)
| Column | Type | Notes |
|---|---|---|
| `prompt_id` | foreignId, `cascadeOnDelete` | |
| `tag_id` | foreignId, `cascadeOnDelete` | |
| — | unique | Composite unique `(prompt_id, tag_id)`. No timestamps needed. |

### 3.4 `prompt_views` (raw event log — the "separate table")
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `prompt_id` | foreignId, `cascadeOnDelete`, indexed | |
| `counted` | boolean, default `false`, indexed | Set to `true` by the aggregation command after the row's been folded into `prompts.view_count`. Enables incremental aggregation. |
| `visitor_hash` | string(64), nullable | `hash('sha256', ip . '|' . userAgent)` — a viewer fingerprint combining IP and user agent, used only for the 30-second dedupe (below). Indexed with `prompt_id`. Do **not** store the raw IP or user agent. |
| `user_id` | foreignId, nullable, `nullOnDelete` | Future: which signed-in user viewed. Null in phase 1. |
| `created_at` | timestamp | Use `created_at` only (no `updated_at` needed) — high-volume insert table. |

One row is inserted per qualifying detail-page view. This table is append-mostly; the cron command reads it, not page requests.

### 3.5 `users` (auth + future sign-up readiness)
Start from the default Laravel `users` migration and add:
| Column | Type | Notes |
|---|---|---|
| `is_admin` | boolean, default `false`, indexed | Gates admin access. Seeded `true` for the one admin. |

**Future-phase readiness (model now, build later):** `prompts.user_id` is already populated in phase 1 (the owning admin), so end-user ownership later reuses the same column with no schema change — a signed-up user simply becomes a valid `user_id`. `prompt_views.user_id` stays nullable for future signed-in viewers (null while there are no end-user accounts). When sign-up ships, add a `favorites` pivot (`user_id`, `prompt_id`) and optionally allow user-owned private prompts via the existing `is_public` + `user_id` columns. Do **not** create the favorites table now — leaving these FKs in place keeps that migration additive.

### Eloquent relationships
- `Prompt` → `belongsToMany(Tag)`, `hasMany(PromptView)`, `belongsTo(User)` (nullable).
- `Tag` → `belongsToMany(Prompt)`.
- `PromptView` → `belongsTo(Prompt)`, `belongsTo(User)` (nullable).
- `User` → `hasMany(Prompt)` (future use).

### Model behaviour
- `Prompt`: generate a unique random 16-character alphanumeric `slug` on create (regenerate on collision); it is **not** derived from the title and does **not** change when the title is edited. `scopePublic($q)` → `where('is_public', true)`. `recordView(?string $visitorHash = null)` → inserts a `prompt_views` row (applying the 30-second dedupe in §4). Cast `is_public` to boolean.
- `Tag`: auto-generate unique `slug` from `name`. Route-model-bind by `slug`.
- Implement slug generation inline. For `Prompt`, use a random generator (e.g. `Str::random(16)`) inside a loop that retries until the value is unique. For `Tag`, use `Str::slug($name)` with a uniqueness loop. A package like `spatie/laravel-sluggable` could cover the tag case but is optional; the prompt's random slug is simple enough to keep inline.

---

## 4. View Tracking & Cron Aggregation

**Why two pieces:** writing `view_count++` on every page hit causes row contention and makes "most viewed" ordering write-heavy. Instead, each view is a cheap insert into `prompt_views`; a scheduled command folds new rows into the cached `prompts.view_count`.

### Recording a view
- In `Prompts\Show::mount()`, after confirming the prompt is public, compute `$visitorHash = hash('sha256', request()->ip() . '|' . request()->userAgent())` and call `$prompt->recordView($visitorHash)`.
- **Dedupe rule (P0):** inside `recordView()`, before inserting, check whether a row already exists for this `prompt_id` **and** the same `visitor_hash` with `created_at` within the **last 30 seconds**. If so, skip the insert (this view does not count). Otherwise insert the row. This collapses refreshes and rapid re-opens by the same viewer while still counting genuine return visits after 30s. The check must never block or slow the page render — keep it to a single indexed lookup, and on any error fall through to rendering without recording.

### Aggregation command (cron)
- Command: `app/Console/Commands/AggregatePromptViews.php`, signature **`prompts:aggregate-views`**.
- **Incremental algorithm (primary):**
  1. Select counts of **uncounted** rows grouped by `prompt_id`:
     `SELECT prompt_id, COUNT(*) AS c FROM prompt_views WHERE counted = false GROUP BY prompt_id`.
  2. For each group, `prompts` `WHERE id = prompt_id` → `increment('view_count', c)`.
  3. Mark those rows `counted = true` (scope the update to rows that existed at step 1, e.g. by max id or a `whereIn` on selected ids, to avoid marking rows inserted mid-run).
  4. Wrap per-prompt update + mark in a DB transaction. Log a summary line (prompts touched, rows folded).
- **Alternative (note in code comment):** full recompute — `UPDATE prompts SET view_count = (SELECT COUNT(*) FROM prompt_views WHERE prompt_id = prompts.id)`. Simpler, but rescans the whole table each run; prefer incremental.
- **Schedule:** in `routes/console.php` (or `app/Console/Kernel.php` for older style), `Schedule::command('prompts:aggregate-views')->everyFiveMinutes()->withoutOverlapping()`.
- **Server cron entry** (document in README): `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`. Local dev: `php artisan schedule:work`.
- **Staleness is acceptable and expected:** "most viewed" reflects counts as of the last run (≤5 min old). State this in the README.
- **Retention (future):** `prompt_views` grows unbounded; a pruning command for old counted rows is a future consideration, not built.

---

## 5. Infinite Pagination Pattern (shared)

Apply the same pattern to `Prompts\MostViewed`, `Prompts\Latest`, `Tags\Show`, and `Search`. Build it once and reuse.

- Livewire full-page component holds `int $perPage` (e.g. start 12) and a `loadMore()` method that increases `$perPage` by the page size.
- `render()` queries with `->take($this->perPage)` (or cursor pagination for scale — acceptable enhancement) and exposes whether more rows exist (`$total > $perPage`) so the component knows when to stop.
- Blade: render the list, then a **sentinel** `<div>` at the bottom observed by an Intersection Observer (Alpine `x-intersect` is the clean option in Livewire 3) that calls `wire:click`/`$wire.loadMore()` when it enters the viewport. Provide a visible **"Load more"** button as a no-JS fallback and show a `wire:loading` spinner while fetching.
- Reset `$perPage` to the initial value whenever the underlying query changes (e.g. the search term updates).
- Consider a small shared trait `App\Livewire\Concerns\WithInfiniteScroll` to hold `perPage` + `loadMore()`.

**Search specifics:** debounce the input (`wire:model.live.debounce.400ms`), sync `q` to the query string (`#[Url] public string $q = ''`), and build the query as: public prompts where `title LIKE %q%` **OR** they have a tag whose `name LIKE %q%`. Use `whereHas('tags', …)` for the tag branch. Distinct results, ordered by relevance-or-recency (recency is fine). Reset pagination on each new term.

---

## 6. Admin Auth (minimal, Bootstrap-consistent)

Do **not** install Breeze/Jetstream (they ship Tailwind and a registration flow we don't want, and would clash with `claude-design`).

- Build a single `Auth\Login` Livewire component using the `Auth` facade (`Auth::attempt`, throttle attempts, regenerate session). Style with the `claude-design` form components.
- Middleware `App\Http\Middleware\EnsureUserIsAdmin` (alias `admin`): allow only `auth()->user()?->is_admin`. Abort 403 otherwise. Register the alias in `bootstrap/app.php`.
- All `/admin/*` routes: `->middleware(['auth', 'admin'])`. `/login`: `->middleware('guest')`.
- `/logout`: POST, CSRF-protected, `Auth::logout()` + session invalidate + regenerate token.
- Admin account is created by a seeder (§ Phase 2), not by sign-up.

---

## 7. Design Integration (`claude-design/`)

- **Phase 0 is mandatory and blocking.** Inventory `claude-design/`: identify the page shell (header/nav/footer), card component(s) for a prompt, the tag/chip component, list/grid layout, buttons, form controls, empty/loading states, and the color + typography tokens. Note the Bootstrap version and any add-on (e.g. Webpixels) it uses.
- Produce a short `DESIGN-MAP.md` (3–4 sentences + a table) mapping each app page/component to the `claude-design` source it's built from. This is the contract for visual QA.
- Build one shared Blade layout (`resources/views/components/layouts/app.blade.php`) from the design's shell and compose every page from the design's components. **No bespoke CSS** beyond what the design folder provides; if something's missing, reuse the nearest existing component rather than inventing one, and note the gap in `DESIGN-MAP.md`.
- Asset pipeline: wire the design's CSS/JS through Vite as the folder dictates.

---

## 8. Acceptance Criteria (Definition of Done for the build)

- [ ] A private prompt (`is_public = false`) is **not** reachable on the public side: it's absent from home, both listings, tag pages, and search, and its detail URL 404s.
- [ ] Home shows the 6 most-viewed, the 6 latest, and only tags that have at least one public prompt; each section links to its full page.
- [ ] Both listing pages and tag/search pages load more rows on scroll, stop cleanly when exhausted, and show a loading indicator while fetching.
- [ ] Search matches on title **and** tag name only — never on body — and reflects `q` in the URL.
- [ ] Visiting a public prompt detail page inserts exactly one `prompt_views` row per qualifying view; running `prompts:aggregate-views` moves those into `prompts.view_count` and marks rows `counted`. Re-running the command does not double-count.
- [ ] Most-viewed ordering reflects `view_count` after aggregation.
- [ ] `/admin/*` is unreachable while logged out and for non-admin users (403/redirect). Admin can create/edit/delete prompts, create/edit tags (tags are never deleted), assign tags, and toggle public/private; the toggle's effect is immediately visible on the public side.
- [ ] All pages render from `claude-design` components per `DESIGN-MAP.md`; no Tailwind, no invented design system.
- [ ] `php artisan migrate:fresh --seed` produces a working site with admin login and a realistic set of public + private prompts and tags.

---

# STEP-BY-STEP TASK LIST (run in order)

> Execute one step at a time. Each step ends with a verification command or check. Do not move to the next step until the current one's DoD is satisfied. Commit at the end of each phase.

---

## Phase 0 — Bootstrap & Design Ingestion

### Step 0.1 — Verify Laravel skeleton boots
- [ ] Run `php -v` and confirm PHP ≥ 8.2.
- [ ] Run `php artisan --version` and confirm Laravel 12.x.
- [ ] Open `.env` and confirm `DB_*` keys point to a working MySQL/MariaDB database.
- [ ] Run `php artisan migrate` against the default Laravel tables.
- **DoD:** `php artisan migrate` exits 0; default tables exist.

### Step 0.2 — Install Livewire 3
- [ ] Run `composer require livewire/livewire`.
- [ ] Confirm `livewire/livewire` version `^3.x` in `composer.json`.
- [ ] Run `php artisan livewire:publish --config` (only if you need to override defaults; otherwise skip).
- **DoD:** `composer show livewire/livewire` prints a 3.x version; `php artisan` lists `livewire:*` commands.

### Step 0.3 — Inventory the `claude-design/` folder
- [ ] List every file in `claude-design/`. Identify the entry HTML, CSS, and JS files.
- [ ] Note: Bootstrap version, any add-on framework (e.g. Webpixels, Volt, etc.), the page shell (header/nav/footer), and the asset paths.
- [ ] Identify the following components in the design: prompt card, tag/chip, list/grid container, buttons, form inputs, empty state, loading spinner.
- [ ] Note the color tokens, typography, and spacing scale used.
- **DoD:** You can name (in writing) the file(s) that contain each of the components above.

### Step 0.4 — Wire `claude-design` assets through Vite
- [ ] Update `vite.config.js` to include the design's CSS and JS entry points.
- [ ] Update `package.json` to install any npm dependencies the design folder requires.
- [ ] Run `npm install` and `npm run build` (or `npm run dev`) and confirm no errors.
- **DoD:** `public/build/` (or the configured output dir) contains the compiled design assets.

### Step 0.5 — Build the shared Blade layout
- [ ] Create `resources/views/components/layouts/app.blade.php` using the header/nav/footer shell from the design.
- [ ] Reference the Vite-built assets via `@vite([...])`.
- [ ] Add a `{{ $slot }}` for page content.
- **DoD:** A throwaway route `/` returning `<x-layouts.app>Hello</x-layouts.app>` renders the design shell with correct fonts/colors in the browser.

### Step 0.6 — Write `DESIGN-MAP.md`
- [ ] Create `DESIGN-MAP.md` at the project root.
- [ ] Include a 3–4 sentence intro describing the design source.
- [ ] Add a table mapping each planned page (Home, Latest, MostViewed, Tags\Show, Search, Prompts\Show, Login, Admin\*) and each component (prompt card, tag chip, form, button, empty state, loader) to its `claude-design/` source file.
- **DoD:** The file exists and every public/admin page in §2 has a row.

### Phase 0 commit
- [ ] `git add -A && git commit -m "phase 0: design ingestion and layout shell"`

---

## Phase 1 — Data Model

### Step 1.1 — Migration: `prompts` table
- [ ] Create `database/migrations/xxxx_create_prompts_table.php` per §3.1.
- [ ] Columns: `id`, `title` (string 255), `slug` (string 16, unique), `body` (longText), `is_public` (bool default false, indexed), `view_count` (unsignedBigInteger default 0, indexed), `user_id` (foreignId, not null, `restrictOnDelete`), `timestamps`.
- [ ] Composite indexes: `(is_public, view_count)`, `(is_public, created_at)`.
- **DoD:** Migration file written; do not migrate yet.

### Step 1.2 — Migration: `tags` table
- [ ] Create `database/migrations/xxxx_create_tags_table.php` per §3.2.
- [ ] Columns: `id`, `name` (string 100, unique), `slug` (string 120, unique), `timestamps`.
- **DoD:** Migration file written.

### Step 1.3 — Migration: `prompt_tag` pivot
- [ ] Create `database/migrations/xxxx_create_prompt_tag_table.php` per §3.3.
- [ ] Columns: `prompt_id` (foreignId, `cascadeOnDelete`), `tag_id` (foreignId, `cascadeOnDelete`).
- [ ] Composite unique index `(prompt_id, tag_id)`. No timestamps.
- **DoD:** Migration file written.

### Step 1.4 — Migration: `prompt_views` table
- [ ] Create `database/migrations/xxxx_create_prompt_views_table.php` per §3.4.
- [ ] Columns: `id`, `prompt_id` (foreignId, `cascadeOnDelete`, indexed), `counted` (bool default false, indexed), `visitor_hash` (string 64, nullable), `user_id` (foreignId, nullable, `nullOnDelete`), `created_at` only.
- [ ] Composite index `(prompt_id, visitor_hash)` to keep the dedupe lookup cheap.
- **DoD:** Migration file written.

### Step 1.5 — Migration: add `is_admin` to `users`
- [ ] Create `database/migrations/xxxx_add_is_admin_to_users_table.php`.
- [ ] Add `is_admin` (bool default false, indexed) in `up()`; drop in `down()`.
- **DoD:** Migration file written.

### Step 1.6 — Run all migrations
- [ ] Run `php artisan migrate:fresh`.
- [ ] Confirm all five tables exist with expected columns and indexes (`SHOW INDEX FROM prompts;` etc.).
- **DoD:** `migrate:fresh` exits 0; schema verified.

### Step 1.7 — `Prompt` model
- [ ] Create `app/Models/Prompt.php` with: `$fillable`, `is_public` boolean cast, relations (`tags` belongsToMany, `views` hasMany, `user` belongsTo).
- [ ] Add `scopePublic($q)` → `where('is_public', true)`.
- [ ] Add `getRouteKeyName(): string` returning `'slug'`.
- [ ] In a `booted()` hook, generate a unique random 16-char alphanumeric slug on `creating` using `Str::random(16)` with a uniqueness loop.
- [ ] Add `recordView(?string $visitorHash = null): void` — implementation stubbed; full dedupe logic in Phase 3.
- **DoD:** `app(\App\Models\Prompt::class)` instantiates without error; `Prompt::factory()` (added next phase) will work.

### Step 1.8 — `Tag` model
- [ ] Create `app/Models/Tag.php` with: `$fillable`, relations (`prompts` belongsToMany).
- [ ] Add `getRouteKeyName(): string` returning `'slug'`.
- [ ] In a `booted()` hook on `creating`/`updating`, generate a unique slug from `name` using `Str::slug($name)` + uniqueness loop (append `-2`, `-3`, … on collision).
- **DoD:** Model instantiates; slug derives correctly in tinker.

### Step 1.9 — `PromptView` model
- [ ] Create `app/Models/PromptView.php`.
- [ ] Disable `updated_at`: set `const UPDATED_AT = null;` or override `$timestamps = false` for `updated_at` only (use `public $timestamps = true;` plus override).
- [ ] Add `$fillable`: `prompt_id`, `counted`, `visitor_hash`, `user_id`.
- [ ] Relations: `prompt` belongsTo, `user` belongsTo.
- **DoD:** Model instantiates; inserting a row in tinker writes `created_at` only.

### Step 1.10 — Update `User` model
- [ ] In `app/Models/User.php`, add `is_admin` to `$fillable` and cast to `boolean`.
- [ ] Add `prompts()` `hasMany` relation.
- **DoD:** `User::factory()->create(['is_admin' => true])` writes `is_admin=1`.

### Step 1.11 — Factories
- [ ] Create `database/factories/PromptFactory.php`: varied `title` (sentence), `body` (paragraphs), `is_public` (50/50 mix), `view_count` left at 0; default `user_id` to `User::factory()->create(['is_admin' => true])->id` (or a state `->admin()`).
- [ ] Create `database/factories/TagFactory.php`: unique `name` (word), `slug` auto via the model hook.
- [ ] Add a `UserFactory::admin()` state setting `is_admin = true`.
- **DoD:** `Prompt::factory()->create()` succeeds standalone.

### Step 1.12 — Tinker smoke test
- [ ] Run `php artisan tinker`.
- [ ] Execute `$p = Prompt::factory()->create();` — note the random 16-char slug.
- [ ] Execute `$t = Tag::factory()->create(['name' => 'Coding']);` — note slug = `coding`.
- [ ] Execute `$p->tags()->attach($t->id);` and `$p->fresh()->tags->pluck('name');`.
- [ ] Execute `Prompt::factory()->count(50)->create();` and confirm no slug collision errors.
- **DoD:** Relations work; slug generation works.

### Phase 1 commit
- [ ] `git add -A && git commit -m "phase 1: data model, migrations, models, factories"`

---

## Phase 2 — Seeders & Admin Account

### Step 2.1 — `AdminUserSeeder`
- [ ] Create `database/seeders/AdminUserSeeder.php`.
- [ ] Read `ADMIN_EMAIL` and `ADMIN_PASSWORD` from env with documented defaults (e.g. `admin@example.test` / `password`).
- [ ] `User::updateOrCreate` with `is_admin = true` and `password = Hash::make(...)`.
- [ ] Document the env keys in `.env.example`.
- **DoD:** Running the seeder creates one admin user.

### Step 2.2 — `TagSeeder`
- [ ] Create `database/seeders/TagSeeder.php`.
- [ ] Seed a realistic set of ~15 tags (e.g. `Writing`, `Coding`, `Marketing`, `Research`, `Productivity`, `Education`, `Design`, `Analysis`, `Brainstorming`, `Email`, `SQL`, `Debugging`, `Refactoring`, `Documentation`, `Testing`).
- **DoD:** Seeder runs; `tags` table has the expected rows.

### Step 2.3 — `PromptSeeder`
- [ ] Create `database/seeders/PromptSeeder.php`.
- [ ] Fetch the seeded admin's `id`.
- [ ] Create ~30 prompts via factory with `user_id = $admin->id`.
- [ ] For each prompt, attach 1–4 random tags.
- [ ] Mark roughly 80% as `is_public = true`, the rest private.
- [ ] For ~10 random public prompts, insert 5–50 raw `prompt_views` rows each (vary `created_at` over the last week, leave `counted = false`).
- **DoD:** Seeder runs after `AdminUserSeeder`; counts look right in tinker.

### Step 2.4 — Register seeders in `DatabaseSeeder`
- [ ] In `database/seeders/DatabaseSeeder.php`, call `AdminUserSeeder`, then `TagSeeder`, then `PromptSeeder` in that order.
- **DoD:** Order is correct (admin before prompts).

### Step 2.5 — Full seed run
- [ ] Run `php artisan migrate:fresh --seed`.
- [ ] Confirm in tinker: one admin user, ~15 tags, ~30 prompts (mix public/private), several hundred `prompt_views` rows.
- **DoD:** End-to-end seed completes without errors.

### Phase 2 commit
- [ ] `git add -A && git commit -m "phase 2: seeders and admin account"`

---

## Phase 3 — View Tracking & Cron

### Step 3.1 — Implement `Prompt::recordView()` dedupe
- [ ] Open `app/Models/Prompt.php`.
- [ ] In `recordView(?string $visitorHash = null)`: wrap the body in `try { ... } catch (\Throwable $e) { return; }` so failures never break the page.
- [ ] Query `PromptView::where('prompt_id', $this->id)->where('visitor_hash', $visitorHash)->where('created_at', '>=', now()->subSeconds(30))->exists()`. If true, return.
- [ ] Otherwise `PromptView::create(['prompt_id' => $this->id, 'visitor_hash' => $visitorHash, 'counted' => false])`.
- **DoD:** Calling `$p->recordView('abc')` twice within 30s creates one row; after 30s a second call creates a second row.

### Step 3.2 — `AggregatePromptViews` command
- [ ] Run `php artisan make:command AggregatePromptViews`.
- [ ] Set `$signature = 'prompts:aggregate-views'`.
- [ ] In `handle()`:
  - Snapshot `$maxId = PromptView::where('counted', false)->max('id') ?? 0;` — bounds the run.
  - Select grouped counts: `PromptView::where('counted', false)->where('id', '<=', $maxId)->groupBy('prompt_id')->selectRaw('prompt_id, COUNT(*) as c')->get()`.
  - For each group, in a `DB::transaction(function () { ... })`:
    - `Prompt::where('id', $group->prompt_id)->increment('view_count', $group->c);`
    - `PromptView::where('prompt_id', $group->prompt_id)->where('counted', false)->where('id', '<=', $maxId)->update(['counted' => true]);`
  - Log `info("Aggregated views: prompts={prompts}, rows={rows}")`.
- [ ] Add a code comment with the full-recompute alternative SQL.
- **DoD:** `php artisan prompts:aggregate-views` runs without errors.

### Step 3.3 — Schedule the command
- [ ] Open `routes/console.php` (Laravel 12 style).
- [ ] Add `Schedule::command('prompts:aggregate-views')->everyFiveMinutes()->withoutOverlapping();`.
- **DoD:** `php artisan schedule:list` shows the entry.

### Step 3.4 — Document the cron entry
- [ ] In `README.md`, document the server cron line: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`.
- [ ] Document local-dev usage: `php artisan schedule:work`.
- [ ] State that "most viewed" counts may lag up to ~5 minutes.
- **DoD:** README section exists.

### Step 3.5 — Idempotency test (manual)
- [ ] Run `php artisan migrate:fresh --seed`.
- [ ] Note the `view_count` on a few prompts (should be 0).
- [ ] Note the count of `prompt_views` rows where `counted=false`.
- [ ] Run `php artisan prompts:aggregate-views`.
- [ ] Confirm: `prompts.view_count` now reflects raw view counts; all `prompt_views.counted = true`.
- [ ] Run the command again. Confirm: no `view_count` changes; no double-count.
- **DoD:** Aggregation is correct and idempotent.

### Phase 3 commit
- [ ] `git add -A && git commit -m "phase 3: view tracking and aggregation"`

---

## Phase 4 — Public Read-Side

### Step 4.1 — `WithInfiniteScroll` trait
- [ ] Create `app/Livewire/Concerns/WithInfiniteScroll.php`.
- [ ] Properties: `public int $perPage = 12;`, `public int $pageSize = 12;`.
- [ ] Method: `public function loadMore(): void { $this->perPage += $this->pageSize; }`.
- [ ] Method: `protected function resetPagination(): void { $this->perPage = $this->pageSize; }`.
- **DoD:** Trait file exists and is importable.

### Step 4.2 — Shared `prompt-card` Blade component
- [ ] Create `resources/views/components/prompt-card.blade.php` from the design's card source.
- [ ] Props: `$prompt`. Renders title (linked to detail), tags (each linked to its tag page), view count, truncated body preview.
- **DoD:** Component renders correctly with a seeded prompt.

### Step 4.3 — Shared `load-more-sentinel` Blade component
- [ ] Create `resources/views/components/load-more-sentinel.blade.php`.
- [ ] Renders the `wire:loading` spinner, a `<button wire:click="loadMore">Load more</button>` fallback, and a `<div x-intersect="$wire.loadMore()">` sentinel.
- [ ] Props: `$hasMore` (bool). Only render when true.
- **DoD:** Component renders.

### Step 4.4 — `Prompts\Latest` component
- [ ] Run `php artisan make:livewire Prompts/Latest`.
- [ ] Use the `WithInfiniteScroll` trait.
- [ ] In `render()`: `Prompt::public()->with('tags')->latest()->take($this->perPage)->get()` + total count via separate query.
- [ ] Blade: iterate `<x-prompt-card>`s in the design's grid layout; include `<x-load-more-sentinel :has-more="$hasMore">`.
- [ ] Add route `Route::get('/prompts/latest', Latest::class)->name('prompts.latest');`.
- **DoD:** Page loads, shows 12 latest public prompts, scrolling loads more.

### Step 4.5 — `Prompts\MostViewed` component
- [ ] Run `php artisan make:livewire Prompts/MostViewed`.
- [ ] Same as Step 4.4 but order by `view_count` desc.
- [ ] Add route `Route::get('/prompts/most-viewed', MostViewed::class)->name('prompts.most-viewed');`.
- **DoD:** Page loads with correct order; scroll loads more.

### Step 4.6 — `Tags\Show` component
- [ ] Run `php artisan make:livewire Tags/Show`.
- [ ] Property: `public Tag $tag;` with `mount(Tag $tag)` route-model-binding by slug.
- [ ] Query: `$this->tag->prompts()->public()->latest()->take($this->perPage)->get()`.
- [ ] Show tag name as heading.
- [ ] Add route `Route::get('/tags/{tag:slug}', Show::class)->name('tags.show');`. Auto-404 on unknown.
- **DoD:** `/tags/coding` shows public prompts tagged Coding; `/tags/nope` 404s.

### Step 4.7 — `Search` component
- [ ] Run `php artisan make:livewire Search`.
- [ ] Property: `#[Url] public string $q = '';` and `wire:model.live.debounce.400ms` on the input.
- [ ] In `updatedQ()`, reset pagination.
- [ ] Query: `Prompt::public()->where(fn($qq) => $qq->where('title', 'like', "%{$this->q}%")->orWhereHas('tags', fn($t) => $t->where('name', 'like', "%{$this->q}%")))->distinct()->latest()->take($this->perPage)->get()`. Skip the query (or return empty) when `$q` is empty.
- [ ] Empty state when `$q === ''`; "no results" when query non-empty but result empty.
- [ ] Add route `Route::get('/search', Search::class)->name('search');`.
- **DoD:** Typing a query updates the URL and results; results never match on body.

### Step 4.8 — `Prompts\Show` component
- [ ] Run `php artisan make:livewire Prompts/Show`.
- [ ] Property: `public Prompt $prompt;`.
- [ ] In `mount(Prompt $prompt)`: route-model-bound by slug. If `!$prompt->is_public` then `abort(404);`. Otherwise compute `$visitorHash = hash('sha256', request()->ip() . '|' . request()->userAgent())` and call `$prompt->recordView($visitorHash)`.
- [ ] Eager-load tags.
- [ ] Blade: title, body wrapped to preserve whitespace (use `<pre>` styled or `white-space: pre-wrap`), tags as clickable chips, view count, copy-to-clipboard button (Alpine + `navigator.clipboard.writeText`) with "Copied!" feedback for 1.5s.
- [ ] Add route `Route::get('/prompts/{prompt:slug}', Show::class)->name('prompts.show');`.
- **DoD:** Public prompt detail loads, records view, copy button works. Private prompt detail 404s.

### Step 4.9 — `Home` component
- [ ] Run `php artisan make:livewire Home`.
- [ ] In `render()`: query top 6 most-viewed (public), 6 latest (public), and tags with `whereHas('prompts', fn($q) => $q->public())` (excludes empty tags).
- [ ] Blade: three sections, each with a "See all" link to its corresponding listing/tag listing page.
- [ ] Add route `Route::get('/', Home::class)->name('home');` (replace the default welcome).
- **DoD:** Homepage shows the three sections; empty tags hidden.

### Step 4.10 — Manual public-scope verification
- [ ] Pick a private prompt's slug from the DB.
- [ ] Visit `/prompts/{that-slug}` → expect 404.
- [ ] Confirm the private prompt does not appear on `/`, `/prompts/latest`, `/prompts/most-viewed`, any tag page, or in `/search` results for its title.
- **DoD:** Private content is invisible on every public route.

### Phase 4 commit
- [ ] `git add -A && git commit -m "phase 4: public read-side"`

---

## Phase 5 — Admin Back Office

### Step 5.1 — `EnsureUserIsAdmin` middleware
- [ ] Create `app/Http/Middleware/EnsureUserIsAdmin.php`.
- [ ] In `handle()`, if `! auth()->user()?->is_admin` → `abort(403)`. Else `return $next($request);`.
- [ ] Register the alias `admin` in `bootstrap/app.php` via `$middleware->alias([...])`.
- **DoD:** `Route::get('/test', fn() => 'ok')->middleware('admin')` returns 403 for non-admins.

### Step 5.2 — `Auth\Login` component
- [ ] Run `php artisan make:livewire Auth/Login`.
- [ ] Properties: `public string $email = ''; public string $password = '';`.
- [ ] Method `submit()`: validate; call `Auth::attempt(['email' => $this->email, 'password' => $this->password])`; on success regenerate session and redirect to `/admin`; on failure throw `ValidationException`.
- [ ] Add `RateLimiter::tooManyAttempts()` throttling (5/min keyed by IP+email).
- [ ] Blade uses design's form components.
- [ ] Add route `Route::get('/login', Login::class)->middleware('guest')->name('login');`.
- **DoD:** Correct credentials redirect to `/admin`; wrong credentials show an error; 6th rapid attempt is throttled.

### Step 5.3 — Logout route
- [ ] In `routes/web.php`: `Route::post('/logout', function () { Auth::logout(); request()->session()->invalidate(); request()->session()->regenerateToken(); return redirect('/login'); })->name('logout');`.
- [ ] Logout button (form POST with CSRF) in the admin layout/nav.
- **DoD:** POST `/logout` ends the session and redirects.

### Step 5.4 — Admin route group
- [ ] Wrap all admin routes in `Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () { ... });`.
- **DoD:** Hitting `/admin/*` while logged out redirects to `/login`.

### Step 5.5 — `Admin\Dashboard`
- [ ] Run `php artisan make:livewire Admin/Dashboard`.
- [ ] In `render()`: counts of public prompts, private prompts, tags, total `prompt_views` rows.
- [ ] Add route inside the admin group: `Route::get('/', Dashboard::class)->name('dashboard');`.
- **DoD:** `/admin` shows four counts.

### Step 5.6 — `Admin\Prompts\Index`
- [ ] Run `php artisan make:livewire Admin/Prompts/Index`.
- [ ] Properties: `public string $q = ''; public ?bool $publicFilter = null;`.
- [ ] In `render()`: paginated (`paginate(20)`) query with optional title-like filter and public/private filter.
- [ ] Blade table: columns title, tags, public/private badge, views, created_at, actions (edit, delete with confirm).
- [ ] Add "Create" button linking to `admin.prompts.create`.
- [ ] Inline public/private toggle: `wire:click="togglePublic({{ $prompt->id }})"` updating the row and re-rendering.
- [ ] Add route: `Route::get('/prompts', Index::class)->name('prompts.index');`.
- **DoD:** Admin sees all prompts; filter works; toggle flips `is_public` and shows the new state.

### Step 5.7 — `Admin\Prompts\Form` (create + edit)
- [ ] Run `php artisan make:livewire Admin/Prompts/Form`.
- [ ] Properties: `public ?Prompt $prompt = null; public string $title = ''; public string $body = ''; public bool $is_public = false; public array $tagIds = [];`.
- [ ] `mount(Prompt $prompt = null)`: if editing, hydrate fields; if creating, leave blank.
- [ ] `render()`: pass `Tag::orderBy('name')->get()` to the view for the multi-select.
- [ ] `save()`: validate; if creating set `user_id = auth()->id()`; persist; sync tags; redirect to `admin.prompts.index` with a flash message.
- [ ] `delete()`: confirm via Alpine `confirm()`; delete; redirect.
- [ ] Add routes: `Route::get('/prompts/create', Form::class)->name('prompts.create');` and `Route::get('/prompts/{prompt}/edit', Form::class)->name('prompts.edit');`.
- **DoD:** Create flow stores a row with the acting admin as owner; edit updates; delete removes.

### Step 5.8 — `Admin\Tags\Index`
- [ ] Run `php artisan make:livewire Admin/Tags/Index`.
- [ ] In `render()`: `Tag::withCount('prompts')->orderBy('name')->paginate(50)`.
- [ ] Blade: table with name, slug, prompt count, edit link. **No delete button or route.**
- [ ] Add route: `Route::get('/tags', Index::class)->name('tags.index');`.
- **DoD:** Tags list renders with counts; no delete action anywhere.

### Step 5.9 — `Admin\Tags\Form` (create + edit)
- [ ] Run `php artisan make:livewire Admin/Tags/Form`.
- [ ] Properties: `public ?Tag $tag = null; public string $name = '';`.
- [ ] `mount(Tag $tag = null)`: hydrate on edit.
- [ ] `save()`: validate `name` unique (ignore self on edit); persist; redirect.
- [ ] Add routes: `Route::get('/tags/create', Form::class)->name('tags.create');` and `Route::get('/tags/{tag}/edit', Form::class)->name('tags.edit');`.
- **DoD:** Admin can create and edit tags; slug auto-generates; no delete.

### Step 5.10 — Manual admin-access verification
- [ ] Log out; visit `/admin` → expect redirect to `/login`.
- [ ] Log in as the admin → expect access.
- [ ] Create a new public prompt; visit `/prompts/{slug}` in another tab → it appears publicly immediately.
- [ ] Toggle it to private; refresh the public detail page → 404; refresh `/prompts/latest` → it's gone.
- **DoD:** Access control holds; toggle is immediate.

### Phase 5 commit
- [ ] `git add -A && git commit -m "phase 5: admin back office"`

---

## Phase 6 — Tests & QA

### Step 6.1 — Public scope tests
- [ ] Create `tests/Feature/PublicScopeTest.php`.
- [ ] Tests: home hides private; `/prompts/latest`, `/prompts/most-viewed`, `/tags/{slug}`, `/search` all hide private; `/prompts/{private-slug}` 404s.
- **DoD:** Tests pass.

### Step 6.2 — Search tests
- [ ] Create `tests/Feature/SearchTest.php`.
- [ ] Tests: matches by title; matches by tag name; **does not** match a unique word found only in the body.
- **DoD:** Tests pass.

### Step 6.3 — View tracking tests
- [ ] Create `tests/Feature/ViewTrackingTest.php`.
- [ ] Tests: visiting a public detail page inserts one row; a second visit with the same IP+UA within 30s inserts none; after 30s a second visit inserts one; private page does not insert a row (it 404s).
- **DoD:** Tests pass.

### Step 6.4 — Aggregation tests
- [ ] Create `tests/Feature/AggregateViewsTest.php`.
- [ ] Tests: with N uncounted rows, the command sets `view_count = N` and marks rows counted; running the command twice in a row leaves `view_count = N` (idempotent).
- **DoD:** Tests pass.

### Step 6.5 — Admin tests
- [ ] Create `tests/Feature/AdminAccessTest.php`.
- [ ] Tests: guests are redirected from `/admin/*`; non-admin authed users get 403; admin can CRUD prompts; admin can create/edit (not delete) tags.
- **DoD:** Tests pass.

### Step 6.6 — Run the full suite
- [ ] Run `php artisan test` (or `./vendor/bin/pest`) and confirm green.
- **DoD:** All tests pass.

### Step 6.7 — Visual QA against `DESIGN-MAP.md`
- [ ] Walk through each public and admin page in the browser.
- [ ] Confirm each matches its `claude-design` source per `DESIGN-MAP.md`.
- [ ] Note any gaps in `DESIGN-MAP.md` (do not invent new components).
- **DoD:** All pages match the design contract.

### Step 6.8 — Finalize `README.md`
- [ ] Sections: setup (clone, composer, npm, .env keys), seeding (`migrate:fresh --seed`, admin credentials), cron line, design-folder dependency note, "view counts lag ~5 minutes" note.
- **DoD:** README is complete.

### Step 6.9 — Acceptance criteria walkthrough
- [ ] Re-run `php artisan migrate:fresh --seed`.
- [ ] Click through every route logged-out and as admin.
- [ ] Run `php artisan prompts:aggregate-views` and confirm counts update.
- [ ] Tick every box in §8.
- **DoD:** All §8 acceptance criteria met.

### Phase 6 commit
- [ ] `git add -A && git commit -m "phase 6: tests, qa, and docs"`
