<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use LBHurtado\FormFlowManager\Services\FormFlowService;

function claimExperienceFixture(): array
{
    return [
        'version' => 1,
        'entry' => [
            'mode' => 'rider_first',
            'initial_phase' => 'rider_intro',
        ],
        'phases' => [],
        'consumed' => [
            'splash' => true,
        ],
        'diagnostics' => [
            'duplicate_splash_prevented' => true,
            'redirect_owner' => 'claim-widget',
            'consumed' => [
                'splash' => true,
            ],
            'warnings' => [],
        ],
    ];
}

beforeEach(function () {
    $viewsPath = __DIR__.'/../Fixtures/views';
    $pagesPath = __DIR__.'/../Fixtures/js/pages';

    if (! is_dir($viewsPath)) {
        mkdir($viewsPath, 0777, true);
    }

    if (! is_dir($pagesPath.'/form-flow/core')) {
        mkdir($pagesPath.'/form-flow/core', 0777, true);
    }

    file_put_contents($viewsPath.'/app.blade.php', <<<'BLADE'
<div id="app" data-page="{{ json_encode($page) }}"></div>
BLADE);

    file_put_contents($pagesPath.'/form-flow/core/GenericForm.vue', '<template></template>');
    file_put_contents($pagesPath.'/form-flow/core/Complete.vue', '<template></template>');
    file_put_contents($pagesPath.'/form-flow/core/Splash.vue', '<template></template>');

    app('view')->addLocation($viewsPath);

    config()->set('inertia.testing.page_paths', [
        $pagesPath,
    ]);
});

