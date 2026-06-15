<div class="page">
    <section class="intro">
        <h1 class="intro__title">Prompt Library</h1>
        <p class="intro__desc">Browse, search, and copy curated prompts.</p>
        <div class="intro__meta">
            <span>{{ $totalPublic }} {{ $totalPublic === 1 ? 'prompt' : 'prompts' }}</span>
            <span class="intro__dot"></span>
            <span>{{ $tags->count() }} {{ $tags->count() === 1 ? 'tag' : 'tags' }}</span>
            <span class="intro__dot"></span>
            <span>updated regularly</span>
        </div>
    </section>

    @if ($latest->isNotEmpty())
        <section class="section">
            <div class="pl-section-head">
                <div>
                    <h2 class="pl-section-head__title">Recently added</h2>
                    <p class="pl-section-head__sub">Fresh prompts from the library</p>
                </div>
                <a class="pl-viewall" href="{{ url('/prompts/latest') }}">View all
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>
            </div>
            <div class="grid-2 grid-2--rail">
                @foreach ($latest as $prompt)
                    <x-prompt-card :prompt="$prompt" wire:key="latest-{{ $prompt->id }}" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($mostViewed->isNotEmpty())
        <section class="section">
            <div class="pl-section-head">
                <div>
                    <h2 class="pl-section-head__title">Most viewed</h2>
                    <p class="pl-section-head__sub">The prompts people copy the most</p>
                </div>
                <a class="pl-viewall" href="{{ url('/prompts/most-viewed') }}">View all
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>
            </div>
            <div class="grid-2 grid-2--rail">
                @foreach ($mostViewed as $index => $prompt)
                    <x-prompt-card :prompt="$prompt" :rank="$index + 1" wire:key="popular-{{ $prompt->id }}" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($tags->isNotEmpty())
        <section class="section" id="tags">
            <div class="pl-section-head">
                <h2 class="pl-section-head__title">Browse by tag</h2>
            </div>
            <div class="tag-grid">
                @foreach ($tags as $tag)
                    <a class="tag-tile" href="{{ route('tags.show', $tag) }}">
                        <span class="tag-tile__name">{{ $tag->name }}</span>
                        <span class="tag-tile__count">{{ $tag->prompts_count }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
