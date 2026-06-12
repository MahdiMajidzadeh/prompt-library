<div class="wrap">
    <article class="read">
        <a class="crumb" href="{{ url('/prompts/latest') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M11 6l-6 6 6 6"/>
            </svg>
            All prompts
        </a>

        <h1 class="detail-title">{{ $prompt->title }}</h1>

        <div class="detail-tags">
            @foreach ($prompt->tags as $tag)
                <a class="pl-tag" href="{{ route('tags.show', $tag) }}">{{ $tag->name }}</a>
            @endforeach
        </div>

        <div class="detail-meta">
            <span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                {{ \Illuminate\Support\Number::format($prompt->view_count) }} {{ $prompt->view_count === 1 ? 'view' : 'views' }}
            </span>
            <span class="detail-dot"></span>
            <span>Added {{ $prompt->created_at->format('M j, Y') }}</span>
        </div>

        <section
            class="prompt-block"
            x-data="{
                copied: false,
                flash: false,
                async copy() {
                    const text = this.$refs.body.textContent;
                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(text);
                        } else {
                            const ta = document.createElement('textarea');
                            ta.value = text;
                            ta.style.position = 'fixed';
                            ta.style.opacity = '0';
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                        }
                        this.copied = true;
                        this.flash = false;
                        this.$nextTick(() => this.flash = true);
                        setTimeout(() => this.copied = false, 1800);
                        setTimeout(() => this.flash = false, 700);
                    } catch (e) {
                        console.error('Copy failed', e);
                    }
                }
            }"
            :class="{ 'is-flash': flash }"
        >
            <div class="prompt-block__bar">
                <span class="prompt-block__label">
                    <span class="prompt-block__dots"><i></i><i></i><i></i></span>
                    prompt
                </span>
                <button type="button" class="pl-btn pl-btn--primary copy-cta" :class="{ 'is-copied': copied }" @click="copy()">
                    <svg class="pl-btn__icon" x-show="!copied" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="9" y="9" width="13" height="13" rx="2"/>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                    <svg class="pl-btn__icon" x-show="copied" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                    <span x-text="copied ? 'Copied' : 'Copy prompt'">Copy prompt</span>
                </button>
            </div>
            <pre class="prompt-block__body" x-ref="body">{{ $prompt->body }}</pre>
        </section>
    </article>

    @if ($related->isNotEmpty())
        <div class="read"><div class="rule"></div></div>

        <section class="related">
            <div class="pl-section-head">
                <div>
                    <h2 class="pl-section-head__title">More like this</h2>
                    <p class="pl-section-head__sub">Other prompts that share its tags</p>
                </div>
                <a class="pl-viewall" href="{{ url('/prompts/latest') }}">View all
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>
            </div>
            <div class="grid-3">
                @foreach ($related as $r)
                    <x-prompt-card :prompt="$r" wire:key="related-{{ $r->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
