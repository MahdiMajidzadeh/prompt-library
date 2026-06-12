<?php

namespace App\Livewire\Admin\Prompts;

use App\Models\Prompt;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Admin · Prompts')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $q = '';

    #[Url(as: 'visibility', except: 'all')]
    public string $visibility = 'all';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedVisibility(): void
    {
        $this->resetPage();
    }

    public function togglePublic(int $promptId): void
    {
        $prompt = Prompt::findOrFail($promptId);
        $prompt->is_public = ! $prompt->is_public;
        $prompt->save();

        session()->flash('status', sprintf(
            'Prompt "%s" is now %s.',
            $prompt->title,
            $prompt->is_public ? 'public' : 'private',
        ));
    }

    public function delete(int $promptId): void
    {
        $prompt = Prompt::findOrFail($promptId);
        $title = $prompt->title;
        $prompt->delete();

        session()->flash('status', "Deleted \"{$title}\".");
        $this->resetPage();
    }

    public function render()
    {
        $term = trim($this->q);
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $prompts = Prompt::query()
            ->with('tags')
            ->when($term !== '', fn ($q) => $q->where('title', 'like', $like))
            ->when($this->visibility === 'public', fn ($q) => $q->where('is_public', true))
            ->when($this->visibility === 'private', fn ($q) => $q->where('is_public', false))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.prompts.index', [
            'prompts' => $prompts,
        ]);
    }
}
