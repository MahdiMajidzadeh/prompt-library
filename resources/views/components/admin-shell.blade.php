@props(['active' => null])
<div class="page">
    <section class="context" style="padding-bottom: var(--spacing-5); align-items: center;">
        <div class="context__main">
            <p class="context__eyebrow">Admin</p>
            <h1 class="context__heading">{{ $heading ?? 'Admin' }}</h1>
            @isset($subheading)
                <p class="context__count">{{ $subheading }}</p>
            @endisset
        </div>

        <nav class="seg" role="tablist" aria-label="Admin sections">
            <a class="seg__btn" aria-pressed="{{ $active === 'dashboard' ? 'true' : 'false' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a class="seg__btn" aria-pressed="{{ $active === 'prompts' ? 'true' : 'false' }}" href="{{ route('admin.prompts.index') }}">Prompts</a>
            <a class="seg__btn" aria-pressed="{{ $active === 'tags' ? 'true' : 'false' }}" href="{{ route('admin.tags.index') }}">Tags</a>
            <form method="POST" action="{{ route('logout') }}" style="display: contents;">
                @csrf
                <button type="submit" class="seg__btn">Sign out</button>
            </form>
        </nav>
    </section>

    @if (session('status'))
        <div class="pl-card" style="cursor: default; padding: var(--spacing-3) var(--spacing-4); margin-bottom: var(--spacing-5); border-color: var(--color-accent);">
            <span style="font-size: var(--text-sm); color: var(--color-accent);">{{ session('status') }}</span>
        </div>
    @endif

    {{ $slot }}
</div>
