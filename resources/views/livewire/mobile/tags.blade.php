<div class="m-page">
    <section class="m-intro">
        <h1 class="m-intro__title">Tags</h1>
        <p class="m-intro__desc">Browse prompts by topic.</p>
        <div class="m-intro__meta">
            <span>{{ $tags->count() }} {{ $tags->count() === 1 ? 'tag' : 'tags' }}</span>
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
        <div class="m-taggrid">
            @foreach ($tags as $tag)
                <a class="m-tagtile" href="{{ route('tags.show', $tag) }}">
                    <span class="m-tagtile__name">{{ $tag->name }}</span>
                    <span class="m-tagtile__count">{{ $tag->prompts_count }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
