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
    config()->set('form-flow.presentation.profiles.example', ['brand' => ['name' => 'Before'], 'appearance' => ['light' => ['canvas' => '#fafafa']], 'icons' => ['size' => 40]]);
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
    config()->set('form-flow.presentation.profiles.example.appearance.light.canvas', '#eeeeee');
    config()->set('form-flow.presentation.profiles.example.icons.size', 32);
    $stored = app(FormFlowService::class)->getFlowState($live['flow_id']);
    expect($stored['instructions']['steps'][0]['config']['presentation']['brand']['name'])->toBe('Before')
        ->and($stored['instructions']['steps'][0]['config']['presentation']['appearance']['light']['canvas'])->toBe('#fafafa')
        ->and($stored['instructions']['steps'][0]['config']['presentation']['icons']['size'])->toBe(40);
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

it('inherits appearance and icon roles while retaining state-specific defaults', function () {
    config()->set('form-flow.presentation.profiles.custom', [
        'appearance' => ['mode' => 'system', 'light' => ['canvas' => '#fafafa']],
        'icons' => ['size' => 40, 'review' => ['name' => 'clipboard-check', 'color' => '#123456']],
    ]);
    $result = app(FormFlowPresentationResolver::class)->resolve(['profile' => 'custom']);
    expect($result['appearance']['mode'])->toBe('system')
        ->and($result['appearance']['light']['canvas'])->toBe('#fafafa')
        ->and($result['icons']['size'])->toBe(40)
        ->and($result['icons']['review']['name'])->toBe('clipboard-check')
        ->and($result['icons']['pending']['name'])->toBe('clock')
        ->and($result['icons']['success']['color'])->toBe('#16a34a');
});

it('rejects unsafe or unsupported appearance and icon settings', function (array $presentation) {
    app(FormFlowPresentationResolver::class)->resolve($presentation);
})->with([
    [['appearance' => ['mode' => 'auto']]],
    [['appearance' => ['light' => ['canvas' => 'url(https://example.test)']]]],
    [['appearance' => ['light' => ['unknown' => '#ffffff']]]],
    [['icons' => ['size' => 100]]],
    [['icons' => ['review' => ['name' => 'custom-html']]]],
    [['icons' => ['success' => ['color' => 'red']]]],
])->throws(ValidationException::class);

it('inherits a bounded artwork aspect ratio without forcing it on generic flows', function () {
    $resolver = app(FormFlowPresentationResolver::class);
    expect($resolver->resolve([])['layout']['artwork_aspect_ratio'])->toBe('natural');
    config()->set('form-flow.presentation.profiles.artwork', ['layout' => ['artwork_aspect_ratio' => '1200/630']]);
    expect($resolver->resolve(['profile' => 'artwork'])['layout']['artwork_aspect_ratio'])->toBe('1200/630');
});

it('rejects an unsupported artwork aspect ratio', function () {
    app(FormFlowPresentationResolver::class)->resolve(['layout' => ['artwork_aspect_ratio' => '1/0']]);
})->throws(ValidationException::class);

it('inherits artwork settings and freezes them when the flow starts', function () {
    config()->set('form-flow.presentation.profiles.artwork', ['artwork' => ['background' => ['enabled' => true, 'opacity' => 0.55]]]);
    $instructions = FormFlowInstructionsData::from([
        'reference_id' => 'artwork-snapshot', 'presentation' => ['profile' => 'artwork'],
        'steps' => [['handler' => 'splash', 'config' => ['content' => '<p>Welcome</p>']]],
    ]);
    $flows = app(FormFlowService::class);
    $live = $flows->startFlow($instructions);
    config()->set('form-flow.presentation.profiles.artwork.artwork.background.opacity', 0.1);
    expect(data_get($flows->getFlowState($live['flow_id']), 'instructions.steps.0.config.presentation.artwork.background.opacity'))->toBe(0.55);
    expect(app(FormFlowPresentationResolver::class)->resolve(['profile' => 'artwork'], ['artwork' => ['foreground' => ['position' => 'center']]])['artwork']['foreground']['position'])->toBe('center');
});

