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
        ->toContain('<FormFlowVersionStrip')
        ->toContain(':show="props.show_package_versions"')
        ->toContain(':package-versions="props.package_versions"');
});

it('tightens the redemption confirmation screen into a semantic payout summary', function (): void {
    $stub = file_get_contents(
        dirname(__DIR__, 2).'/stubs/resources/js/pages/form-flow/core/Complete.vue',
    );

    // 1. The confirmation component exposes a semantic redemption summary.
    expect($stub)
        ->toContain('<dl')
        ->toContain('data-testid="redemption-summary"')
        ->toContain('redemptionSummaryFields')
        ->toContain('v-if="redemptionSummaryFields.length > 0"');

    // 2. The five payout fields have the required canonical order.
    $mobilePos = strpos($stub, 'label: "Mobile Number"');
    $bankPos = strpos($stub, 'label: "Bank/Wallet Provider"');
    $accountPos = strpos($stub, 'label: "Account Number"');
    $amountPos = strpos($stub, 'label: "Amount"');
    $railPos = strpos($stub, 'label: "Payment Method"');

    expect($mobilePos)->not->toBeFalse()
        ->and($bankPos)->not->toBeFalse()
        ->and($accountPos)->not->toBeFalse()
        ->and($amountPos)->not->toBeFalse()
        ->and($railPos)->not->toBeFalse();
    expect($mobilePos)->toBeLessThan($bankPos)
        ->and($bankPos)->toBeLessThan($accountPos)
        ->and($accountPos)->toBeLessThan($amountPos)
        ->and($amountPos)->toBeLessThan($railPos);

    // 3. The old payout-route sentence, route pills, and arrows are absent.
    expect($stub)
        ->not->toContain('payoutRouteSegments')
        ->not->toContain('payoutRouteIcons')
        ->not->toContain('payoutRouteSentence')
        ->not->toContain('payoutRouteSegmentsList')
        ->not->toContain('payoutRouteIconsList')
        ->not->toContain('payoutRouteSummary')
        ->not->toContain('Confirm destination')
        ->not->toContain('ReceiptText')
        ->not->toContain('selectedDestination')
        ->not->toContain('heroData');

    // 4. Institution and rail icons render after their authoritative text
    // values (value span appears before the icon component in source).
    $valueSpanPos = strpos($stub, '{{ field.value }}</span>');
    $iconPos = strpos($stub, '<PayoutDestinationIcon');

    expect($valueSpanPos)->not->toBeFalse()
        ->and($iconPos)->not->toBeFalse()
        ->and($valueSpanPos)->toBeLessThan($iconPos);

    // 5. Icon failure cannot remove the textual value: the value span is
    // unconditional (not gated behind the same v-if as the icon).
    expect($stub)->toContain('{{ field.value }}</span>');

    // Decorative icons must not duplicate screen-reader announcements.
    expect($stub)
        ->toContain('alt=""')
        ->toContain('aria-hidden="true"');

    // Icons are never added to Mobile Number, Account Number, or Amount --
    // only the Bank/Wallet Provider and Payment Method field pushes may
    // declare an `icon:` property.
    $extractFieldPushBlock = function (string $stub, string $key): string {
        $start = strpos($stub, "key: \"{$key}\",");
        expect($start)->not->toBeFalse("Could not locate field push for {$key}");

        $end = strpos($stub, '});', $start);

        return substr($stub, $start, $end - $start);
    };

    expect($extractFieldPushBlock($stub, 'mobile'))->not->toContain('icon:');
    expect($extractFieldPushBlock($stub, 'account_number'))->not->toContain('icon:');
    expect($extractFieldPushBlock($stub, 'amount'))->not->toContain('icon:');
    expect($extractFieldPushBlock($stub, 'bank_code'))->toContain('icon:');
    expect($extractFieldPushBlock($stub, 'settlement_rail'))->toContain('icon:');

    // 6. Supplemental evidence sections (Personal Information, Location
    // Verification, Identity Verification) remain available.
    expect($stub)
        ->toContain('supplementalSections')
        ->toContain('groupDataBySection')
        ->toContain('v-for="section in supplementalSections"');

    // 7. Specialized workflows are not forced to display absent payout
    // fields -- every field push is guarded by presence, not truthiness.
    expect($stub)
        ->toContain('function hasCollectedValue')
        ->toContain('hasCollectedValue(data, "mobile")')
        ->toContain('hasCollectedValue(data, "account_number")')
        ->toContain('hasCollectedValue(data, "amount")')
        ->toContain('hasCollectedValue(data, "settlement_rail")');

    // 8. The shared FormFlowActions behavior and package-version strip
    // remain intact.
    expect($stub)
        ->toContain('<FormFlowActions')
        ->toContain(':primary-label="confirmationLabel"')
        ->toContain('@primary="handleClose"')
        ->toContain('<FormFlowVersionStrip');

    // 9. The fixed action component (and its invisible viewport spacer)
    // renders outside the compact Card/CardContent, so the spacer never
    // eats visible space inside the card body. The reference ID, however,
    // stays inside the card's content, immediately after the summary and
    // any supplemental evidence.
    $cardContentClosePos = strpos($stub, '</CardContent>');
    $cardClosePos = strpos($stub, '</Card>');
    $formFlowActionsPos = strpos($stub, '<FormFlowActions');
    $referenceIdPos = strpos($stub, '{{ state.reference_id }}');
    $summaryPos = strpos($stub, 'data-testid="redemption-summary"');
    $supplementalPos = strpos($stub, 'v-for="section in supplementalSections"');

    expect($cardContentClosePos)->not->toBeFalse()
        ->and($cardClosePos)->not->toBeFalse()
        ->and($formFlowActionsPos)->not->toBeFalse()
        ->and($referenceIdPos)->not->toBeFalse();

    // FormFlowActions is a sibling of Card, not a child of CardContent.
    expect($cardContentClosePos)->toBeLessThan($cardClosePos)
        ->and($cardClosePos)->toBeLessThan($formFlowActionsPos);

    // The reference ID stays inside CardContent, after the summary and
    // supplemental sections.
    expect($summaryPos)->toBeLessThan($supplementalPos)
        ->and($supplementalPos)->toBeLessThan($referenceIdPos)
        ->and($referenceIdPos)->toBeLessThan($cardContentClosePos);

    // 10. Every summary row uses a bounded two-column grid (neither column
    // can force the other outside the card) with safe long-value wrapping.
    $summaryBlockStart = strpos($stub, '<dl');
    $summaryBlockEnd = strpos($stub, '</dl>', $summaryBlockStart);
    $summaryBlock = substr($stub, $summaryBlockStart, $summaryBlockEnd - $summaryBlockStart);

    expect($summaryBlock)
        ->toContain('grid grid-cols-2')
        ->toContain('min-w-0')
        ->toContain('break-words')
        ->toContain('justify-end')
        ->not->toContain('truncate');
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
