<?php

declare(strict_types=1);

it('bounds the default splash logo in the published Vue stub', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/Splash.vue',
    );

    expect($stub)
        ->toContain('form-flow-default-splash-logo')
        ->toContain('class="form-flow-default-splash-logo mb-4 w-auto object-contain')
        ->toContain('width: auto;')
        ->toContain('height: auto;')
        ->toContain('max-width: min(14rem, 70vw);')
        ->toContain('max-height: 4.5rem;')
        ->toContain('object-fit: contain;');
});

it('renders the default splash Pay Code as a full-width dynamic display', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/Splash.vue',
    );

    expect($stub)
        ->toContain('const voucherCodeSizeClass = computed')
        ->toContain('data-testid="form-flow-splash-pay-code"')
        ->toContain('class="flex w-full items-center gap-3 sm:gap-4"')
        ->toContain('h-0.5 min-w-5 flex-1 rounded-full bg-primary/75')
        ->toContain('font-mono font-black uppercase leading-none')
        ->not->toContain('aria-hidden="true">||</span>');
});
