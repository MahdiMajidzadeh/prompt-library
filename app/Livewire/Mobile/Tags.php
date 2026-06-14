<?php

namespace App\Livewire\Mobile;

use App\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.mobile')]
#[Title('Tags')]
class Tags extends Component
{
    public function render()
    {
        $tags = Tag::query()
            ->whereHas('prompts', fn ($q) => $q->public())
            ->withCount(['prompts' => fn ($q) => $q->public()])
            ->orderBy('name')
            ->get();

        return view('livewire.mobile.tags', [
            'tags' => $tags,
        ]);
    }
}
