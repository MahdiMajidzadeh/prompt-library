<?php

namespace App\Livewire\Mobile;

use App\Livewire\Concerns\WithInfiniteScroll;
use App\Models\Prompt;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.mobile')]
#[Title('Search')]
class Search extends Component
{
    use WithInfiniteScroll;

    #[Url(as: 'q', except: '')]
    public string $q = '';

    public function updatedQ(): void
    {
        $this->resetInfiniteScroll();
    }

    public function render()
    {
        $term = trim($this->q);

        if ($term === '') {
            return view('livewire.mobile.search', [
                'prompts' => collect(),
                'total' => 0,
                'hasMore' => false,
                'isEmptyQuery' => true,
            ]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $base = Prompt::public()
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhereHas('tags', fn ($t) => $t->where('name', 'like', $like));
            });

        $total = (clone $base)->distinct()->count('prompts.id');

        $prompts = $base
            ->with('tags')
            ->distinct()
            ->latest('prompts.created_at')
            ->take($this->perPage)
            ->get();

        return view('livewire.mobile.search', [
            'prompts' => $prompts,
            'total' => $total,
            'hasMore' => $total > $prompts->count(),
            'isEmptyQuery' => false,
        ]);
    }
}
