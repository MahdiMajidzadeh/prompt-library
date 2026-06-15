<?php

namespace App\Observers;

use App\Models\Tag;
use App\Services\SitemapService;
use Throwable;

/**
 * Regenerates public/sitemap.xml when the set of public-tag URLs changes.
 * Same fail-soft contract as [[App\Observers\PromptObserver]].
 */
class TagObserver
{
    public function __construct(protected SitemapService $sitemap) {}

    public function created(Tag $tag): void
    {
        $this->regenerate();
    }

    public function updated(Tag $tag): void
    {
        if ($tag->wasChanged('slug') || $tag->wasChanged('name')) {
            $this->regenerate();
        }
    }

    public function deleted(Tag $tag): void
    {
        $this->regenerate();
    }

    protected function regenerate(): void
    {
        try {
            app(SitemapService::class)->generate();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
