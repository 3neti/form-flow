# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

Form Flow Manager is a Laravel package that provides a driver-based form flow orchestration system with DirXML-style mapping. It enables multi-step form flows through YAML configuration files and a plugin architecture for extensible form handlers.

**Package Name:** `3neti/form-flow` (`LBHurtado\FormFlowManager`)
**Type:** Laravel Library/Package (not a standalone application)
**Requirements:** PHP ^8.2, Laravel ^11.0 || ^12.0, Inertia.js ^2.0

## Commands

### Testing
```bash
composer test              # Run all tests via Pest
vendor/bin/pest           # Run tests directly
vendor/bin/pest --filter="test name"  # Run specific test
```

### Publishing Assets
```bash
php artisan vendor:publish --tag=form-flow-config    # Publish config file
php artisan vendor:publish --tag=form-flow-drivers   # Publish driver examples
php artisan vendor:publish --tag=form-flow-views     # Publish Vue components
php artisan vendor:publish --tag=form-flow-core      # Publish everything
```

### Development
```bash
composer install          # Install dependencies (uses Orchestra Testbench for testing)
```

## Architecture

### Core Components

**1. FormFlowService** (`src/Services/FormFlowService.php`)
- Manages flow state using Laravel session storage
- Session keys: `form_flow.{flow_id}.*`
- Handles flow lifecycle: start, update, complete, cancel

**2. FormFlowController** (`src/Http/Controllers/FormFlowController.php`)
- Routes registered at `/form-flow/*`
- Key endpoints:
  - `POST /form-flow/start` - Create new flow (returns `flow_url`)
  - `GET /form-flow/{flow_id}` - Show current step
  - `POST /form-flow/{flow_id}/step/{step}` - Update step data
  - `POST /form-flow/{flow_id}/complete` - Complete flow
- Follows HyperVerge pattern: accepts `reference_id`, returns `flow_url` for separate session access

**3. Handler System** (Plugin Architecture)
- Core interface: `FormHandlerInterface` (`src/Contracts/FormHandlerInterface.php`)
- Built-in handlers in `src/Handlers/`:
  - `FormHandler` - Basic form inputs (text, email, date, number, select, checkbox, textarea, file)
  - `SplashHandler` - Welcome/splash screens
  - `MissingHandler` - Fallback for undefined handlers
- Plugin handlers auto-register via service providers (see PLUGIN_ARCHITECTURE.md)
- Handlers registry in `config/form-flow.php`

**4. Mapping & Template System**
- **MappingEngine** (`src/Services/MappingEngine.php`) - Transforms domain objects to form instructions using YAML drivers
- **TemplateRenderer** (`src/Services/TemplateRenderer.php`) - Variable interpolation with `{{ variable }}` syntax
- **ExpressionEvaluator** (`src/Services/ExpressionEvaluator.php`) - Conditional logic evaluation
- **DriverService** (`src/Services/DriverService.php`) - Loads YAML driver configs and orchestrates transformation

**5. Driver System**
- YAML configuration files in `config/form-flow-drivers/`
- Defines how domain objects (e.g., Voucher) map to form flows
- Supports conditional steps, template variables, and transformations
- Uses DirXML-style declarative mapping

### Data Transfer Objects (DTOs)

Using `spatie/laravel-data`:
- `FormFlowInstructionsData` - Complete flow definition
- `FormFlowStepData` - Individual step configuration
- `DriverConfigData` - YAML driver configuration

### Frontend Integration

**Inertia.js + Vue 3 Components** (`stubs/resources/js/pages/form-flow/core/`)
- `GenericForm.vue` - Generic form renderer
- `Splash.vue` - Splash screen component
- `Complete.vue` - Flow completion page
- `MissingHandler.vue` - Fallback UI for missing handlers

Published to host app via `php artisan vendor:publish --tag=form-flow-views`

## Plugin Development

To create a new handler plugin (e.g., `form-handler-location`):

1. **Create separate package** with structure:
   ```
   form-handler-{name}/
   ├── src/{Name}Handler.php          # Implements FormHandlerInterface
   ├── src/{Name}HandlerServiceProvider.php
   └── resources/js/{Name}CapturePage.vue
   ```

