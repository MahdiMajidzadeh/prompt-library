<?php

namespace App\Livewire\Prompts;

use App\Livewire\Concerns\WithInfiniteScroll;
use App\Models\Prompt;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Latest prompts')]
class Latest extends Component
{
    use WithInfiniteScroll;

    public function render()
    {
        $total = Prompt::public()->count();

        $prompts = Prompt::public()
            ->with('tags')
            ->latest()
            ->take($this->perPage)
            ->get();

        return view('livewire.prompts.latest', [
            'prompts' => $prompts,
            'total' => $total,
            'hasMore' => $total > $prompts->count(),
        ])->layoutData([
            'description' => "The newest prompts added to the library — {$total} curated prompts for writing, coding, marketing, and analysis.",
        ]);
    }
}
