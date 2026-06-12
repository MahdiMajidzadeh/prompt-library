<div class="page">
    <section class="context">
        <div class="context__main">
            <p class="context__eyebrow">Tag</p>
            <h1 class="context__heading">
                <span class="context__tagpill">
                    {{ $tag->name }}
                    <a class="context__clear" href="{{ url('/prompts/latest') }}" aria-label="Clear tag filter">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </a>
                </span>
            </h1>
            <p class="context__count">{{ $total }} {{ $total === 1 ? 'prompt' : 'prompts' }}</p>
        </div>
    </section>

    @if ($prompts->isEmpty())
        <div class="pl-empty">
            <div class="pl-empty__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20.59 13.41 11.17 22a2 2 0 0 1-2.83 0L2 15.83a2 2 0 0 1 0-2.83L11.41 4H20a2 2 0 0 1 2 2v8.59a2 2 0 0 1-.59 1.83Z"/>
                    <circle cx="7" cy="7" r="1"/>
                </svg>
            </div>
            <h2 class="pl-empty__title">Nothing here yet</h2>
            <p class="pl-empty__text">No public prompts carry this tag.</p>
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
