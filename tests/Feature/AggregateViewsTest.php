<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Models\PromptView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AggregateViewsTest extends TestCase
{
    use RefreshDatabase;

    private function makePrompt(): Prompt
    {
        $admin = User::factory()->admin()->create();

        return Prompt::factory()->public()->for($admin, 'user')->create(['view_count' => 0]);
    }

    private function seedViews(Prompt $prompt, int $n, bool $counted = false): void
    {
        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            $rows[] = [
                'prompt_id' => $prompt->id,
                'visitor_hash' => "hash-{$prompt->id}-{$i}",
                'counted' => $counted,
                'user_id' => null,
                'created_at' => now(),
            ];
        }
        PromptView::insert($rows);
    }

    public function test_aggregates_uncounted_rows_into_view_count(): void
    {
        $a = $this->makePrompt();
        $b = $this->makePrompt();
        $this->seedViews($a, 7);
        $this->seedViews($b, 3);

        $this->artisan('prompts:aggregate-views')->assertSuccessful();

        $this->assertSame(7, $a->fresh()->view_count);
        $this->assertSame(3, $b->fresh()->view_count);
        $this->assertSame(0, PromptView::where('counted', false)->count());
        $this->assertSame(10, PromptView::where('counted', true)->count());
    }

    public function test_aggregation_is_idempotent(): void
    {
        $prompt = $this->makePrompt();
        $this->seedViews($prompt, 5);

        $this->artisan('prompts:aggregate-views')->assertSuccessful();
        $this->assertSame(5, $prompt->fresh()->view_count);

        $this->artisan('prompts:aggregate-views')->assertSuccessful();
        $this->assertSame(5, $prompt->fresh()->view_count, 'Second run must not double-count.');
    }

    public function test_already_counted_rows_are_ignored(): void
    {
        $prompt = $this->makePrompt();
        $this->seedViews($prompt, 4, counted: true);
        $this->seedViews($prompt, 6, counted: false);

        $this->artisan('prompts:aggregate-views')->assertSuccessful();

        $this->assertSame(6, $prompt->fresh()->view_count, 'Only the uncounted batch should be folded in.');
        $this->assertSame(0, PromptView::where('counted', false)->count());
    }

    public function test_aggregation_with_no_uncounted_rows_is_a_noop(): void
    {
        $prompt = $this->makePrompt();
        $this->seedViews($prompt, 4, counted: true);
        // Set view_count out of band — it isn't mass-assignable, only the cron writes it.
        Prompt::where('id', $prompt->id)->update(['view_count' => 100]);

        $this->artisan('prompts:aggregate-views')->assertSuccessful();

        $this->assertSame(100, $prompt->fresh()->view_count);
    }
}
