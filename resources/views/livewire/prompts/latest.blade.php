<div class="page">
    <section class="context">
        <div class="context__main">
            <p class="context__eyebrow">All prompts</p>
            <h1 class="context__heading">Latest</h1>
            <p class="context__count">{{ $total }} {{ $total === 1 ? 'prompt' : 'prompts' }}</p>
        </div>
    </section>

    @if ($prompts->isEmpty())
        <div class="pl-empty">
            <div class="pl-empty__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
            </div>
            <h2 class="pl-empty__title">No prompts yet</h2>
            <p class="pl-empty__text">Check back soon, or browse the most viewed.</p>
        </div>
    @else
        <div class="grid-3">
            @foreach ($prompts as $prompt)
                <x-prompt-card :prompt="$prompt" wire:key="prompt-{{ $prompt->id }}" />
            @endforeach
        </div>

        <x-load-more-sentinel :has-more="$hasMore" />
    @endif
</div>
