<?php

declare(strict_types=1);

it('bounds the default splash logo in the published Vue stub', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/Splash.vue',
    );

    expect($stub)
        ->toContain('form-flow-default-splash-logo')
        ->toContain('max-width: 5rem;')
        ->toContain('max-height: 5rem;')
        ->toContain('object-fit: contain;');
});
