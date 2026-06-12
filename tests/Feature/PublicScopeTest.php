<?php

namespace Tests\Feature;

use App\Livewire\Home;
use App\Livewire\Prompts\Latest;
use App\Livewire\Prompts\MostViewed;
use App\Livewire\Search;
use App\Livewire\Tags\Show as TagShow;
use App\Models\Prompt;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicScopeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_home_shows_public_prompts_and_hides_private(): void
    {
        $admin = $this->admin();
        $publicPrompt = Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Public title XYZ', 'view_count' => 5]);
        $privatePrompt = Prompt::factory()->private()->for($admin, 'user')->create(['title' => 'Private title QQQ']);

        Livewire::test(Home::class)
            ->assertSee('Public title XYZ')
            ->assertDontSee('Private title QQQ');
    }

    public function test_latest_listing_hides_private_prompts(): void
    {
        $admin = $this->admin();
        Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Public latest']);
        Prompt::factory()->private()->for($admin, 'user')->create(['title' => 'Private latest']);

        Livewire::test(Latest::class)
            ->assertSee('Public latest')
            ->assertDontSee('Private latest');
    }

    public function test_most_viewed_listing_hides_private_prompts(): void
    {
        $admin = $this->admin();
        Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Public popular', 'view_count' => 99]);
        Prompt::factory()->private()->for($admin, 'user')->create(['title' => 'Private popular', 'view_count' => 9999]);

        Livewire::test(MostViewed::class)
            ->assertSee('Public popular')
            ->assertDontSee('Private popular');
    }

    public function test_tag_page_hides_private_prompts(): void
    {
        $admin = $this->admin();
        $tag = Tag::factory()->create(['name' => 'Coding']);

        $public = Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Public tagged']);
        $private = Prompt::factory()->private()->for($admin, 'user')->create(['title' => 'Private tagged']);

        $public->tags()->attach($tag->id);
        $private->tags()->attach($tag->id);

        Livewire::test(TagShow::class, ['tag' => $tag])
            ->assertSee('Public tagged')
            ->assertDontSee('Private tagged');
    }

    public function test_unknown_tag_returns_404(): void
    {
        $this->get('/tags/does-not-exist')->assertNotFound();
    }

    public function test_search_hides_private_prompts(): void
    {
        $admin = $this->admin();
        Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Search hit public']);
        Prompt::factory()->private()->for($admin, 'user')->create(['title' => 'Search hit private']);

        Livewire::test(Search::class)
            ->set('q', 'Search hit')
            ->assertSee('Search hit public')
            ->assertDontSee('Search hit private');
    }

    public function test_private_prompt_detail_returns_404(): void
    {
        $admin = $this->admin();
        $private = Prompt::factory()->private()->for($admin, 'user')->create();
        $public = Prompt::factory()->public()->for($admin, 'user')->create();

        $this->get(route('prompts.show', $private))->assertNotFound();
        $this->get(route('prompts.show', $public))->assertOk();
    }

    public function test_home_excludes_tags_with_no_public_prompts(): void
    {
        $admin = $this->admin();
        $emptyTag = Tag::factory()->create(['name' => 'EmptyTag']);
        $usedTag = Tag::factory()->create(['name' => 'UsedTag']);

        $prompt = Prompt::factory()->public()->for($admin, 'user')->create();
        $prompt->tags()->attach($usedTag->id);

        // A private prompt carrying the otherwise-empty tag must not rescue the tag.
        $privatePrompt = Prompt::factory()->private()->for($admin, 'user')->create();
        $privatePrompt->tags()->attach($emptyTag->id);

        Livewire::test(Home::class)
            ->assertSee('UsedTag')
            ->assertDontSee('EmptyTag');
    }
}
