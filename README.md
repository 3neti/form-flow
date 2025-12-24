# Form Flow

[![Latest Version](https://img.shields.io/packagist/v/3neti/form-flow.svg?style=flat-square)](https://packagist.org/packages/3neti/form-flow)
[![Tests](https://img.shields.io/github/actions/workflow/status/3neti/form-flow/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/3neti/form-flow/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/3neti/form-flow.svg?style=flat-square)](https://packagist.org/packages/3neti/form-flow)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Driver-based form flow orchestration system with DirXML-style mapping for Laravel applications.

## Features

- **Driver-Based Architecture**: Define multi-step form flows using YAML configuration files
- **DirXML-Style Mapping**: Transform and map data between steps using flexible mapping rules
- **Handler Plugin System**: Extend functionality with custom form handlers
- **Built-in Handlers**: FormHandler, SplashHandler, and MissingHandler included
- **Vue Components**: Pre-built Inertia.js Vue components for rapid development
- **Configurable**: Customize route prefixes, middleware, and behavior
- **Self-Contained**: Automatic CSRF handling, route registration, and asset publishing

## Requirements

- PHP ^8.2
- Laravel ^11.0 || ^12.0
- Inertia.js ^2.0

## Installation

Install via Composer:

```bash
composer require 3neti/form-flow
```

### Publish Assets

Publish the configuration file:

```bash
php artisan vendor:publish --tag=form-flow-config
```

Publish driver examples:

```bash
php artisan vendor:publish --tag=form-flow-drivers
```

Publish Vue components:

```bash
php artisan vendor:publish --tag=form-flow-views
```

Or publish everything at once:

```bash
php artisan vendor:publish --tag=form-flow-core
```

## Configuration

The package configuration is located at `config/form-flow.php`:

```php
return [
    'route_prefix' => env('FORM_FLOW_ROUTE_PREFIX', 'form-flow'),
    'middleware' => ['web'],
    'driver_directory' => config_path('form-flow-drivers'),
    'session_prefix' => 'form_flow',
    'handlers' => [
        // Plugin handlers register themselves via service providers
    ],
];
```

## Usage

### Creating a Form Flow

Define a flow driver in `config/form-flow-drivers/my-flow.yaml`:

```yaml
name: my-flow
title: My Form Flow
steps:
  - handler: splash
    config:
      title: Welcome
      message: Let's get started
      
  - handler: form
    config:
      title: User Information
      fields:
        - name: full_name
          type: text
          label: Full Name
          validation: required|string|max:255
```

### Starting a Flow

```php
use Illuminate\Support\Facades\Http;

$response = Http::post('/form-flow/start', [
    'reference_id' => 'unique-transaction-id',
    'steps' => [
        ['handler' => 'splash', 'config' => [...]],
        ['handler' => 'form', 'config' => [...]],
    ],
    'callbacks' => [
        'on_complete' => 'https://your-app.com/api/flow-complete',
        'on_cancel' => 'https://your-app.com/api/flow-cancelled',
    ],
]);

$flowUrl = $response->json('flow_url');
```

### Available Routes

The package automatically registers these routes:

- `POST /form-flow/start` - Start a new flow
- `GET /form-flow/{flow_id}` - Show current step
- `POST /form-flow/{flow_id}/step/{step}` - Update step data
- `POST /form-flow/{flow_id}/complete` - Complete flow
- `POST /form-flow/{flow_id}/cancel` - Cancel flow
- `DELETE /form-flow/{flow_id}` - Destroy flow state

## Plugin Development

Create custom handlers by implementing `FormHandlerInterface`:

```php
use LBHurtado\FormFlowManager\Contracts\FormHandlerInterface;

class MyCustomHandler implements FormHandlerInterface
{
    public function getName(): string
    {
        return 'my-custom';
    }
    
    public function handle(Request $request, FormFlowStepData $step, array $context = []): array
    {
        // Process step data
        return ['processed' => true];
    }
    
    public function render(FormFlowStepData $step, array $context = [])
    {
        return inertia('MyCustomView', [...]);
    }
    
    // ... other methods
}
```

Register in your service provider:

```php
public function boot(): void
{
    $handlers = config('form-flow.handlers', []);
    $handlers['my-custom'] = MyCustomHandler::class;
    config(['form-flow.handlers' => $handlers]);
}
```

## Vue Components

The package includes ready-to-use Vue components:

- `GenericForm.vue` - Generic form renderer
- `Splash.vue` - Splash screen handler
- `Complete.vue` - Flow completion page
- `MissingHandler.vue` - Fallback for missing handlers

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Lester Hurtado](https://github.com/lbhurtado)
- [3neti](https://github.com/3neti)
