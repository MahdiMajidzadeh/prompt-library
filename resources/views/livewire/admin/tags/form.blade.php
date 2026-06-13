<div>
    <x-admin-shell active="tags">
        <x-slot:heading>{{ $isEdit ? 'Edit tag' : 'New tag' }}</x-slot:heading>
        @if ($isEdit)
            <x-slot:subheading>{{ $tag->slug }}</x-slot:subheading>
        @endif

        <form wire:submit="save" class="pl-card" style="cursor: default; padding: var(--spacing-6); gap: var(--spacing-5); max-width: 520px;" novalidate>
            <div style="display: flex; flex-direction: column; gap: var(--spacing-2);">
                <label for="name" class="pl-sidebar__label" style="margin: 0;">Name</label>
                <input id="name" type="text" class="pl-search__input" style="padding-left: var(--spacing-4);" wire:model="name">
                <p style="font-size: var(--text-xs); color: var(--color-text-tertiary); margin: 0;">The URL slug is derived from the name automatically.</p>
                @error('name')
                    <p style="font-size: var(--text-xs); color: #B91C1C; margin: 0;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-3); align-items: center; padding-top: var(--spacing-4); border-top: 1px solid var(--color-border);">
                <button type="submit" class="pl-btn pl-btn--primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Save changes' : 'Create tag' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
                <a href="{{ route('admin.tags.index') }}" class="pl-btn pl-btn--ghost">Cancel</a>
            </div>
        </form>
    </x-admin-shell>
</div>
