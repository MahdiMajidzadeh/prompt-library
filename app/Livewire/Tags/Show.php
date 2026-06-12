<?php

namespace App\Livewire\Tags;

use App\Livewire\Concerns\WithInfiniteScroll;
use App\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    use WithInfiniteScroll;

    public Tag $tag;

    public function mount(Tag $tag): void
    {
        $this->tag = $tag;
    }

    public function render()
    {
        $query = $this->tag->prompts()->public();

        $total = (clone $query)->count();

        $prompts = $query
            ->with('tags')
            ->latest('prompts.created_at')
            ->take($this->perPage)
            ->get();

        return view('livewire.tags.show', [
            'prompts' => $prompts,
            'total' => $total,
            'hasMore' => $total > $prompts->count(),
        ])->title('Tag: '.$this->tag->name);
    }
}
