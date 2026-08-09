<?php

declare(strict_types=1);

it('exposes an explicit action placement contract in the shared action component', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/components/FormFlowActions.vue',
    );

    expect($stub)
        ->toContain('| "viewport_bottom";')
        ->toContain('actionPlacement?: FormFlowActionPlacement | string | null;')
        ->toContain('props.actionPlacement === "viewport_bottom"')
        ->toContain('props.actionPlacement === "bottom_sticky"')
        ->toContain('props.actionPlacement === "bottom"')
        ->toContain('props.actionPlacement === "inline"')
        ->toContain('normalizedActionPlacement.value === "viewport_bottom"')
        ->toContain('normalizedActionPlacement.value === "bottom_sticky"')
        ->toContain('normalizedActionPlacement.value === "bottom"')
        ->toContain('viewportBottomStyle')
        ->toContain('bottom: "max(0.2in, calc(env(safe-area-inset-bottom) + 1rem))"')
        ->toContain(':style="viewportBottomStyle"')
        ->toContain('sticky bottom-0')
        ->toContain('mt-auto grid gap-3 border-t pt-4');
});

it('uses shared form flow actions from the generic form stub', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/GenericForm.vue',
    );

    expect($stub)
        ->toContain('import FormFlowActions from "./components/FormFlowActions.vue";')
        ->toContain('import FormFlowVersionStrip from "./components/FormFlowVersionStrip.vue";')
        ->toContain('| "viewport_bottom"')
        ->toContain('<FormFlowActions')
        ->toContain(':action-placement="props.action_placement"')
        ->toContain('<FormFlowVersionStrip')
        ->toContain(':context="props.package_version_context"')
        ->not->toContain('const actionContainerClass = computed')
        ->not->toContain('const secondaryButtonClass = computed')
        ->not->toContain('const primaryButtonClass = computed');
});

it('exposes the package version strip in the shared form flow screen stub', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/components/FormFlowScreen.vue',
    );

    expect($stub)
        ->toContain('import FormFlowVersionStrip from "./FormFlowVersionStrip.vue";')
        ->toContain('packageVersions?: PackageVersion[] | Record<string, string> | null;')
        ->toContain('showPackageVersions?: boolean;')
        ->toContain('versionContext?: string | null;')
        ->toContain('<FormFlowVersionStrip')
        ->toContain(':show="props.showPackageVersions"')
        ->toContain(':package-versions="props.packageVersions"')
        ->toContain(':context="props.versionContext"');
});

it('exposes the package version strip in the completion screen stub', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/Complete.vue',
    );

    expect($stub)
        ->toContain('import FormFlowVersionStrip from "./components/FormFlowVersionStrip.vue";')
        ->toContain('package_versions?:')
        ->toContain('show_package_versions?: boolean;')
        ->toContain('ReceiptText')
        ->toContain('<FormFlowVersionStrip')
        ->toContain(':show="props.show_package_versions"')
        ->toContain(':package-versions="props.package_versions"');
});

it('renders package versions as a compact QA chip strip', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/components/FormFlowVersionStrip.vue',
    );

    expect($stub)
        ->toContain('data-testid="form-flow-package-version-strip"')
        ->toContain('QA build')
        ->toContain('shortPackageName')
        ->toContain('packageNamesForContext')
        ->toContain('visiblePackageVersions')
        ->toContain('3neti/form-handler-selfie');
});
