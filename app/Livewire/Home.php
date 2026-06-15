<?php

namespace App\Livewire;

use App\Models\Prompt;
use App\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Prompt Library')]
class Home extends Component
{
    public function render()
    {
        $mostViewed = Prompt::public()
            ->with('tags')
            ->orderByDesc('view_count')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        $latest = Prompt::public()
            ->with('tags')
            ->latest()
            ->take(6)
            ->get();

        $tags = Tag::query()
            ->whereHas('prompts', fn ($q) => $q->public())
            ->withCount(['prompts' => fn ($q) => $q->public()])
            ->orderBy('name')
            ->get();

        $totalPublic = Prompt::public()->count();

        return view('livewire.home', [
            'mostViewed' => $mostViewed,
            'latest' => $latest,
            'tags' => $tags,
            'totalPublic' => $totalPublic,
        ])->layoutData([
            'description' => "Browse {$totalPublic} curated AI prompts. Search, copy, and explore prompts for writing, coding, marketing, analysis, and productivity.",
        ]);
    }
}
