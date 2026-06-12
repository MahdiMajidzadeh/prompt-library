<?php

namespace App\Livewire\Prompts;

use App\Livewire\Concerns\WithInfiniteScroll;
use App\Models\Prompt;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Most viewed prompts')]
class MostViewed extends Component
{
    use WithInfiniteScroll;

    public function render()
    {
        $total = Prompt::public()->count();

        $prompts = Prompt::public()
            ->with('tags')
            ->orderByDesc('view_count')
            ->orderByDesc('id')
            ->take($this->perPage)
            ->get();

        return view('livewire.prompts.most-viewed', [
            'prompts' => $prompts,
            'total' => $total,
            'hasMore' => $total > $prompts->count(),
        ]);
    }
}
