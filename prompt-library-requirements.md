# Prompt Library — Requirements & Build Plan

> **For:** Claude Code · **Stack:** Laravel 12 + Livewire 3 + Bootstrap 5
> **Mode:** Execute the **Task List** at the bottom top-to-bottom. Each task has a Definition of Done. Do not skip ahead; later tasks assume earlier ones are complete and migrated.

---

## 0. Assumptions (correct before starting if wrong)

- Laravel **12**, Livewire **3**, Bootstrap **5**. PHP 8.2+. MySQL/MariaDB.
- **Design comes entirely from the `design-claude/` folder** in the project root. Do not invent colors, typography, spacing, or components. Read that folder first (Task 1) and reuse it for the layout shell and every page.
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

Do **not** install Breeze/Jetstream (they ship Tailwind and a registration flow we don't want, and would clash with `design-claude`).

- Build a single `Auth\Login` Livewire component using the `Auth` facade (`Auth::attempt`, throttle attempts, regenerate session). Style with the `design-claude` form components.
- Middleware `App\Http\Middleware\EnsureUserIsAdmin` (alias `admin`): allow only `auth()->user()?->is_admin`. Abort 403 otherwise. Register the alias in `bootstrap/app.php`.
- All `/admin/*` routes: `->middleware(['auth', 'admin'])`. `/login`: `->middleware('guest')`.
- `/logout`: POST, CSRF-protected, `Auth::logout()` + session invalidate + regenerate token.
- Admin account is created by a seeder (§ Task 3), not by sign-up.

---

## 7. Design Integration (`design-claude/`)

- **Task 1 is mandatory and blocking.** Inventory `design-claude/`: identify the page shell (header/nav/footer), card component(s) for a prompt, the tag/chip component, list/grid layout, buttons, form controls, empty/loading states, and the color + typography tokens. Note the Bootstrap version and any add-on (e.g. Webpixels) it uses.
- Produce a short `DESIGN-MAP.md` (3–4 sentences + a table) mapping each app page/component to the `design-claude` source it's built from. This is the contract for visual QA.
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
- [ ] All pages render from `design-claude` components per `DESIGN-MAP.md`; no Tailwind, no invented design system.
- [ ] `php artisan migrate:fresh --seed` produces a working site with admin login and a realistic set of public + private prompts and tags.

---

# TASK LIST (run in order)

> Run migrations and re-run the app after each phase. Commit at the end of each phase. Tick boxes as you go.

### Phase 0 — Bootstrap & design ingestion
- [ ] **0.1** Confirm/install Laravel 12 project skeleton and Livewire 3 (`composer require livewire/livewire`). Confirm DB connection in `.env` and that `php artisan migrate` runs clean on the default tables.
- [ ] **0.2** Read the entire `design-claude/` folder. Identify shell, components, tokens, Bootstrap version, and any add-on framework.
- [ ] **0.3** Wire the design's CSS/JS into Vite. Build the shared layout `components/layouts/app.blade.php` from the design's shell (header/nav/footer). Verify a blank page renders with correct fonts/colors.
- [ ] **0.4** Write `DESIGN-MAP.md` mapping planned pages/components → design sources.
- **DoD:** App boots; a placeholder page renders inside the real design shell; `DESIGN-MAP.md` exists.

### Phase 1 — Data model
- [ ] **1.1** Migrations: `create_prompts_table`, `create_tags_table`, `create_prompt_tag_table`, `create_prompt_views_table`, and a migration adding `is_admin` to `users`. Include all columns, indexes, FKs, and the composite unique on the pivot per §3.
- [ ] **1.2** Models `Prompt`, `Tag`, `PromptView` with relationships, casts, `scopePublic`, unique-slug generation, and `Prompt::recordView()`. Route-model-bind `Prompt` and `Tag` by `slug`. Add `is_admin` cast on `User`.
- [ ] **1.3** Factories: `PromptFactory` (varied titles/bodies, mix of public/private, random `view_count` left at 0 — counts come from views; **`user_id` defaults to `User::factory()->admin()`** so the not-null FK is satisfied when used standalone in tests), `TagFactory`.
- [ ] **1.4** `php artisan migrate:fresh` succeeds; tinker-create a prompt with tags to verify the relationship and slug uniqueness.
- **DoD:** Schema migrates clean; relationships and slug generation verified in tinker.

### Phase 2 — Seeders & admin account
- [ ] **2.1** `AdminUserSeeder`: one user, `is_admin = true`, credentials read from `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`) with safe documented defaults.
- [ ] **2.2** `TagSeeder` (a realistic set of prompt-library tags) and `PromptSeeder` (use the factory; **own every prompt with the seeded admin's `user_id`**; attach 1–4 tags each; mix of public/private; insert several `prompt_views` rows for some prompts so aggregation has data to fold). Runs after `AdminUserSeeder` so the owner exists.
- [ ] **2.3** Register all seeders in `DatabaseSeeder`. `php artisan migrate:fresh --seed` works end to end.
- **DoD:** Seeded DB has an admin, tags, public+private prompts, and some raw view rows.

### Phase 3 — View tracking & cron
- [ ] **3.1** Implement `Prompt::recordView()` with the **30-second `visitor_hash` (IP + user agent) dedupe** from §4 (P0): skip the insert when an identical-fingerprint row for the same prompt exists within the last 30s; never let the check block the page render.
- [ ] **3.2** Command `prompts:aggregate-views` using the incremental algorithm in §4, transaction-wrapped, with a summary log line and the full-recompute alternative noted in a comment.
- [ ] **3.3** Schedule it `everyFiveMinutes()->withoutOverlapping()`. Document the server cron line and `schedule:work` in `README.md`.
- [ ] **3.4** Test: seed raw views → run command → assert `view_count` matches and rows are `counted`; run again → assert no double-count.
- **DoD:** Aggregation is correct, idempotent, scheduled, and documented.

### Phase 4 — Public read-side
- [ ] **4.1** Build the shared `WithInfiniteScroll` concern (§5).
- [ ] **4.2** `Prompts\Latest` and `Prompts\MostViewed` full-page components with infinite scroll, ordering by `created_at` desc and `view_count` desc respectively, **public scope only**, built from design cards.
- [ ] **4.3** `Tags\Show` — public prompts for `{tag:slug}`, infinite scroll, 404 on unknown tag, tag name shown as the page heading.
- [ ] **4.4** `Search` — title-OR-tag query (§5 specifics), debounced live input, `#[Url]` on `q`, empty + no-results states, infinite scroll.
- [ ] **4.5** `Prompts\Show` — full prompt (title, whitespace-preserved body, clickable tags, view count), **records a view on mount**, **404 if private**, copy-to-clipboard button on the body (Alpine + Clipboard API) with copied feedback.
- [ ] **4.6** `Home` — most-viewed (top 6), latest (6 most recent), and **tags that have at least one public prompt** (exclude empty tags); each section links to its listing/tag page. No pagination here.
- [ ] **4.7** Manually verify a private prompt is invisible everywhere public and its detail URL 404s.
- **DoD:** Every public page works, paginates on scroll, enforces public scope, and uses design components.

### Phase 5 — Admin back office
- [ ] **5.1** `EnsureUserIsAdmin` middleware + alias; `Auth\Login` (throttled) and `/logout`; route groups (`/admin/*` → `auth,admin`; `/login` → `guest`).
- [ ] **5.2** `Admin\Dashboard` with the four counts.
- [ ] **5.3** `Admin\Prompts\Index` — list all prompts, search/filter, public/private badge with inline toggle, edit/delete, create button (admin-only paginator is fine).
- [ ] **5.4** `Admin\Prompts\Form` — create/edit: title, body (textarea), tag multi-select, public/private; **on create set `user_id = auth()->id()`** (owner = the acting admin); validation; delete with confirm. (Create-tag-inline is P1.)
- [ ] **5.5** `Admin\Tags\Index` + `Admin\Tags\Form` — create + edit tags with prompt counts. **No delete** — tags are never deleted; do not add a delete action or route.
- [ ] **5.6** Verify: logged-out and non-admin users are blocked from `/admin/*`; toggling a prompt to public makes it appear publicly (after no cron dependency — public scope is immediate).
- **DoD:** Admin can fully manage prompts and tags; access control holds; public/private toggle works.

### Phase 6 — Tests & QA
- [ ] **6.1** Feature tests (Pest or PHPUnit): public scope hides private prompts across all public routes; search matches title and tag but not body; a detail-page view inserts a row, and a repeat view from the same IP + user agent within 30s does **not** (dedupe); aggregation is correct and idempotent; `/admin` access control; admin CRUD happy paths.
- [ ] **6.2** Visual QA against `DESIGN-MAP.md` — every page matches its design source; flag any gaps.
- [ ] **6.3** `README.md`: setup, `.env` keys (incl. admin + schedule), seeding, cron line, and the design-folder dependency.
- [ ] **6.4** Final pass: `php artisan migrate:fresh --seed`, click through every route logged-out and as admin, run `prompts:aggregate-views`, confirm all §8 acceptance criteria.
- **DoD:** Tests pass; QA clean; README complete; all acceptance criteria met.
