<?php

namespace LBHurtado\FormFlowManager\Tests;

use Inertia\ServiceProvider;
use LBHurtado\FormFlowManager\FormFlowServiceProvider;
use LBHurtado\FormHandlerLocation\LocationHandler;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\LaravelData\Normalizers\ArrayableNormalizer;
use Spatie\LaravelData\Normalizers\ArrayNormalizer;
use Spatie\LaravelData\Normalizers\JsonNormalizer;
use Spatie\LaravelData\Normalizers\ModelNormalizer;
use Spatie\LaravelData\Normalizers\ObjectNormalizer;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelDataServiceProvider::class,
            ServiceProvider::class,
            FormFlowServiceProvider::class,
            //            \LBHurtado\FormHandlerLocation\LocationHandlerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);

        // Laravel Data configuration
        $app['config']->set('data.validation_strategy', 'only_requests');
        $app['config']->set('data.max_transformation_depth', 6);
        $app['config']->set('data.throw_when_max_transformation_depth_reached', 6);
        $app['config']->set('data.normalizers', [
            ModelNormalizer::class,
            ArrayableNormalizer::class,
            ObjectNormalizer::class,
            ArrayNormalizer::class,
            JsonNormalizer::class,
        ]);
        $app['config']->set('data.date_format', "Y-m-d\TH:i:sP");

        // Location handler configuration
        $app['config']->set('location-handler.opencage_api_key', 'test_key');
        $app['config']->set('location-handler.map_provider', 'google');
        $app['config']->set('location-handler.capture_snapshot', true);
        $app['config']->set('location-handler.require_address', false);

        // Register location handler
        $app['config']->set('form-flow.handlers.location', LocationHandler::class);
    }
}
