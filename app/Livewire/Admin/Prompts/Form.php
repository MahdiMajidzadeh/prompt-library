<?php

namespace App\Livewire\Admin\Prompts;

use App\Models\Prompt;
use App\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?Prompt $prompt = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string')]
    public string $body = '';

    public bool $is_public = false;

    /** @var array<int, int> */
    public array $tagIds = [];

    public function mount(?Prompt $prompt = null): void
    {
        if ($prompt?->exists) {
            $this->prompt = $prompt;
            $this->title = $prompt->title;
            $this->body = $prompt->body;
            $this->is_public = $prompt->is_public;
            $this->tagIds = $prompt->tags->pluck('id')->all();
        }
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->prompt?->exists) {
            $this->prompt->fill([
                'title' => $data['title'],
                'body' => $data['body'],
                'is_public' => $this->is_public,
            ])->save();
            $this->prompt->tags()->sync($this->tagIds);
            $message = "Updated \"{$this->prompt->title}\".";
        } else {
            $prompt = Prompt::create([
                'title' => $data['title'],
                'body' => $data['body'],
                'is_public' => $this->is_public,
                'user_id' => auth()->id(),
            ]);
            $prompt->tags()->sync($this->tagIds);
            $message = "Created \"{$prompt->title}\".";
        }

        session()->flash('status', $message);

        $this->redirect(route('admin.prompts.index'), navigate: false);
    }

    public function delete(): void
    {
        if (! $this->prompt?->exists) {
            return;
        }

        $title = $this->prompt->title;
        $this->prompt->delete();

        session()->flash('status', "Deleted \"{$title}\".");
        $this->redirect(route('admin.prompts.index'), navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.prompts.form', [
            'tags' => Tag::orderBy('name')->get(),
            'isEdit' => $this->prompt?->exists ?? false,
        ])->title($this->prompt?->exists ? 'Admin · Edit prompt' : 'Admin · New prompt');
    }
}
