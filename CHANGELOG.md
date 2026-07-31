# Changelog

All notable changes to `form-flow` will be documented in this file.

## v1.8.0 - 2026-07-31

### Added
- Claim-experience and claim-workflow metadata on active, splash, and completion pages
- Contextual claim confirmation labels and consumed-splash controls
- Configurable form UI variants with reusable screen and action components
- Dynamic readonly and persistence field configuration
- Laravel 13, Inertia 3, and Pest 4 compatibility

### Changed
- Form Flow no longer couples its driver service to the voucher package
- Unresolved and circular form variables are cleared before browser exposure

## v1.0.0 - 2025-12-23

### Added
- Initial release of form-flow package
- Driver-based form flow orchestration system
- DirXML-style mapping support
- Built-in handlers: FormHandler, SplashHandler, MissingHandler
- Vue components: GenericForm, Splash, Complete, MissingHandler
- Configurable route prefix and middleware
- Auto-discovery of drivers
- CSRF protection for server-to-server endpoints
- Publishable config, drivers, and Vue components
- Support for Laravel 11 and 12

### Changed
- Package renamed from `lbhurtado/form-flow-manager` to `3neti/form-flow`
- License changed to MIT

### Technical Details
- Namespace: `LBHurtado\FormFlowManager`
- PHP: ^8.2
- Laravel: ^11.0 || ^12.0
