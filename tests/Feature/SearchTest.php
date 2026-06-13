<?php

namespace Tests\Feature;

use App\Livewire\Search;
use App\Models\Prompt;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_search_matches_title(): void
    {
        $admin = $this->admin();
        Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Banana bread recipe']);
        Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Sourdough starter guide']);

        Livewire::test(Search::class)
            ->set('q', 'banana')
            ->assertSee('Banana bread recipe')
            ->assertDontSee('Sourdough starter guide');
    }

    public function test_search_matches_tag_name(): void
    {
        $admin = $this->admin();
        $coding = Tag::factory()->create(['name' => 'CodingTag']);

        $tagged = Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Tagged prompt LMNOP']);
        $tagged->tags()->attach($coding->id);

        Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Untagged prompt VWXYZ']);

        Livewire::test(Search::class)
            ->set('q', 'CodingTag')
            ->assertSee('Tagged prompt LMNOP')
            ->assertDontSee('Untagged prompt VWXYZ');
    }

    public function test_search_does_not_match_body_text(): void
    {
        $admin = $this->admin();
        $token = 'zzunique_body_token_99887766';

        Prompt::factory()->public()->for($admin, 'user')->create([
            'title' => 'Some unrelated title',
            'body' => "This body contains the {$token} which should never be searched.",
        ]);

        Livewire::test(Search::class)
            ->set('q', $token)
            ->assertDontSee('Some unrelated title');
    }

    public function test_empty_query_shows_prompt_message(): void
    {
        Livewire::test(Search::class)
            ->set('q', '')
            ->assertSee('Use the search bar above');
    }

    public function test_no_matches_shows_no_results_state(): void
    {
        $admin = $this->admin();
        Prompt::factory()->public()->for($admin, 'user')->create(['title' => 'Apple pie']);

        Livewire::test(Search::class)
            ->set('q', 'nonexistent_term_xyz_123')
            ->assertSee('No matches');
    }

    public function test_q_is_reflected_in_url(): void
    {
        $component = Livewire::withUrlParams(['q' => 'banana'])->test(Search::class);

        $this->assertSame('banana', $component->get('q'));
    }
}