it('passes claim experience to the active step page', function () {
    $referenceId = 'claim-experience-active-'.uniqid();

    $createResponse = $this->postJson('/form-flow/start', [
        'reference_id' => $referenceId,
        'steps' => [
            [
                'handler' => 'form',
                'config' => [
                    'title' => 'Wallet Information',
                    'fields' => [
                        [
                            'name' => 'mobile',
                            'type' => 'text',
                            'label' => 'Mobile Number',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ],
        'callbacks' => [
            'on_complete' => 'https://example.com/callback',
        ],
        'metadata' => [
            'claim_experience' => claimExperienceFixture(),
        ],
    ]);

    $createResponse->assertSuccessful();

    $this->get($createResponse->json('flow_url'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('form-flow/core/GenericForm')
            ->where('claim_experience.version', 1)
            ->where('claim_experience.entry.mode', 'rider_first')
            ->where('claim_experience.entry.initial_phase', 'rider_intro')
            ->where('claim_experience.consumed.splash', true)
            ->where('claim_experience.diagnostics.duplicate_splash_prevented', true)
        );
});

it('passes claim experience to the complete page', function () {
    Http::fake();

    $referenceId = 'claim-experience-complete-'.uniqid();

    $createResponse = $this->postJson('/form-flow/start', [
        'reference_id' => $referenceId,
        'steps' => [
            [
                'handler' => 'form',
                'config' => [
                    'title' => 'Wallet Information',
                    'fields' => [
                        [
                            'name' => 'mobile',
                            'type' => 'text',
                            'label' => 'Mobile Number',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ],
        'callbacks' => [
            'on_complete' => 'https://example.com/callback',
        ],
        'metadata' => [
            'claim_experience' => claimExperienceFixture(),
        ],
    ]);

    $createResponse->assertSuccessful();

    $service = app(FormFlowService::class);
    $state = $service->getFlowStateByReference($referenceId);
    $flowId = $state['flow_id'];

    $service->updateStepData($flowId, 0, [
        'mobile' => '639171234567',
    ]);

    $this->get("/form-flow/{$flowId}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('form-flow/core/Complete')
            ->where('claim_experience.version', 1)
            ->where('claim_experience.entry.mode', 'rider_first')
            ->where('claim_experience.entry.initial_phase', 'rider_intro')
            ->where('claim_experience.consumed.splash', true)
            ->where('claim_experience.diagnostics.duplicate_splash_prevented', true)
        );
});

it('passes claim experience to the splash step page', function () {
    $referenceId = 'claim-experience-splash-'.uniqid();

    $createResponse = $this->postJson('/form-flow/start', [
        'reference_id' => $referenceId,
        'steps' => [
            [
                'handler' => 'splash',
                'config' => [
                    'title' => 'Welcome',
                    'content' => '<h1>Welcome</h1>',
                    'timeout' => 0,
                ],
            ],
        ],
        'callbacks' => [
            'on_complete' => 'https://example.com/callback',
        ],
        'metadata' => [
            'claim_experience' => claimExperienceFixture(),
        ],
    ]);

    $createResponse->assertSuccessful();

    $this->get($createResponse->json('flow_url'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('form-flow/core/Splash')
            ->where('claim_experience.version', 1)
            ->where('claim_experience.entry.mode', 'rider_first')
            ->where('claim_experience.entry.initial_phase', 'rider_intro')
            ->where('claim_experience.consumed.splash', true)
            ->where('claim_experience.diagnostics.duplicate_splash_prevented', true)
        );
});

it('marks splash step as duplicate candidate when claim splash was already consumed', function () {
    $referenceId = 'claim-experience-duplicate-splash-'.uniqid();

    $createResponse = $this->postJson('/form-flow/start', [
        'reference_id' => $referenceId,
        'steps' => [
            [
                'handler' => 'splash',
                'config' => [
                    'title' => 'Welcome',
                    'content' => '<h1>Welcome</h1>',
                    'timeout' => 0,
                ],
            ],
        ],
        'callbacks' => [
            'on_complete' => 'https://example.com/callback',
        ],
        'metadata' => [
            'claim_experience' => [
                'version' => 1,
                'entry' => [
                    'mode' => 'rider_first',
                    'initial_phase' => 'rider_intro',
                ],
                'phases' => [],
                'consumed' => [
                    'splash' => true,
                ],
                'diagnostics' => [
                    'duplicate_splash_prevented' => true,
                ],
            ],
        ],
    ]);

    $createResponse->assertSuccessful();

    $this->get($createResponse->json('flow_url'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('form-flow/core/Splash')
            ->where('claim_experience.version', 1)
            ->where('claim_experience.consumed.splash', true)
            ->where('claim_experience_warnings.0', 'duplicate_splash_candidate')
        );
});

it('does not skip duplicate splash candidate by default', function () {
    config()->set('form-flow.claim_experience.skip_consumed_splash', false);

    $referenceId = 'claim-experience-no-skip-splash-'.uniqid();

    $createResponse = $this->postJson('/form-flow/start', [
        'reference_id' => $referenceId,
        'steps' => [
            [
                'handler' => 'splash',
                'config' => [
                    'title' => 'Welcome',
                    'content' => '<h1>Welcome</h1>',
                    'timeout' => 0,
                ],
            ],
            [
                'handler' => 'form',
                'config' => [
                    'title' => 'Wallet Information',
                    'fields' => [
                        [
                            'name' => 'mobile',
                            'type' => 'text',
                            'label' => 'Mobile Number',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ],
        'callbacks' => [
            'on_complete' => 'https://example.com/callback',
        ],
        'metadata' => [
            'claim_experience' => claimExperienceFixture(),
        ],
    ]);

    $createResponse->assertSuccessful();

    $this->get($createResponse->json('flow_url'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('form-flow/core/Splash')
            ->where('claim_experience_warnings.0', 'duplicate_splash_candidate')
        );
});

it('skips consumed splash when skip consumed splash is enabled', function () {
    config()->set('form-flow.claim_experience.skip_consumed_splash', true);

    $referenceId = 'claim-experience-skip-splash-'.uniqid();

    $createResponse = $this->postJson('/form-flow/start', [
        'reference_id' => $referenceId,
        'steps' => [
            [
                'handler' => 'splash',
                'config' => [
                    'title' => 'Welcome',
                    'content' => '<h1>Welcome</h1>',
                    'timeout' => 0,
                    'step_name' => 'intro_splash',
                ],
            ],
            [
                'handler' => 'form',
                'config' => [
                    'title' => 'Wallet Information',
                    'fields' => [
                        [
                            'name' => 'mobile',
                            'type' => 'text',
                            'label' => 'Mobile Number',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ],
        'callbacks' => [
            'on_complete' => 'https://example.com/callback',
        ],
        'metadata' => [
            'claim_experience' => claimExperienceFixture(),
        ],
    ]);

    $createResponse->assertSuccessful();

    $response = $this->get($createResponse->json('flow_url'));

    $response->assertRedirect();

    $this->followingRedirects()
        ->get($createResponse->json('flow_url'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('form-flow/core/GenericForm')
            ->where('claim_experience.version', 1)
            ->where('claim_experience.consumed.splash', true)
        );
});

it('skips consumed splash when claim experience opts in per flow', function () {
    config()->set('form-flow.claim_experience.skip_consumed_splash', false);

    $referenceId = 'claim-experience-per-flow-skip-splash-'.uniqid();

    $experience = claimExperienceFixture();
    $experience['options'] = [
        'skip_consumed_splash' => true,
    ];

    $createResponse = $this->postJson('/form-flow/start', [
        'reference_id' => $referenceId,
        'steps' => [
            [
                'handler' => 'splash',
                'config' => [
                    'title' => 'Welcome',
                    'content' => '<h1>Welcome</h1>',
                    'timeout' => 0,
                    'step_name' => 'intro_splash',
                ],
            ],
            [
                'handler' => 'form',
                'config' => [
                    'title' => 'Wallet Information',
                    'fields' => [
                        [
                            'name' => 'mobile',
                            'type' => 'text',
                            'label' => 'Mobile Number',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ],
        'callbacks' => [
            'on_complete' => 'https://example.com/callback',
        ],
        'metadata' => [
            'claim_experience' => $experience,
        ],
    ]);

    $createResponse->assertSuccessful();

    $this->get($createResponse->json('flow_url'))
        ->assertRedirect();

    $this->followingRedirects()
        ->get($createResponse->json('flow_url'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('form-flow/core/GenericForm')
            ->where('claim_experience.version', 1)
            ->where('claim_experience.options.skip_consumed_splash', true)
            ->where('claim_experience.consumed.splash', true)
        );
});