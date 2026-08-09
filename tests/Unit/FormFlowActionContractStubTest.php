<?php

declare(strict_types=1);

it('exposes an explicit action placement contract in the shared action component', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/components/FormFlowActions.vue',
    );

    expect($stub)
        ->toContain('type FormFlowActionPlacement = "inline" | "bottom" | "bottom_sticky";')
        ->toContain('actionPlacement?: FormFlowActionPlacement | string | null;')
        ->toContain('props.actionPlacement === "bottom_sticky"')
        ->toContain('props.actionPlacement === "bottom"')
        ->toContain('props.actionPlacement === "inline"')
        ->toContain('normalizedActionPlacement.value === "bottom_sticky"')
        ->toContain('normalizedActionPlacement.value === "bottom"')
        ->toContain('sticky bottom-0')
        ->toContain('mt-auto grid gap-3 border-t pt-4');
});

it('uses shared form flow actions from the generic form stub', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/GenericForm.vue',
    );

    expect($stub)
        ->toContain('import FormFlowActions from "./components/FormFlowActions.vue";')
        ->toContain('action_placement?: "inline" | "bottom" | "bottom_sticky" | string | null;')
        ->toContain('<FormFlowActions')
        ->toContain(':action-placement="props.action_placement"')
        ->toContain('data-testid="form-flow-package-version-strip"')
        ->not->toContain('const actionContainerClass = computed')
        ->not->toContain('const secondaryButtonClass = computed')
        ->not->toContain('const primaryButtonClass = computed');
});

it('exposes the package version strip in the shared form flow screen stub', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/components/FormFlowScreen.vue',
    );

    expect($stub)
        ->toContain('packageVersions?: PackageVersion[] | Record<string, string> | null;')
        ->toContain('showPackageVersions?: boolean;')
        ->toContain('data-testid="form-flow-package-version-strip"')
        ->toContain('props.showPackageVersions && normalizedPackageVersions.length > 0');
});
