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
        ->toContain('max-height: 6rem;')
        ->toContain('object-fit: contain;');
});
