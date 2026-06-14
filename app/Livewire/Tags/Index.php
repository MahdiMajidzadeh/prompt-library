<?php

namespace App\Livewire\Tags;

use App\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tags')]
class Index extends Component
{
    public function render()
    {
        $tags = Tag::query()
            ->whereHas('prompts', fn ($q) => $q->public())
            ->withCount(['prompts' => fn ($q) => $q->public()])
            ->orderBy('name')
            ->get();

        return view('livewire.tags.index', [
            'tags' => $tags,
        ]);
    }
}
