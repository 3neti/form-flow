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
        ->toContain('const voucherCodeDisplayStyle = computed')
        ->toContain('fontSize = "5.25rem"')
        ->toContain('fontSize = "4.5rem"')
        ->toContain('letterSpacing: "0.08em"')
        ->toContain('data-testid="form-flow-splash-pay-code"')
        ->toContain('class="flex w-full items-center gap-3 sm:gap-5"')
        ->toContain('class="flex min-w-6 flex-1 flex-col gap-2"')
        ->toContain('form-flow-splash-pay-code-rule')
        ->toContain('background: currentColor;')
        ->toContain('color: hsl(var(--foreground));')
        ->toContain('class="min-w-0 max-w-full break-all font-mono font-black uppercase leading-none text-primary"')
        ->not->toContain('aria-hidden="true">||</span>');
});
