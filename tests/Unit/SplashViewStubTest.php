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
        ->toContain('class="@container flex w-full items-center justify-center"')
        ->toContain('whitespace-nowrap')
        ->toContain('{{ `|| ${voucher_code} ||` }}')
        ->not->toContain('break-all');
});

it('lets claim presentation drive splash copy and in-card action placement', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/Splash.vue',
    );

    expect($stub)
        ->toContain('interface ClaimPresentation')
        ->toContain('claim_presentation?: ClaimPresentation | null;')
        ->toContain('const pageTitle = computed(() => claimPresentationTitle.value ?? props.title ?? "Welcome");')
        ->toContain('const primaryActionLabel = computed(() => {')
        ->toContain('const voucherCodeEyebrow = computed(() => {')
        ->toContain('const splashActionPlacement = computed(() => props.action_placement || "bottom");')
        ->toContain('<Head :title="pageTitle" />')
        ->toContain('{{ voucherCodeEyebrow }}')
        ->toContain(':primary-label="primaryActionLabel"')
        ->not->toContain('Redeeming');
});
