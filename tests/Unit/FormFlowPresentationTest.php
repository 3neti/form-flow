<?php

use Illuminate\Validation\ValidationException;
use LBHurtado\FormFlowManager\Data\FormFlowInstructionsData;
use LBHurtado\FormFlowManager\Services\FormFlowPresentationResolver;
use LBHurtado\FormFlowManager\Services\FormFlowPreviewCompiler;
use LBHurtado\FormFlowManager\Services\FormFlowService;

it('inherits profiles and driver settings before explicit step settings', function () {
    config()->set('form-flow.presentation.profiles.example-v1', ['brand' => ['name' => 'Example', 'logo_height' => 24], 'layout' => ['density' => 'immersive']]);
    $result = app(FormFlowPresentationResolver::class)->resolve(
        ['profile' => 'example-v1', 'brand' => ['logo_height' => 32]],
        ['layout' => ['density' => 'compact']],
    );
    expect($result['brand']['name'])->toBe('Example')
        ->and($result['brand']['logo_height'])->toBe(32)
        ->and($result['layout']['density'])->toBe('compact')
        ->and($result['actions']['placement'])->toBe('viewport_bottom');
});

it('rejects unsupported presentation instead of silently changing the flow', function (array $presentation) {
    app(FormFlowPresentationResolver::class)->resolve($presentation);
})->with([
    'unknown profile' => [['profile' => 'unknown']],
    'unsupported version' => [['schema' => 'form-flow.presentation.v99']],
    'oversized logo' => [['brand' => ['logo_height' => 200]]],
    'unknown setting' => [['arbitrary_html' => '<script>unsafe</script>']],
    'hidden overflow' => [['layout' => ['scrolling' => 'never']]],
    'covered content' => [['actions' => ['reserve_content_space' => false]]],
])->throws(ValidationException::class);

it('does not allow profiles to inherit another profile', function () {
    config()->set('form-flow.presentation.profiles.child', ['profile' => 'parent']);
    app(FormFlowPresentationResolver::class)->resolve(['profile' => 'child']);
})->throws(ValidationException::class);

it('gates diagnostic versions on the server even when YAML requests them', function () {
    $this->app['env'] = 'production';
    $result = app(FormFlowPresentationResolver::class)->resolve(['diagnostics' => ['show_versions' => true]]);
    expect($result['diagnostics']['show_versions'])->toBeFalse();
});

it('freezes live presentation and produces the same contract for preview', function () {
    config()->set('form-flow.presentation.profiles.example', ['brand' => ['name' => 'Before']]);
    $instructions = FormFlowInstructionsData::from([
        'reference_id' => 'presentation-lifecycle',
        'presentation' => ['profile' => 'example'],
        'steps' => [
            ['handler' => 'form', 'config' => ['fields' => [['name' => 'name', 'type' => 'text']]]],
            ['handler' => 'splash', 'config' => ['content' => 'Done']],
        ],
    ]);
    $live = app(FormFlowService::class)->startFlow($instructions);
    $preview = app(FormFlowPreviewCompiler::class)->compile($instructions);
    expect($preview[0]->props['presentation'])->toBe($live['instructions']['steps'][0]['config']['presentation'])
        ->and($preview[1]->props['presentation']['progress'])->toBe(['show' => true, 'current' => 2, 'total' => 2]);
    config()->set('form-flow.presentation.profiles.example.brand.name', 'After');
    $stored = app(FormFlowService::class)->getFlowState($live['flow_id']);
    expect($stored['instructions']['steps'][0]['config']['presentation']['brand']['name'])->toBe('Before');
    app(FormFlowService::class)->updateStepData($live['flow_id'], 0, ['name' => 'Synthetic']);
    app(FormFlowService::class)->updateStepData($live['flow_id'], 1, []);
    expect(app(FormFlowService::class)->completeFlow($live['flow_id'])['status'])->toBe('completed');
});

it('leaves drivers without presentation unchanged', function () {
    $instructions = FormFlowInstructionsData::from(['reference_id' => 'legacy', 'steps' => [['handler' => 'form', 'config' => []]]]);
    expect(app(FormFlowPresentationResolver::class)->prepare($instructions))->toBe($instructions);
});

it('removes package versions from the frozen production state and every preview screen', function () {
    $this->app['env'] = 'production';
    $instructions = FormFlowInstructionsData::from([
        'reference_id' => 'production-presentation',
        'presentation' => ['diagnostics' => ['show_versions' => true]],
        'metadata' => ['show_package_versions' => true, 'package_versions' => [['name' => 'internal', 'version' => 'private']]],
        'steps' => [['handler' => 'form', 'config' => ['package_versions' => [['name' => 'internal', 'version' => 'private']]]]],
    ]);
    $prepared = app(FormFlowPresentationResolver::class)->prepare($instructions);
    $preview = app(FormFlowPreviewCompiler::class)->compile($instructions);
    expect($prepared->metadata['package_versions'])->toBe([])
        ->and($prepared->metadata['show_package_versions'])->toBeFalse()
        ->and($preview[0]->props['package_versions'])->toBe([])
        ->and($preview[0]->props['show_package_versions'])->toBeFalse();
});
