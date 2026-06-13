<div>
    <x-admin-shell active="prompts">
        <x-slot:heading>Prompts</x-slot:heading>
        <x-slot:subheading>{{ $prompts->total() }} {{ $prompts->total() === 1 ? 'prompt' : 'prompts' }}</x-slot:subheading>

        <div style="display: flex; gap: var(--spacing-3); flex-wrap: wrap; align-items: center; margin-bottom: var(--spacing-5);">
            <div class="pl-search" style="flex: 1; min-width: 280px; max-width: 420px;">
                <svg class="pl-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" class="pl-search__input" placeholder="Search titles…" wire:model.live.debounce.300ms="q">
            </div>

            <div class="seg" role="group" aria-label="Visibility filter">
                <button type="button" class="seg__btn" wire:click="$set('visibility', 'all')" aria-pressed="{{ $visibility === 'all' ? 'true' : 'false' }}">All</button>
                <button type="button" class="seg__btn" wire:click="$set('visibility', 'public')" aria-pressed="{{ $visibility === 'public' ? 'true' : 'false' }}">Public</button>
                <button type="button" class="seg__btn" wire:click="$set('visibility', 'private')" aria-pressed="{{ $visibility === 'private' ? 'true' : 'false' }}">Private</button>
            </div>

            <a href="{{ route('admin.prompts.create') }}" class="pl-btn pl-btn--primary" style="margin-left: auto;">+ New prompt</a>
        </div>

        @if ($prompts->isEmpty())
            <div class="pl-empty">
                <div class="pl-empty__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                    </svg>
                </div>
                <h2 class="pl-empty__title">No prompts match</h2>
                <p class="pl-empty__text">Adjust the filter or create one.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: var(--spacing-2);">
                @foreach ($prompts as $prompt)
                    <div class="pl-card pl-card--row" wire:key="prompt-{{ $prompt->id }}" style="cursor: default;">
                        <div class="pl-card__main">
                            <div style="display: flex; align-items: baseline; gap: var(--spacing-3); flex-wrap: wrap;">
                                <h3 class="pl-card__title" style="font-size: var(--text-base);">
                                    <a href="{{ route('admin.prompts.edit', $prompt) }}" style="color: var(--color-text);">{{ $prompt->title }}</a>
                                </h3>
                                <span class="pl-tag pl-tag--sm pl-tag--static" style="{{ $prompt->is_public ? 'background: var(--color-accent); color: var(--color-accent-contrast); border-color: var(--color-accent);' : '' }}">
                                    {{ $prompt->is_public ? 'Public' : 'Private' }}
                                </span>
                                <span class="pl-meta">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    {{ \Illuminate\Support\Number::abbreviate($prompt->view_count) }}
                                </span>
                                <span class="pl-meta">{{ $prompt->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="pl-card__tags">
                                @foreach ($prompt->tags as $tag)
                                    <span class="pl-tag pl-tag--sm pl-tag--static">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-2); flex-shrink: 0;">
                            <button
                                type="button"
                                class="pl-btn pl-btn--secondary"
                                style="height: 36px; padding: 0 var(--spacing-3); font-size: var(--text-xs);"
                                wire:click="togglePublic({{ $prompt->id }})"
                                wire:loading.attr="disabled"
                                wire:target="togglePublic({{ $prompt->id }})"
                            >
                                {{ $prompt->is_public ? 'Make private' : 'Make public' }}
                            </button>
                            <a href="{{ route('admin.prompts.edit', $prompt) }}" class="pl-btn pl-btn--secondary" style="height: 36px; padding: 0 var(--spacing-3); font-size: var(--text-xs);">Edit</a>
                            <button
                                type="button"
                                class="pl-btn pl-btn--ghost"
                                style="height: 36px; padding: 0 var(--spacing-3); font-size: var(--text-xs); color: #B91C1C;"
                                wire:click="delete({{ $prompt->id }})"
                                wire:confirm="Delete \"{{ $prompt->title }}\"? This cannot be undone."
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top: var(--spacing-6);">
                {{ $prompts->onEachSide(1)->links() }}
            </div>
        @endif
    </x-admin-shell>
</div>
