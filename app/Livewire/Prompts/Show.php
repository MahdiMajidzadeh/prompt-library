<?php

namespace App\Livewire\Prompts;

use App\Models\Prompt;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Prompt $prompt;

    public function mount(Prompt $prompt): void
    {
        if (! $prompt->is_public) {
            abort(404);
        }

        $this->prompt = $prompt->load('tags');

        $visitorHash = hash(
            'sha256',
            request()->ip().'|'.request()->userAgent(),
        );

        $this->prompt->recordView($visitorHash);
    }

    public function render()
    {
        $tagIds = $this->prompt->tags->pluck('id');

        $related = Prompt::public()
            ->where('id', '!=', $this->prompt->id)
            ->when($tagIds->isNotEmpty(), fn ($q) => $q
                ->whereHas('tags', fn ($t) => $t->whereIn('tags.id', $tagIds)))
            ->with('tags')
            ->orderByDesc('view_count')
            ->take(6)
            ->get();

        return view('livewire.prompts.show', [
            'related' => $related,
        ])->title($this->prompt->title);
    }
}
