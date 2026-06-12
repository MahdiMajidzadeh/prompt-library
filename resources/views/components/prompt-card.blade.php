@props([
    'prompt',
    'rank' => null,
])
@php
    $detailUrl = route('prompts.show', $prompt);
    $visibleTags = $prompt->tags->take(2);
    $hiddenTagCount = max($prompt->tags->count() - 2, 0);
@endphp

<article
    {{ $attributes->class(['pl-card', 'pl-card--ranked' => $rank !== null]) }}
    tabindex="0"
    role="link"
    aria-label="{{ $prompt->title }}"
    x-data
    @click="if (!$event.target.closest('.pl-tag')) window.location='{{ $detailUrl }}'"
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
    </div>
    <pre class="pl-card__preview">{{ $prompt->body }}</pre>
    <div class="pl-card__foot">
        <div class="pl-card__tags">
            @foreach ($visibleTags as $tag)
                <a class="pl-tag pl-tag--sm" href="{{ route('tags.show', $tag) }}" @click.stop>{{ $tag->name }}</a>
            @endforeach
            @if ($hiddenTagCount > 0)
                <span class="pl-tag pl-tag--sm pl-tag--static">+{{ $hiddenTagCount }}</span>
            @endif
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
