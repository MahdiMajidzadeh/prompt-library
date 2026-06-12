@props(['hasMore' => false])
@if ($hasMore)
    <div
        class="loadmore"
        x-data
        x-init="
            new IntersectionObserver(
                (entries) => { if (entries[0].isIntersecting) $wire.loadMore(); },
                { rootMargin: '200px 0px' }
            ).observe($el);
        "
    >
        <div wire:loading.flex wire:target="loadMore" class="loadmore__hint">Loading…</div>
        <button type="button" class="pl-btn pl-btn--secondary" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
            Load more
        </button>
        <div class="loadmore__sentinel" aria-hidden="true"></div>
    </div>
@endif