it('rejects unsupported artwork presentation settings', function (array $artwork) {
    app(FormFlowPresentationResolver::class)->resolve(['artwork' => $artwork]);
})->with([
    [['background' => ['opacity' => 2]]],
    [['background' => ['blur' => 100]]],
    [['background' => ['overlay' => -1]]],
    [['background' => ['script' => 'unsafe']]],
    [['foreground' => ['position' => 'outside']]],
    [['foreground' => ['padding' => 0]]],
    [['foreground' => ['top_spacing' => 1000]]],
])->throws(ValidationException::class);

it('resolves registered screen overrides before explicit step settings', function () {
    $resolver = app(FormFlowPresentationResolver::class);
    $resolver->registerScreens(['capture']);
    config()->set('form-flow.presentation.profiles.tokens', [
        'tokens' => ['spacing' => 'comfortable'],
        'screens' => ['capture' => ['tokens' => ['spacing' => 'spacious'], 'actions' => ['roles' => ['capture' => ['label' => 'Take photo']]]]],
    ]);
    $result = $resolver->resolve(['profile' => 'tokens', 'tokens' => ['spacing' => 'compact']], ['tokens' => ['content_width' => 'wide']], 'capture');
    expect($result['tokens'])->toBe(['spacing' => 'spacious', 'content_width' => 'wide'])
        ->and($result['actions']['roles']['capture']['label'])->toBe('Take photo');
    expect($resolver->resolve(['profile' => 'tokens'], ['tokens' => ['spacing' => 'compact']], 'capture')['tokens']['spacing'])->toBe('compact');
});

it('rejects unbounded tokens and screen or action behavior overrides', function (array $value) {
    app(FormFlowPresentationResolver::class)->resolve($value);
})->with([
    [['tokens' => ['content_width' => '10000px']]],
    [['tokens' => ['spacing' => 'custom']]],
    [['tokens' => ['typography' => 'html']]],
    [['tokens' => ['control_size' => 'tiny']]],
    [['screens' => ['unknown' => []]]],
    [['screens' => ['form' => ['screens' => []]]]],
    [['screens' => ['form' => ['profile' => 'other']]]],
    [['actions' => ['roles' => ['capture' => ['href' => '/transfer']]]]],
    [['actions' => ['roles' => ['capture' => ['label' => '<b>Take photo</b>']]]]],
    [['notices' => ['error' => ['background' => 'red']]]],
])->throws(ValidationException::class);

it('freezes tokens screen overrides and action copy for live and preview', function () {
    $resolver = app(FormFlowPresentationResolver::class);
    config()->set('form-flow.presentation.profiles.frozen', ['screens' => ['form' => ['tokens' => ['control_size' => 'large'], 'actions' => ['roles' => ['continue' => ['label' => 'Next details']]]]]]);
    $instructions = FormFlowInstructionsData::from(['reference_id' => 'frozen-tokens', 'presentation' => ['profile' => 'frozen'], 'steps' => [['handler' => 'form', 'config' => []]]]);
    $flows = app(FormFlowService::class);
    $live = $flows->startFlow($instructions);
    $preview = app(FormFlowPreviewCompiler::class)->compile($instructions);
    expect($preview[0]->props['presentation'])->toBe($live['instructions']['steps'][0]['config']['presentation']);
    config()->set('form-flow.presentation.profiles.frozen.screens.form.tokens.control_size', 'standard');
    expect(data_get($flows->getFlowState($live['flow_id']), 'instructions.steps.0.config.presentation.tokens.control_size'))->toBe('large');
});

it('suppresses diagnostics inside frozen screen overrides in production', function () {
    $this->app['env'] = 'production';
    $result = app(FormFlowPresentationResolver::class)->resolve(['screens' => ['form' => ['diagnostics' => ['show_versions' => true]]]]);
    expect($result['screens']['form']['diagnostics']['show_versions'])->toBeFalse();
});
