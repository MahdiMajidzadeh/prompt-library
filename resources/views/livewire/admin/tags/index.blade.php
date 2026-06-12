<div>
    <x-admin-shell active="tags">
        <x-slot:heading>Tags</x-slot:heading>
        <x-slot:subheading>{{ $tags->total() }} {{ $tags->total() === 1 ? 'tag' : 'tags' }}</x-slot:subheading>

        <div style="display: flex; justify-content: flex-end; margin-bottom: var(--spacing-5);">
            <a href="{{ route('admin.tags.create') }}" class="pl-btn pl-btn--primary">+ New tag</a>
        </div>

        @if ($tags->isEmpty())
            <div class="pl-empty">
                <h2 class="pl-empty__title">No tags yet</h2>
                <p class="pl-empty__text">Create your first tag.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: var(--spacing-2);">
                @foreach ($tags as $tag)
                    <div class="pl-card pl-card--row" wire:key="tag-{{ $tag->id }}" style="cursor: default;">
                        <div class="pl-card__main">
                            <div style="display: flex; align-items: baseline; gap: var(--spacing-3); flex-wrap: wrap;">
                                <h3 class="pl-card__title" style="font-size: var(--text-base);">
                                    <a href="{{ route('admin.tags.edit', $tag) }}" style="color: var(--color-text);">{{ $tag->name }}</a>
                                </h3>
                                <span class="pl-meta" style="font-family: var(--font-mono);">{{ $tag->slug }}</span>
                                <span class="pl-meta">{{ $tag->prompts_count }} {{ $tag->prompts_count === 1 ? 'prompt' : 'prompts' }}</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.tags.edit', $tag) }}" class="pl-btn pl-btn--secondary" style="height: 36px; padding: 0 var(--spacing-3); font-size: var(--text-xs);">Edit</a>
                    </div>
                @endforeach
            </div>

            <div style="margin-top: var(--spacing-6);">
                {{ $tags->onEachSide(1)->links() }}
            </div>
        @endif
    </x-admin-shell>
</div>
