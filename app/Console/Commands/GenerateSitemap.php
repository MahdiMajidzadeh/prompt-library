<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Rebuild public/sitemap.xml from the current DB state.';

    public function handle(SitemapService $sitemap): int
    {
        $count = $sitemap->generate();
        $this->info("Sitemap regenerated with {$count} URLs → public/sitemap.xml");

        return self::SUCCESS;
    }
}
