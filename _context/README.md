# Project context

Reference docs for working on this codebase with Claude Code. Each file is a self-contained topic. Open the one closest to the task, then follow the cross-links into source files (cited as `path:line`).

## Index

| File | When to read it |
| ---- | --------------- |
| [01-overview.md](./01-overview.md) | What this app is, who uses it, current status. |
| [02-architecture.md](./02-architecture.md) | Directory layout, request flow, layout shell. |
| [03-data-model.md](./03-data-model.md) | Tables, columns, relations, indexes. |
| [04-routes.md](./04-routes.md) | Every route → Livewire component, middleware, behavior. |
| [05-livewire-components.md](./05-livewire-components.md) | Component patterns, attributes, traits, list of all components. |
| [06-domain-logic.md](./06-domain-logic.md) | View tracking, aggregation, search, slugs, HTTP caching. |
| [07-design-system.md](./07-design-system.md) | Tailwind 4 setup, tokens, `pl-*` classes, theming. |
| [08-auth-admin.md](./08-auth-admin.md) | Login throttling, admin gate, seeded admin. |
| [09-testing.md](./09-testing.md) | phpunit config, in-memory SQLite, test list, factory helpers. |
| [10-dev-workflow.md](./10-dev-workflow.md) | Setup, dev/build commands, env vars, scheduler. |
| [11-conventions.md](./11-conventions.md) | Coding style, what to do, what to avoid. |
| [12-deployment.md](./12-deployment.md) | Deploy to a Linux VPS — nginx, PHP-FPM, MySQL, cron, SSL. |
| [13-telegram-bot.md](./13-telegram-bot.md) | Admin Telegram bot — Nutgram setup, webhook, command list, conversation state. |

## How this folder is meant to be used

- **For Claude Code**: when picking up a task, scan this README, then read the topic docs that match the change you're making. Source files are always the source of truth — if a doc disagrees with code, trust the code and update the doc.
- **For humans**: same flow. The numbered prefixes give a sensible reading order if you want to onboard end-to-end.
- **Keep it current**: when you change architecture or a domain rule (e.g. how view dedupe works, what `is_public` means), update the matching doc in the same PR. The docs lose value fast if they drift.

## Out-of-scope (not in this folder, but useful)

- [`README.md`](../README.md) — project introduction (what it is, screenshots, brief quickstart). For full dev workflow see [`10-dev-workflow.md`](./10-dev-workflow.md).
- [`DESIGN-MAP.md`](../DESIGN-MAP.md) — maps every Blade page back to its `claude-design/*.html` source.
- [`prompt-library-requirements.md`](../prompt-library-requirements.md) — original product requirements (historical reference).
- [`claude-design/`](../claude-design/) — original HTML mockups (Home, Prompts, Detail).
- [`docs/screenshots/`](../docs/screenshots/) — screenshots referenced by the README.
