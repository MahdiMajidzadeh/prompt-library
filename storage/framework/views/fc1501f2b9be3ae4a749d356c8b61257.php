<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title' => 'Prompt Library']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['title' => 'Prompt Library']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>

<header class="pl-header">
    <div class="pl-header__inner">
        <a class="pl-wordmark" href="<?php echo e(url('/')); ?>">
            <span class="pl-wordmark__mark pl-wordmark__mark--accent">P</span> Prompt Library
        </a>
        <div class="pl-header__search">
            <form class="pl-search" action="<?php echo e(url('/search')); ?>" method="GET" role="search">
                <svg class="pl-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input class="pl-search__input" type="search" name="q" value="<?php echo e(request('q')); ?>" placeholder="Search prompts…" aria-label="Search prompts">
            </form>
        </div>
        <nav class="pl-header__nav">
            <a class="pl-navlink <?php echo e(request()->is('/') ? 'pl-navlink--active' : ''); ?>" href="<?php echo e(url('/')); ?>">Browse</a>
            <a class="pl-navlink <?php echo e(request()->is('prompts/latest') ? 'pl-navlink--active' : ''); ?>" href="<?php echo e(url('/prompts/latest')); ?>">Latest</a>
            <a class="pl-navlink <?php echo e(request()->is('prompts/most-viewed') ? 'pl-navlink--active' : ''); ?>" href="<?php echo e(url('/prompts/most-viewed')); ?>">Most viewed</a>
            <button type="button" class="pl-iconbtn" id="themeToggle" aria-label="Toggle dark mode"></button>
        </nav>
    </div>
</header>

<main class="mx-auto" style="max-width: var(--container-content); padding: 0 var(--spacing-5);">
    <?php echo e($slot); ?>

</main>

<footer class="pl-footer">
    <div class="pl-footer__inner">
        <a class="pl-wordmark" style="font-size: var(--text-base);" href="<?php echo e(url('/')); ?>">
            <span class="pl-wordmark__mark">P</span> Prompt Library
        </a>
        <span class="pl-footer__copy">
            Developed with AI by
            <a class="pl-footer__link" href="https://mahdi.majidzadeh.ir/" target="_blank" rel="noopener noreferrer">Mahdi Majidzadeh</a>
        </span>
    </div>
</footer>

<nav class="m-tabbar" aria-label="Primary">
    <a class="m-tab <?php echo e(request()->routeIs('home') ? 'm-tab--active' : ''); ?>" href="<?php echo e(route('home')); ?>" aria-label="Home">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>
        Home
    </a>
    <a class="m-tab <?php echo e(request()->routeIs('search') ? 'm-tab--active' : ''); ?>" href="<?php echo e(route('search')); ?>" aria-label="Search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        Search
    </a>
    <a class="m-tab" href="<?php echo e(route('home')); ?>#tags" aria-label="Tags">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.59 3H4a1 1 0 0 0-1 1v5.59A2 2 0 0 0 3.59 11l9.58 9.59a2 2 0 0 0 2.83 0l4.59-4.59a2 2 0 0 0 0-2.83Z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>
        Tags
    </a>
</nav>

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
<?php /**PATH /Users/mahdi/Documents/app/prompt-library/resources/views/components/layouts/app.blade.php ENDPATH**/ ?>