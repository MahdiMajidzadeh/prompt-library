<?php

namespace App\Console\Commands;

use App\Models\Prompt;
use App\Models\PromptView;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Folds uncounted prompt_views rows into the denormalized prompts.view_count cache.
 *
 * Incremental algorithm:
 *   1. Snapshot the max id of currently-uncounted rows so concurrent inserts
 *      that arrive mid-run are not marked counted prematurely.
 *   2. Group counts by prompt_id, restricted to ids <= snapshot.
 *   3. Per prompt, increment view_count and mark its uncounted rows counted,
 *      both inside a single transaction.
 *
 * Alternative (simpler but rescans the whole table every run):
 *   UPDATE prompts SET view_count = (
 *     SELECT COUNT(*) FROM prompt_views WHERE prompt_id = prompts.id
 *   );
 * The incremental approach above is preferred for write volume reasons.
 */
class AggregatePromptViews extends Command
{
    protected $signature = 'prompts:aggregate-views';

    protected $description = 'Fold uncounted prompt_views rows into prompts.view_count.';

    public function handle(): int
    {
        $maxId = PromptView::where('counted', false)->max('id');

        if ($maxId === null) {
            $this->info('No uncounted views to aggregate.');
            $this->logSummary(0, 0);

            return self::SUCCESS;
        }

        $groups = PromptView::query()
            ->where('counted', false)
            ->where('id', '<=', $maxId)
            ->selectRaw('prompt_id, COUNT(*) AS c')
            ->groupBy('prompt_id')
            ->get();

        $promptsTouched = 0;
        $rowsFolded = 0;

        foreach ($groups as $group) {
            DB::transaction(function () use ($group, $maxId, &$promptsTouched, &$rowsFolded) {
                Prompt::where('id', $group->prompt_id)->increment('view_count', $group->c);

                PromptView::where('prompt_id', $group->prompt_id)
                    ->where('counted', false)
                    ->where('id', '<=', $maxId)
                    ->update(['counted' => true]);

                $promptsTouched++;
                $rowsFolded += (int) $group->c;
            });
        }

        $this->info("Aggregated views: prompts={$promptsTouched}, rows={$rowsFolded}.");
        $this->logSummary($promptsTouched, $rowsFolded);

        return self::SUCCESS;
    }

    private function logSummary(int $prompts, int $rows): void
    {
        logger()->info('prompts:aggregate-views', [
            'prompts_touched' => $prompts,
            'rows_folded' => $rows,
        ]);
    }
}