2. **Implement FormHandlerInterface** with methods:
   - `getName()` - Returns handler identifier (e.g., 'location')
   - `handle(Request, FormFlowStepData, array)` - Process step submission
   - `validate(array, array)` - Validate collected data
   - `render(FormFlowStepData, array)` - Return Inertia response
   - `getConfigSchema()` - Define configuration schema

3. **Service provider must call** `registerHandler()` in `boot()`:
   ```php
   protected function registerHandler(): void
   {
       $handlers = config('form-flow.handlers', []);
       $handlers['location'] = LocationHandler::class;
       config(['form-flow.handlers' => $handlers]);
   }
   ```

4. **Auto-discovery** via `composer.json`:
   ```json
   "extra": {
       "laravel": {
           "providers": ["YourNamespace\\YourHandlerServiceProvider"]
       }
   }
   ```

**Core should NOT depend on plugins.** Plugins in `require-dev` for testing only. Host apps install plugins as needed.

See PLUGIN_ARCHITECTURE.md for complete guide.

## Testing Patterns

**Framework:** Pest (not PHPUnit)
**Test Suites:**
- `tests/Unit/` - Service/component tests
- `tests/Feature/` - Integration tests (controller, mapping engine)

**Key Test Patterns:**
- Use `test()->postJson()` for HTTP testing (Orchestra Testbench provides Laravel app context)
- Validate flow lifecycle: start → update steps → complete → callback
- Test handler auto-registration for plugins
- Test mapping transformations with various driver configs

**Example Test Structure:**
```php
test('form-flow start endpoint exists', function () {
    $response = test()->postJson('/form-flow/start', [...]);
    expect($response->status())->not->toBe(404);
});
```

## Key Patterns & Conventions

### Session Isolation
- Each flow uses isolated session keys: `form_flow.{flow_id}.*`
- Reference ID mapping: `form_flow_ref.{reference_id} => flow_id`

### Async Handler Support
- Some handlers (e.g., KYC) complete in callbacks outside user session
- Use `Cache::put("kyc_completed.{$flowId}", ...)` pattern
- Controller checks cache on next `show()` request and applies cached data

### Validation
- Request validation in controller
- Handler-specific validation in handler's `validate()` method
- Config schema validation via `getConfigSchema()`

### Error Handling
- Missing handlers fallback to `MissingHandler` instead of crashing
- Logs warnings: `Log::warning('[FormFlow] Missing handler at runtime', [...])`

### Naming Conventions
- Handlers: lowercase with hyphens (e.g., 'form', 'location', 'selfie')
- Step names: optional `step_name` in config for named references
- Classes: PSR-4, namespace `LBHurtado\FormFlowManager\`

## Configuration

**Config file:** `config/form-flow.php`
- `route_prefix` - URL prefix for routes (default: 'form-flow')
- `middleware` - Applied to all routes (default: ['web'])
- `driver_directory` - Path to YAML drivers (default: `config_path('form-flow-drivers')`)
- `session_prefix` - Session key prefix (default: 'form_flow')
- `handlers` - Registered handler classes (plugins auto-register here)

## Important Notes

- This is a **library package**, not a standalone app - no typical Laravel app structure (no `app/`, `database/`, etc.)
- Uses **Orchestra Testbench** for testing Laravel features in isolation
- **Inertia.js** is required but only in `require-dev` (host apps provide it)
- **Vue components** are published stubs, not compiled assets
- **YAML drivers** are domain-specific and should be customized per use case
- Always run `php artisan config:clear` after modifying handler registrations

## Flow Lifecycle

1. **Start:** `POST /form-flow/start` with `reference_id`, `steps[]`, `callbacks`
2. **Render:** `GET /form-flow/{flow_id}` returns Inertia view for current step
3. **Submit:** `POST /form-flow/{flow_id}/step/{step}` with step data
4. **Progress:** Automatically advances to next step after successful submission
5. **Complete:** When all steps done, triggers `on_complete` callback with collected data
6. **Cleanup:** Optionally `DELETE /form-flow/{flow_id}` to clear session state

## Dependencies to Note

- `symfony/yaml` - YAML driver parsing
- `spatie/laravel-data` - DTOs with validation/transformation
- `illuminate/support` - Laravel core (contracts via composer, not full framework)
- `orchestra/testbench` (dev) - Laravel testing harness
- `inertiajs/inertia-laravel` (dev) - SSR framework integration
