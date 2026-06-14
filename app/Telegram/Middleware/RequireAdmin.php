<?php

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Nutgram;

/**
 * Blocks every Telegram sender whose user id is not in the
 * config('nutgram.admin_ids') whitelist. When the list is empty the bot
 * effectively rejects everyone — a fail-closed default.
 */
class RequireAdmin
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $userId = $bot->userId();
        $admins = config('nutgram.admin_ids', []);

        if ($userId === null || ! in_array($userId, $admins, true)) {
            $bot->sendMessage('🚫 This bot is restricted to administrators.');

            return;
        }

        $next($bot);
    }
}
