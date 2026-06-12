@props(['title' => 'Prompt Library'])
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<header class="pl-header">
    <div class="pl-header__inner">
        <a class="pl-wordmark" href="{{ url('/') }}">
            <span class="pl-wordmark__mark pl-wordmark__mark--accent">P</span> Prompt Library
        </a>
        <div class="pl-header__search">
            <form class="pl-search" action="{{ url('/search') }}" method="GET" role="search">
                <svg class="pl-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input class="pl-search__input" type="search" name="q" value="{{ request('q') }}" placeholder="Search prompts…" aria-label="Search prompts">
            </form>
        </div>
        <nav class="pl-header__nav">
            <a class="pl-navlink {{ request()->is('/') ? 'pl-navlink--active' : '' }}" href="{{ url('/') }}">Browse</a>
            <a class="pl-navlink {{ request()->is('prompts/latest') ? 'pl-navlink--active' : '' }}" href="{{ url('/prompts/latest') }}">Latest</a>
            <a class="pl-navlink {{ request()->is('prompts/most-viewed') ? 'pl-navlink--active' : '' }}" href="{{ url('/prompts/most-viewed') }}">Most viewed</a>
            <button type="button" class="pl-iconbtn" id="themeToggle" aria-label="Toggle dark mode"></button>
        </nav>
    </div>
</header>

<main class="mx-auto" style="max-width: var(--container-content); padding: 0 var(--spacing-5);">
    {{ $slot }}
</main>

<footer class="pl-footer">
    <div class="pl-footer__inner">
        <a class="pl-wordmark" style="font-size: var(--text-base);" href="{{ url('/') }}">
            <span class="pl-wordmark__mark">P</span> Prompt Library
        </a>
        <span class="pl-footer__copy">
            Developed with AI by
            <a class="pl-footer__link" href="https://mahdi.majidzadeh.ir/" target="_blank" rel="noopener noreferrer">Mahdi Majidzadeh</a>
        </span>
    </div>
</footer>

<script>
(function () {
    const SUN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
    const MOON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>';
    const btn = document.getElementById('themeToggle');
    function apply(t) {
        document.documentElement.setAttribute('data-theme', t);
        localStorage.setItem('pl-theme', t);
        btn.innerHTML = t === 'dark' ? MOON : SUN;
    }
    btn.addEventListener('click', function () {
        apply(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
    apply(localStorage.getItem('pl-theme') || 'light');
})();
</script>

</body>
</html>
