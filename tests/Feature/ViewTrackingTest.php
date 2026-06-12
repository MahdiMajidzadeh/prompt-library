<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Models\PromptView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function publicPrompt(): Prompt
    {
        $admin = User::factory()->admin()->create();

        return Prompt::factory()->public()->for($admin, 'user')->create();
    }

    public function test_visiting_public_detail_inserts_one_view_row(): void
    {
        $prompt = $this->publicPrompt();

        $this->assertSame(0, PromptView::count());

        $this->get(route('prompts.show', $prompt))->assertOk();

        $this->assertSame(1, PromptView::where('prompt_id', $prompt->id)->count());
    }

    public function test_dedupe_collapses_rapid_repeat_visits_from_same_fingerprint(): void
    {
        $prompt = $this->publicPrompt();
        $hash = 'fingerprint-aaa';

        $prompt->recordView($hash);
        $prompt->recordView($hash);
        $prompt->recordView($hash);

        $this->assertSame(1, PromptView::where('prompt_id', $prompt->id)->count());
    }

    public function test_different_fingerprint_records_a_separate_row(): void
    {
        $prompt = $this->publicPrompt();

        $prompt->recordView('fingerprint-aaa');
        $prompt->recordView('fingerprint-bbb');

        $this->assertSame(2, PromptView::where('prompt_id', $prompt->id)->count());
    }

    public function test_dedupe_window_expires_after_30_seconds(): void
    {
        $prompt = $this->publicPrompt();
        $hash = 'fingerprint-aaa';

        $prompt->recordView($hash);
        $this->assertSame(1, PromptView::where('prompt_id', $prompt->id)->count());

        // Backdate the first row past the 30-second window.
        PromptView::where('prompt_id', $prompt->id)
            ->where('visitor_hash', $hash)
            ->update(['created_at' => now()->subSeconds(31)]);

        $prompt->recordView($hash);

        $this->assertSame(2, PromptView::where('prompt_id', $prompt->id)->count());
    }

    public function test_private_prompt_visit_does_not_insert_a_view(): void
    {
        $admin = User::factory()->admin()->create();
        $private = Prompt::factory()->private()->for($admin, 'user')->create();

        $this->get(route('prompts.show', $private))->assertNotFound();

        $this->assertSame(0, PromptView::where('prompt_id', $private->id)->count());
    }

    public function test_recorded_rows_are_uncounted_by_default(): void
    {
        $prompt = $this->publicPrompt();
        $prompt->recordView('any-hash');

        $row = PromptView::where('prompt_id', $prompt->id)->first();

        $this->assertNotNull($row);
        $this->assertFalse($row->counted);
        $this->assertSame('any-hash', $row->visitor_hash);
    }
}
