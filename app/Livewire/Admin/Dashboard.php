<?php

namespace App\Livewire\Admin;

use App\Models\Prompt;
use App\Models\PromptView;
use App\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Admin · Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'publicCount' => Prompt::where('is_public', true)->count(),
            'privateCount' => Prompt::where('is_public', false)->count(),
            'tagCount' => Tag::count(),
            'totalViews' => PromptView::count(),
            'totalViewCount' => (int) Prompt::sum('view_count'),
        ]);
    }
}
