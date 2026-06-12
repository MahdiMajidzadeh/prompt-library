<?php

namespace App\Livewire\Admin\Tags;

use App\Models\Tag;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?Tag $tag = null;

    public string $name = '';

    public function mount(?Tag $tag = null): void
    {
        if ($tag?->exists) {
            $this->tag = $tag;
            $this->name = $tag->name;
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tags', 'name')->ignore($this->tag?->id),
            ],
        ]);

        if ($this->tag?->exists) {
            $this->tag->fill(['name' => $data['name']])->save();
            $message = "Updated tag \"{$this->tag->name}\".";
        } else {
            $tag = Tag::create(['name' => $data['name']]);
            $message = "Created tag \"{$tag->name}\".";
        }

        session()->flash('status', $message);

        $this->redirect(route('admin.tags.index'), navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.tags.form', [
            'isEdit' => $this->tag?->exists ?? false,
        ])->title($this->tag?->exists ? 'Admin · Edit tag' : 'Admin · New tag');
    }
}
