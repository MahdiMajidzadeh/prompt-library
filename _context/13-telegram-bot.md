# Telegram bot

An admin-only Telegram bot for creating prompts and tags without opening the
web admin. Built on **[nutgram/laravel](https://nutgram.dev/)** in webhook
mode. Read-only, single-user-ish — anyone whose Telegram user id is not in the
env whitelist is silently rejected.

## Files

| File | Purpose |
| ---- | ------- |
| [`config/nutgram.php`](../config/nutgram.php) | Token, admin id whitelist, webhook safe_mode, conversation TTL. Published from the package; we own it. |
| [`routes/telegram.php`](../routes/telegram.php) | All command handlers + global `RequireAdmin` middleware. |
| [`routes/web.php`](../routes/web.php) (`telegram.webhook`) | `POST /telegram/webhook` → resolves `Nutgram` from the container and calls `$bot->run()`. |
| [`bootstrap/app.php`](../bootstrap/app.php) | Adds `telegram/webhook` to the CSRF except list — Telegram cannot send a CSRF token. |
| [`app/Telegram/Middleware/RequireAdmin.php`](../app/Telegram/Middleware/RequireAdmin.php) | Fail-closed: blocks senders not in `config('nutgram.admin_ids')`. |
| [`app/Telegram/Conversations/AddPromptConversation.php`](../app/Telegram/Conversations/AddPromptConversation.php) | Wizard: title → body → public/private → tags → save. |
| [`app/Telegram/Conversations/AddTagConversation.php`](../app/Telegram/Conversations/AddTagConversation.php) | Single-step wizard: name → create. |

## Env vars

```env
TELEGRAM_TOKEN=                    # from @BotFather after /newbot
TELEGRAM_ADMIN_IDS=12345,67890     # comma-separated Telegram user ids (@userinfobot)
```

With no `TELEGRAM_ADMIN_IDS` set, the bot rejects everyone — a safer default
than rejecting nobody.

## Commands

| Command | Behavior |
| ------- | -------- |
| `/start`, `/help` | Print the command list. |
| `/addprompt` | Start `AddPromptConversation`. The owning Laravel `User` is the first row with `is_admin = true` (we don't yet link Telegram users to Laravel users). |
| `/addtag` | Start `AddTagConversation`. Slug is auto-derived; duplicates by name or slug are detected and reported. |
| `/cancel` | `$bot->endConversation()` and reply. Safe to call when nothing is active. |

## Webhook setup

Nutgram ships its own artisan commands; we did **not** write custom set/delete
commands.

```bash
# Set the webhook to the public URL (must be HTTPS in production).
php artisan nutgram:hook:set https://your-domain.test/telegram/webhook

# Inspect what Telegram currently has registered.
php artisan nutgram:hook:info

# Remove the hook (e.g. before switching back to polling for local dev).
php artisan nutgram:hook:remove
```

In `local`, `config('nutgram.safe_mode')` is `false` so any request body is
accepted (handy for replaying updates from `nutgram:hook:info`). In
`production`, safe mode flips to `true` and Nutgram verifies the
`X-Telegram-Bot-Api-Secret-Token` header against `md5(config('app.key'))` —
attached automatically by `nutgram:hook:set` when safe mode is on.

## Local development

There is no tunnel set up. For local end-to-end testing either:

1. **Tunnel** — run e.g. `ngrok http 8000` and point `nutgram:hook:set` at the
   public URL.
2. **Long-polling** — `php artisan nutgram:run` (or `:listen` for hot reload).
   This bypasses the webhook entirely. Stop the hook first
   (`nutgram:hook:remove`) or polling will compete with the webhook.

## Conversation state

Conversation classes extend `SergiX44\Nutgram\Conversations\Conversation`.
Public properties on the class instance are serialized to the configured cache
store between turns (TTL set by `config('nutgram.config.conversation_ttl')`).
Cache store is the app default (`config('cache.default')`, currently
`database`).

`$this->next('methodName')` advances the wizard; `$this->end()` discards the
serialized instance. `/cancel` ends whatever conversation is open for the
current `(user_id, chat_id)` pair.

## Known limitations / future work

- **Prompt owner is fixed** — set to the first admin in the `users` table.
  Future: a `telegram_user_id` column on `users` + a `/start <token>` link
  flow.
- **Read-only operations missing** — no `/prompts`, `/editprompt`, `/delprompt`,
  `/togglepublic`, `/tags`, `/edittag` yet. The scope for v1 was strictly
  *create prompt + create tag*.
- **No tests yet** — Nutgram ships a `FakeNutgram` helper and the package
  auto-binds it under `runningUnitTests()`; a feature test that drives the
  full `/addprompt` flow against an in-memory SQLite would be the right next
  step.
- **Body length** — Telegram caps a single message at ~4 000 chars. Longer
  prompt bodies need to be split (not currently supported by the wizard).
