<?php

namespace App\Observers;

use App\Models\Prompt;
use App\Services\SitemapService;
use Throwable;

/**
 * Regenerates public/sitemap.xml whenever the set of public-prompt URLs
 * changes. A sitemap write must never break a save — failures are swallowed
 * and the next successful save (or `php artisan sitemap:generate`) will
 * heal the file.
 */
class PromptObserver
{
    public function __construct(protected SitemapService $sitemap) {}

    public function created(Prompt $prompt): void
    {
        if ($prompt->is_public) {
            $this->regenerate();
        }
    }

    public function updated(Prompt $prompt): void
    {
        // Regenerate when the public set could have shifted: visibility changed,
        // slug changed, or the prompt is currently public (so its lastmod moves).
        $visibilityFlipped = $prompt->wasChanged('is_public');
        $slugChanged = $prompt->wasChanged('slug');

        if ($visibilityFlipped || $slugChanged || $prompt->is_public) {
            $this->regenerate();
        }
    }

    public function deleted(Prompt $prompt): void
    {
        if ($prompt->is_public) {
            $this->regenerate();
        }
    }

    protected function regenerate(): void
    {
        try {
            $this->sitemap->generate();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
