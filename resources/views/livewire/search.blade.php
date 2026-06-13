<div class="page">
    <section class="context">
        <div class="context__main" style="flex: 1;">
            <p class="context__eyebrow">Search</p>
            <h1 class="context__heading">
                @if ($isEmptyQuery)
                    Find a prompt
                @else
                    Results for <span class="context__q">“{{ $q }}”</span>
                @endif
            </h1>
            @if (! $isEmptyQuery)
                <p class="context__count">{{ $total }} {{ $total === 1 ? 'match' : 'matches' }}</p>
            @endif
        </div>
    </section>

    @if ($isEmptyQuery)
        <div class="pl-empty">
            <div class="pl-empty__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
            </div>
            <h2 class="pl-empty__title">Use the search bar above</h2>
            <p class="pl-empty__text">Matches prompt titles and tag names. Body text is not searched.</p>
        </div>
    @elseif ($prompts->isEmpty())
        <div class="pl-empty">
            <div class="pl-empty__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 21l-6-6"/>
                    <circle cx="10" cy="10" r="7"/>
                    <path d="M7 10h6M10 7v6" opacity=".4"/>
                </svg>
            </div>
            <h2 class="pl-empty__title">No matches</h2>
            <p class="pl-empty__text">Try a different word or check the spelling.</p>
        </div>
    @else
        <div class="grid-2">
            @foreach ($prompts as $prompt)
                <x-prompt-card :prompt="$prompt" wire:key="prompt-{{ $prompt->id }}" />
            @endforeach
        </div>

        <x-load-more-sentinel :has-more="$hasMore" />
    @endif
</div>
