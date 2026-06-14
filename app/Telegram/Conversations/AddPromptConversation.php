<?php

namespace App\Telegram\Conversations;

use App\Models\Prompt;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

/**
 * Wizard that walks the admin through creating a Prompt:
 *
 *   title  →  body  →  visibility  →  tags  →  save
 *
 * The owning Laravel User is the first row with `is_admin = true`. We don't
 * yet link a specific Telegram user to a specific Laravel user — the
 * authorization layer ([[App\Telegram\Middleware\RequireAdmin]]) has already
 * confirmed the sender is on the env whitelist before this conversation runs.
 */
class AddPromptConversation extends Conversation
{
    public ?string $title = null;

    public ?string $body = null;

    public ?bool $isPublic = null;

    /** @var array<int, string> */
    public array $tagNames = [];

    public function start(Nutgram $bot): void
    {
        $bot->sendMessage("📝 Send the prompt title.\nSend /cancel any time to abort.");
        $this->next('collectTitle');
    }

    public function collectTitle(Nutgram $bot): void
    {
        $title = trim((string) $bot->message()?->text);

        if ($title === '' || mb_strlen($title) > 255) {
            $bot->sendMessage('Title must be 1–255 characters. Try again.');
            $this->next('collectTitle');

            return;
        }

        $this->title = $title;
        $bot->sendMessage('Now send the full prompt body in one message (up to ~4 000 characters).');
        $this->next('collectBody');
    }

    public function collectBody(Nutgram $bot): void
    {
        $body = trim((string) $bot->message()?->text);

        if ($body === '') {
            $bot->sendMessage('Body cannot be empty. Send the prompt body or /cancel.');
            $this->next('collectBody');

            return;
        }

        $this->body = $body;
        $bot->sendMessage("Visibility? Reply `public` or `private`.");
        $this->next('collectVisibility');
    }

    public function collectVisibility(Nutgram $bot): void
    {
        $answer = strtolower(trim((string) $bot->message()?->text));

        $this->isPublic = match ($answer) {
            'public', 'y', 'yes' => true,
            'private', 'n', 'no' => false,
            default => null,
        };

        if ($this->isPublic === null) {
            $bot->sendMessage("Didn't catch that. Reply `public` or `private`.");
            $this->next('collectVisibility');

            return;
        }

        $existing = Tag::orderBy('name')->pluck('name')->take(40)->implode(', ');
        $existingLine = $existing !== '' ? "\nExisting tags: {$existing}" : '';

        $bot->sendMessage(
            "Add tags as a comma-separated list, or send `skip` for none. New tags are created on the fly.{$existingLine}"
        );
        $this->next('collectTags');
    }

    public function collectTags(Nutgram $bot): void
    {
        $raw = trim((string) $bot->message()?->text);

        if (strtolower($raw) === 'skip' || $raw === '') {
            $this->tagNames = [];
        } else {
            $this->tagNames = collect(explode(',', $raw))
                ->map(fn ($s) => trim($s))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $this->save($bot);
    }

    protected function save(Nutgram $bot): void
    {
        $owner = User::where('is_admin', true)->orderBy('id')->first();

        if (! $owner) {
            $bot->sendMessage('❌ No admin user found in the database to own this prompt. Aborting.');
            $this->end();

            return;
        }

        $prompt = Prompt::create([
            'title' => $this->title,
            'body' => $this->body,
            'is_public' => $this->isPublic,
            'user_id' => $owner->id,
        ]);

        $tagIds = [];
        foreach ($this->tagNames as $name) {
            $slug = Str::slug($name);
            if ($slug === '') {
                continue;
            }
            $tag = Tag::firstOrCreate(['slug' => $slug], ['name' => $name]);
            $tagIds[] = $tag->id;
        }

        if ($tagIds !== []) {
            $prompt->tags()->sync($tagIds);
        }

        $visibility = $this->isPublic ? 'public' : 'private';
        $tagSummary = count($tagIds) > 0 ? count($tagIds).' tag(s)' : 'no tags';
        $url = $this->isPublic ? url(route('prompts.show', $prompt, false)) : '(private — admin only)';

        $bot->sendMessage(
            "✅ Saved “{$prompt->title}” ({$visibility}, {$tagSummary}).\n{$url}"
        );

        $this->end();
    }
}
