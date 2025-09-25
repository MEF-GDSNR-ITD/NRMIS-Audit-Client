# Changelog

All notable changes to `nrmis/audit-client` will be documented in this file.

## 1.1.0 - 2024-12-19

### Added
- Laravel 12 support
- Updated composer constraints to support Laravel 10, 11, and 12
- Updated testbench dependency for Laravel 12 testing

### Changed
- `illuminate/support`: `^10.0|^11.0` → `^10.0|^11.0|^12.0`
- `illuminate/http`: `^10.0|^11.0` → `^10.0|^11.0|^12.0`
- `illuminate/database`: `^10.0|^11.0` → `^10.0|^11.0|^12.0`
- `orchestra/testbench`: `^8.0|^9.0` → `^8.0|^9.0|^10.0`

## 1.0.0 - 2024-09-19

### Added
- Initial release
- HTTP client for centralized audit logging
- Support for multiple audit types (user actions, auth, system, security, performance)
- Correlation tracking across microservices
- Model auditing with Eloquent trait
- Async and sync logging modes
- Batch audit processing
- Health check functionality
- Error handling and graceful failures
- Comprehensive test suite
- Laravel service provider and facade
- Configurable options via environment variables
- Documentation and examples

### Features
- **AuditClient class** - Main HTTP client for sending audit logs
- **Auditable trait** - Automatic model auditing for Eloquent models
- **Facade support** - Easy Laravel integration
- **Multiple audit types** - User, auth, system, security, performance logging
- **Correlation tracking** - Link related events across services
- **Request/Session tracking** - Track events within requests and sessions
- **Batch processing** - Efficient bulk operations
- **Health monitoring** - Check audit service connectivity
- **Configuration management** - Extensive customization options
- **Error resilience** - Graceful handling of service failures

### Documentation
- Comprehensive README with examples
- Configuration reference
- Usage patterns and best practices
- Performance considerations
- Testing guidelines
