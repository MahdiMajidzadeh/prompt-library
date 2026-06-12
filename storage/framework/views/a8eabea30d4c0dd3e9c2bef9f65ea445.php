<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => ['title' => 'Prompt Library']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Prompt Library']); ?>
    <section style="padding: var(--spacing-9) 0 var(--spacing-8); max-width: var(--container-reading);">
        <h1 style="font-size: var(--text-4xl); font-weight: 600; letter-spacing: var(--tracking-tight); margin: 0 0 var(--spacing-4); line-height: 1.04;">
            Prompt Library
        </h1>
        <p style="font-size: var(--text-lg); color: var(--color-text-secondary); margin: 0; line-height: var(--leading-snug);">
            Browse, search, and copy curated prompts.
        </p>
    </section>

    <section style="padding-bottom: var(--spacing-9);">
        <p class="pl-tag pl-tag--static">Layout shell renders</p>
    </section>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $attributes = $__attributesOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__attributesOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $component = $__componentOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__componentOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php /**PATH /Users/mahdi/Documents/app/prompt-library/resources/views/welcome.blade.php ENDPATH**/ ?>