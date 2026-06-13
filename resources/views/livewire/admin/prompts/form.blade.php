<div>
    <x-admin-shell active="prompts">
        <x-slot:heading>{{ $isEdit ? 'Edit prompt' : 'New prompt' }}</x-slot:heading>
        @if ($isEdit)
            <x-slot:subheading>{{ $prompt->slug }}</x-slot:subheading>
        @endif

        <form wire:submit="save" class="pl-card" style="cursor: default; padding: var(--spacing-6); gap: var(--spacing-5);" novalidate>
            <div style="display: flex; flex-direction: column; gap: var(--spacing-2);">
                <label for="title" class="pl-sidebar__label" style="margin: 0;">Title</label>
                <input id="title" type="text" class="pl-search__input" style="padding-left: var(--spacing-4);" wire:model="title">
                @error('title')
                    <p style="font-size: var(--text-xs); color: #B91C1C; margin: 0;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: var(--spacing-2);">
                <label for="body" class="pl-sidebar__label" style="margin: 0;">Body</label>
                <textarea
                    id="body"
                    class="pl-search__input"
                    style="padding: var(--spacing-3) var(--spacing-4); height: auto; min-height: 280px; font-family: var(--font-mono); line-height: var(--leading-relaxed); resize: vertical;"
                    wire:model="body"
                ></textarea>
                <p style="font-size: var(--text-xs); color: var(--color-text-tertiary); margin: 0;">Plain text. Line breaks are preserved.</p>
                @error('body')
                    <p style="font-size: var(--text-xs); color: #B91C1C; margin: 0;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: var(--spacing-2);">
                <label class="pl-sidebar__label" style="margin: 0;">Tags</label>
                <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-2);">
                    @foreach ($tags as $tag)
                        @php $active = in_array($tag->id, $tagIds, true); @endphp
                        <button
                            type="button"
                            class="pl-tag {{ $active ? 'pl-tag--active' : '' }}"
                            wire:click="$toggle('tagIds.{{ $tag->id }}')"
                        >
                            {{ $tag->name }}
                        </button>
                    @endforeach
                </div>
                <p style="font-size: var(--text-xs); color: var(--color-text-tertiary); margin: 0;">Click to add or remove. {{ count($tagIds) }} selected.</p>
            </div>

            <label style="display: inline-flex; align-items: center; gap: var(--spacing-2); font-size: var(--text-sm); cursor: pointer;">
                <input type="checkbox" wire:model="is_public">
                <span>Visible on the public side</span>
            </label>

            <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-3); align-items: center; padding-top: var(--spacing-4); border-top: 1px solid var(--color-border);">
                <button type="submit" class="pl-btn pl-btn--primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Save changes' : 'Create prompt' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
                <a href="{{ route('admin.prompts.index') }}" class="pl-btn pl-btn--ghost">Cancel</a>

                @if ($isEdit)
                    <button
                        type="button"
                        class="pl-btn pl-btn--ghost"
                        style="color: #B91C1C; margin-left: auto;"
                        wire:click="delete"
                        wire:confirm="Delete \"{{ $prompt->title }}\"? This cannot be undone."
                    >
                        Delete
                    </button>
                @endif
            </div>
        </form>
    </x-admin-shell>
</div>
