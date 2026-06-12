<?php

namespace Database\Seeders;

use App\Models\Prompt;
use App\Models\PromptView;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', true)->firstOrFail();
        $tagIds = Tag::pluck('id')->all();

        if (empty($tagIds)) {
            $this->command?->warn('PromptSeeder: no tags found — run TagSeeder first.');

            return;
        }

        $prompts = Prompt::factory()
            ->count(30)
            ->for($admin, 'user')
            ->create();

        foreach ($prompts as $prompt) {
            $count = random_int(1, 4);
            $picks = collect($tagIds)->shuffle()->take($count)->all();
            $prompt->tags()->attach($picks);
        }

        $publicPrompts = $prompts->where('is_public', true);
        $sampled = $publicPrompts->shuffle()->take(10);

        $rows = [];
        foreach ($sampled as $prompt) {
            $n = random_int(5, 50);
            for ($i = 0; $i < $n; $i++) {
                $rows[] = [
                    'prompt_id' => $prompt->id,
                    'visitor_hash' => hash('sha256', $prompt->id.'|'.random_int(1, 9999).'|'.$i),
                    'counted' => false,
                    'user_id' => null,
                    'created_at' => now()->subMinutes(random_int(0, 60 * 24 * 7)),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            PromptView::insert($chunk);
        }
    }
}
