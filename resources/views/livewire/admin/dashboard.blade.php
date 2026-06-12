<div>
    <x-admin-shell active="dashboard">
        <x-slot:heading>Dashboard</x-slot:heading>
        <x-slot:subheading>{{ now()->format('l, F j, Y') }}</x-slot:subheading>

        <div class="grid-3" style="--cols: 4;">
            <div class="pl-card" style="cursor: default;">
                <div class="pl-card__head">
                    <h3 class="pl-card__title">Public prompts</h3>
                </div>
                <div style="font-size: var(--text-3xl); font-weight: 600; letter-spacing: var(--tracking-tight); font-family: var(--font-mono); color: var(--color-text);">
                    {{ $publicCount }}
                </div>
                <span class="pl-meta">visible on the public side</span>
            </div>

            <div class="pl-card" style="cursor: default;">
                <div class="pl-card__head">
                    <h3 class="pl-card__title">Private prompts</h3>
                </div>
                <div style="font-size: var(--text-3xl); font-weight: 600; letter-spacing: var(--tracking-tight); font-family: var(--font-mono); color: var(--color-text);">
                    {{ $privateCount }}
                </div>
                <span class="pl-meta">drafts / hidden</span>
            </div>

            <div class="pl-card" style="cursor: default;">
                <div class="pl-card__head">
                    <h3 class="pl-card__title">Tags</h3>
                </div>
                <div style="font-size: var(--text-3xl); font-weight: 600; letter-spacing: var(--tracking-tight); font-family: var(--font-mono); color: var(--color-text);">
                    {{ $tagCount }}
                </div>
                <span class="pl-meta">in use across the library</span>
            </div>

            <div class="pl-card" style="cursor: default;">
                <div class="pl-card__head">
                    <h3 class="pl-card__title">Total views</h3>
                </div>
                <div style="font-size: var(--text-3xl); font-weight: 600; letter-spacing: var(--tracking-tight); font-family: var(--font-mono); color: var(--color-text);">
                    {{ \Illuminate\Support\Number::format($totalViews) }}
                </div>
                <span class="pl-meta">raw rows · aggregated {{ \Illuminate\Support\Number::format($totalViewCount) }}</span>
            </div>
        </div>
    </x-admin-shell>
</div>
