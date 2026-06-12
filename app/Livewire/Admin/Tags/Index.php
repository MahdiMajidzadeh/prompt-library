<?php

namespace App\Livewire\Admin\Tags;

use App\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Admin · Tags')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $tags = Tag::query()
            ->withCount('prompts')
            ->orderBy('name')
            ->paginate(50);

        return view('livewire.admin.tags.index', [
            'tags' => $tags,
        ]);
    }
}
