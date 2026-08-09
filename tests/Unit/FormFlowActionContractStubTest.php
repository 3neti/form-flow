<?php

declare(strict_types=1);

it('exposes an explicit action placement contract in the shared action component', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/components/FormFlowActions.vue',
    );

    expect($stub)
        ->toContain('type FormFlowActionPlacement = "inline" | "bottom_sticky";')
        ->toContain('actionPlacement?: FormFlowActionPlacement | string | null;')
        ->toContain('props.actionPlacement === "bottom_sticky" || props.actionPlacement === "inline"')
        ->toContain('normalizedActionPlacement.value === "bottom_sticky"')
        ->toContain('sticky bottom-0');
});

it('uses shared form flow actions from the generic form stub', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/GenericForm.vue',
    );

    expect($stub)
        ->toContain('import FormFlowActions from "./components/FormFlowActions.vue";')
        ->toContain('action_placement?: "inline" | "bottom_sticky" | string | null;')
        ->toContain('<FormFlowActions')
        ->toContain(':action-placement="props.action_placement"')
        ->not->toContain('const actionContainerClass = computed')
        ->not->toContain('const secondaryButtonClass = computed')
        ->not->toContain('const primaryButtonClass = computed');
});
