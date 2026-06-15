<div class="page">
    <section class="context">
        <div class="context__main" style="flex: 1;">
            <p class="context__eyebrow">Tags</p>
            <h1 class="context__heading">Browse by tag</h1>
            <p class="context__count">{{ $tags->count() }} {{ $tags->count() === 1 ? 'tag' : 'tags' }}</p>
        </div>
    </section>

    @if ($tags->isEmpty())
        <div class="pl-empty">
            <div class="pl-empty__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.59 3H4a1 1 0 0 0-1 1v5.59A2 2 0 0 0 3.59 11l9.58 9.59a2 2 0 0 0 2.83 0l4.59-4.59a2 2 0 0 0 0-2.83Z"/>
                    <circle cx="7.5" cy="7.5" r="1.5"/>
                </svg>
            </div>
            <h2 class="pl-empty__title">No tags yet</h2>
            <p class="pl-empty__text">Tags appear here once they are attached to a public prompt.</p>
        </div>
    @else
        <div class="tag-grid">
            @foreach ($tags as $tag)
                <a class="tag-tile" href="{{ route('tags.show', $tag) }}">
                    <span class="tag-tile__name">{{ $tag->name }}</span>
                    <span class="tag-tile__count">{{ $tag->prompts_count }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
