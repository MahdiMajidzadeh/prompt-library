@props([
    'prompt',
    'rank' => null,
])
@php
    $detailUrl = route('prompts.show', $prompt);
    $copyText = e($prompt->body);
@endphp

<article
    {{ $attributes->class(['pl-card', 'pl-card--ranked' => $rank !== null]) }}
    tabindex="0"
    role="link"
    aria-label="{{ $prompt->title }}"
    x-data
    @click="if (!$event.target.closest('.pl-copy-icon, .pl-tag')) window.location='{{ $detailUrl }}'"
    @keydown.enter.prevent="window.location='{{ $detailUrl }}'"
    @keydown.space.prevent="window.location='{{ $detailUrl }}'"
    style="cursor: pointer;"
>
    <div class="pl-card__head">
        <h3 class="pl-card__title">
            @if ($rank !== null)
                <span class="pl-card__rank">{{ str_pad((string) $rank, 2, '0', STR_PAD_LEFT) }}</span>&nbsp;
            @endif
            {{ $prompt->title }}
        </h3>
        <button
            type="button"
            class="pl-copy-icon pl-card__copy"
            aria-label="Copy prompt"
            x-data="{ copied: false }"
            @click.stop="
                navigator.clipboard?.writeText(@js($prompt->body)).catch(()=>{});
                copied = true;
                setTimeout(() => copied = false, 1600);
            "
            :class="{ 'is-copied': copied }"
        >
            <template x-if="!copied">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
            </template>
            <template x-if="copied">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </template>
        </button>
    </div>
    <pre class="pl-card__preview">{{ $prompt->body }}</pre>
    <div class="pl-card__foot">
        <div class="pl-card__tags">
            @foreach ($prompt->tags as $tag)
                <a class="pl-tag" href="{{ route('tags.show', $tag) }}" @click.stop>{{ $tag->name }}</a>
            @endforeach
        </div>
        <span class="pl-meta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            {{ \Illuminate\Support\Number::abbreviate($prompt->view_count) }}
        </span>
    </div>
</article>
