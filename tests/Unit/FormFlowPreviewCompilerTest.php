<?php

use LBHurtado\FormFlowManager\Contracts\FormHandlerPreviewInterface;
use LBHurtado\FormFlowManager\Data\FormFlowInstructionsData;
use LBHurtado\FormFlowManager\Handlers\FormHandler;
use LBHurtado\FormFlowManager\Services\FormFlowPreviewCompiler;

test('preview compiler preserves the exact ordered form fields and marks screens inert', function () {
    $instructions = FormFlowInstructionsData::from([
        'reference_id' => 'preview-contract',
        'steps' => [
            [
                'handler' => 'form',
                'config' => [
                    'title' => 'Recipient details',
                    'fields' => [
                        ['name' => 'name', 'type' => 'text', 'required' => true],
                        ['name' => 'email', 'type' => 'email', 'required' => false],
                    ],
                ],
            ],
            [
                'handler' => 'splash',
                'config' => ['content' => '<strong>Welcome</strong>', 'timeout' => 3],
            ],
        ],
        'callbacks' => ['on_complete' => 'https://example.test/complete'],
    ]);

    $screens = app(FormFlowPreviewCompiler::class)->compile($instructions);

    expect($screens)->toHaveCount(2)
        ->and($screens[0]->handler)->toBe('form')
        ->and($screens[0]->component)->toBe('form-flow/core/GenericForm')
        ->and($screens[0]->props['fields'])->toBe($instructions->steps[0]->config['fields'])
        ->and($screens[0]->props['preview_mode'])->toBeTrue()
        ->and($screens[1]->handler)->toBe('splash')
        ->and($screens[1]->component)->toBe('form-flow/core/Splash')
        ->and($screens[1]->props['content'])->toBe('<strong>Welcome</strong>')
        ->and($screens[1]->props['preview_mode'])->toBeTrue();
});

test('built in form handler exposes the preview contract', function () {
    expect(app(FormHandler::class))->toBeInstanceOf(FormHandlerPreviewInterface::class);
});
