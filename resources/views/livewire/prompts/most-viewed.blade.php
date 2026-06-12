<div class="page">
    <section class="context">
        <div class="context__main">
            <p class="context__eyebrow">All prompts</p>
            <h1 class="context__heading">Most viewed</h1>
            <p class="context__count">{{ $total }} {{ $total === 1 ? 'prompt' : 'prompts' }}</p>
        </div>
    </section>

    @if ($prompts->isEmpty())
        <div class="pl-empty">
            <div class="pl-empty__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </div>
            <h2 class="pl-empty__title">No prompts yet</h2>
            <p class="pl-empty__text">Check back soon, or browse the latest.</p>
        </div>
    @else
        <div class="grid-2">
            @foreach ($prompts as $index => $prompt)
                <x-prompt-card :prompt="$prompt" :rank="$index + 1" wire:key="prompt-{{ $prompt->id }}" />
            @endforeach
        </div>

        <x-load-more-sentinel :has-more="$hasMore" />
    @endif
</div>
