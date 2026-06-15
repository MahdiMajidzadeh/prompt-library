<?php

namespace App\Livewire\Prompts;

use App\Models\Prompt;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Most viewed prompts')]
class MostViewed extends Component
{
    /** Hard cap on the page — no infinite scroll, no load-more. */
    private const CAP = 20;

    public function render()
    {
        $prompts = Prompt::public()
            ->with('tags')
            ->orderByDesc('view_count')
            ->orderByDesc('id')
            ->take(self::CAP)
            ->get();

        return view('livewire.prompts.most-viewed', [
            'prompts' => $prompts,
            'total' => $prompts->count(),
            'hasMore' => false,
        ])->layoutData([
            'description' => 'The most-copied AI prompts in the library — ranked by view count.',
        ]);
    }
}
