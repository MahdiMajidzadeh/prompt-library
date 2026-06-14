<?php

namespace App\Telegram\Conversations;

use App\Models\Tag;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class AddTagConversation extends Conversation
{
    public function start(Nutgram $bot): void
    {
        $bot->sendMessage("🏷 Send the new tag name (1–32 characters).\nSend /cancel to abort.");
        $this->next('collectName');
    }

    public function collectName(Nutgram $bot): void
    {
        $name = trim((string) $bot->message()?->text);

        if ($name === '' || mb_strlen($name) > 32) {
            $bot->sendMessage('Name must be 1–32 characters. Try again, or /cancel.');
            $this->next('collectName');

            return;
        }

        $slug = Str::slug($name);

        if ($slug === '') {
            $bot->sendMessage('That name has no letters or digits I can slugify. Try again.');
            $this->next('collectName');

            return;
        }

        $existing = Tag::where('slug', $slug)->orWhere('name', $name)->first();

        if ($existing) {
            $bot->sendMessage("ℹ️ Tag “{$existing->name}” already exists. Nothing to do.");
            $this->end();

            return;
        }

        $tag = Tag::create(['name' => $name]);

        $bot->sendMessage("✅ Created tag “{$tag->name}” (slug: {$tag->slug}).");
        $this->end();
    }
}
