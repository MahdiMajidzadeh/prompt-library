<?php

namespace App\Services;

use App\Models\Prompt;
use App\Models\Tag;
use Illuminate\Support\Carbon;

/**
 * Builds public/sitemap.xml from the current DB state.
 *
 * Called manually via `php artisan sitemap:generate` and automatically
 * whenever a public Prompt or any Tag is created/updated/deleted (see
 * App\Observers\PromptObserver, App\Observers\TagObserver).
 */
class SitemapService
{
    /**
     * Static landing pages always present regardless of DB state.
     *
     * @return array<int, array{loc: string, changefreq: string, priority: string}>
     */
    protected function staticUrls(): array
    {
        return [
            ['loc' => route('home'),                'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('prompts.latest'),      'changefreq' => 'daily',  'priority' => '0.8'],
            ['loc' => route('prompts.most-viewed'), 'changefreq' => 'daily',  'priority' => '0.8'],
            ['loc' => route('tags.index'),          'changefreq' => 'weekly', 'priority' => '0.7'],
        ];
    }

    public function generate(): int
    {
        $now = Carbon::now()->toAtomString();

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->staticUrls() as $url) {
            $lines[] = $this->urlNode($url['loc'], $now, $url['changefreq'], $url['priority']);
        }

        Tag::query()
            ->whereHas('prompts', fn ($q) => $q->public())
            ->orderBy('name')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Tag $tag) use (&$lines) {
                $lines[] = $this->urlNode(
                    route('tags.show', $tag),
                    Carbon::parse($tag->updated_at)->toAtomString(),
                    'weekly',
                    '0.6',
                );
            });

        Prompt::query()
            ->public()
            ->orderByDesc('updated_at')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Prompt $prompt) use (&$lines) {
                $lines[] = $this->urlNode(
                    route('prompts.show', $prompt),
                    Carbon::parse($prompt->updated_at)->toAtomString(),
                    'monthly',
                    '0.9',
                );
            });

        $lines[] = '</urlset>';

        $xml = implode("\n", $lines)."\n";
        $count = count($lines) - 3; // minus xml header, urlset open, urlset close

        $path = public_path('sitemap.xml');
        $tmp = $path.'.tmp';

        // Write-then-rename so a half-written sitemap is never served.
        file_put_contents($tmp, $xml);
        rename($tmp, $path);

        return $count;
    }

    protected function urlNode(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        $loc = htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "  <url>\n".
            "    <loc>{$loc}</loc>\n".
            "    <lastmod>{$lastmod}</lastmod>\n".
            "    <changefreq>{$changefreq}</changefreq>\n".
            "    <priority>{$priority}</priority>\n".
            '  </url>';
    }
}
