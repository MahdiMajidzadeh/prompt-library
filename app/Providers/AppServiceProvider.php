<?php

namespace App\Providers;

use App\Models\Prompt;
use App\Models\Tag;
use App\Observers\PromptObserver;
use App\Observers\TagObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sitemap auto-regenerates when public-facing content changes.
        Prompt::observe(PromptObserver::class);
        Tag::observe(TagObserver::class);
    }
}
