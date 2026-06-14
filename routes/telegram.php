<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Telegram\Conversations\AddPromptConversation;
use App\Telegram\Conversations\AddTagConversation;
use App\Telegram\Middleware\RequireAdmin;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| Every public command is gated by RequireAdmin — the bot is admin-only.
| Conversation classes live in app/Telegram/Conversations and persist their
| state through the cache store (see config/nutgram.php:conversation_ttl).
|
*/

$bot->middleware(RequireAdmin::class);

$bot->onCommand('start', function (Nutgram $bot) {
    $bot->sendMessage(
        "👋 Hi admin.\n\n".
        "Commands:\n".
        "/addprompt — create a new prompt (wizard)\n".
        "/addtag — create a new tag\n".
        "/cancel — abort the current wizard\n".
        "/help — show this list"
    );
})->description('Show the welcome message and command list.');

$bot->onCommand('help', function (Nutgram $bot) {
    $bot->sendMessage(
        "Commands:\n".
        "/addprompt — wizard: title → body → public/private → tags\n".
        "/addtag — create a single tag by name\n".
        "/cancel — exit any active wizard"
    );
})->description('List available commands.');

$bot->onCommand('addprompt', function (Nutgram $bot) {
    AddPromptConversation::begin(
        bot: $bot,
        userId: $bot->userId(),
        chatId: $bot->chatId(),
    );
})->description('Create a new prompt (wizard).');

$bot->onCommand('addtag', function (Nutgram $bot) {
    AddTagConversation::begin(
        bot: $bot,
        userId: $bot->userId(),
        chatId: $bot->chatId(),
    );
})->description('Create a new tag.');

$bot->onCommand('cancel', function (Nutgram $bot) {
    $bot->endConversation();
    $bot->sendMessage('↩️ Cancelled.');
})->description('Abort the current wizard.');
