# NRMIS Audit Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nrmis/audit-client.svg?style=flat-square)](https://packagist.org/packages/nrmis/audit-client)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/nrmis/audit-client/run-tests?label=tests)](https://github.com/nrmis/audit-client/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/nrmis/audit-client/Check%20&%20fix%20styling?label=code%20style)](https://github.com/nrmis/audit-client/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nrmis/audit-client.svg?style=flat-square)](https://packagist.org/packages/nrmis/audit-client)

A Laravel package for centralized audit logging in microservices architecture. This package provides a robust HTTP client for sending audit logs to a centralized audit service, with support for various audit types, correlation tracking, and automatic model auditing.

## Features

- 🚀 **Easy Integration** - Simple Laravel service provider and facade
- 📝 **Multiple Audit Types** - User actions, authentication, system events, security events, performance monitoring
- 🔗 **Correlation Tracking** - Track related events across microservices
- 🎯 **Model Auditing** - Automatic auditing with Eloquent trait
- ⚡ **Async Support** - Fire-and-forget or synchronous logging
- 🛡️ **Error Handling** - Graceful failure handling with logging
- 📊 **Batch Processing** - Efficient bulk audit log submission
- 🏥 **Health Checks** - Monitor audit service connectivity
- 🔧 **Configurable** - Extensive configuration options
- 🧪 **Well Tested** - Comprehensive test suite

## Installation

You can install the package via composer:

```bash
composer require nrmis/audit-client
```

The package will automatically register its service provider.

You can publish the config file with:

```bash
php artisan vendor:publish --tag="audit-client-config"
```

This will publish the configuration file to `config/audit-client.php`.

## Configuration

### Environment Variables

Add these environment variables to your `.env` file:

```env
# Audit Service Configuration
AUDIT_SERVICE_URL=http://audit-service:7777/api/v1
AUDIT_SERVICE_NAME=your-service-name
AUDIT_SERVICE_VERSION=1.0.0
AUDIT_ENVIRONMENT=production
AUDIT_ENABLED=true
AUDIT_ASYNC=true
AUDIT_TIMEOUT=10
AUDIT_CONNECT_TIMEOUT=5

# Optional: API Security
AUDIT_API_KEY=your-api-key
AUDIT_VERIFY_SSL=true
```

### Configuration File

The configuration file allows you to customize various aspects of the audit client:

```php
return [
    'base_url' => env('AUDIT_SERVICE_URL', 'http://audit-service:7777/api/v1'),
    'service_name' => env('AUDIT_SERVICE_NAME', config('app.name')),
    'service_version' => env('AUDIT_SERVICE_VERSION', '1.0.0'),
    'environment' => env('AUDIT_ENVIRONMENT', config('app.env')),
    'enabled' => env('AUDIT_ENABLED', true),
    'async' => env('AUDIT_ASYNC', true),
    'timeout' => env('AUDIT_TIMEOUT', 10),
    'connect_timeout' => env('AUDIT_CONNECT_TIMEOUT', 5),
    // ... more configuration options
];
```

## Usage

### Basic Logging

```php
use Nrmis\AuditClient\Facades\AuditClient;

// Simple audit log
AuditClient::log([
    'event' => 'user_profile_updated',
    'user_id' => 123,
    'severity' => 'info',
    'metadata' => ['field' => 'email']
]);
```

### User Action Logging

```php
// Log user actions with old/new values
AuditClient::logUserAction(
    'profile_updated',
    $userId,
    'App\Models\User',
    ['email' => 'old@example.com'],  // old values
    ['email' => 'new@example.com'],  // new values
    ['changed_by' => 'admin'],       // metadata
    'info'                           // severity
);
```

### Authentication Events

```php
// Successful login
AuditClient::logAuth('login', $userId, 'App\Models\User', true, [
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent()
]);

// Failed login
AuditClient::logAuth('login_failed', null, null, false, [
    'username' => $username,
    'reason' => 'invalid_credentials'
]);
```

### System Events

```php
// System maintenance
AuditClient::logSystem('maintenance_started', 'warning', [
    'maintenance_type' => 'database_migration',
    'estimated_duration' => '30 minutes'
]);
```

### Security Events

```php
// Security threat detected
AuditClient::logSecurity('brute_force_attempt', $userId, 'brute_force', [
    'attempts' => 5,
    'blocked' => true,
    'ip_address' => $request->ip()
]);
```

### Performance Monitoring

```php
$startTime = microtime(true);
// ... perform operation
$duration = microtime(true) - $startTime;

AuditClient::logPerformance('database_query', $duration, [
    'query_type' => 'SELECT',
    'table' => 'users',
    'rows_affected' => 1250
]);
```

### Batch Logging

```php
$audits = [
    [
        'event' => 'user_created',
        'user_id' => 1,
        'severity' => 'info'
    ],
    [
        'event' => 'role_assigned',
        'user_id' => 1,
        'severity' => 'info'
    ]
];

$result = AuditClient::logBatch($audits);
// Returns: ['success' => true, 'created' => 2, 'errors' => 0, 'details' => [...]]
```

### Correlation Tracking

Track related events across services:

```php
$correlationId = Str::uuid();

// Related events across services
AuditClient::withCorrelation($correlationId)
    ->logUserAction('order_created', $userId);

AuditClient::withCorrelation($correlationId)
    ->logUserAction('payment_processed', $userId);

AuditClient::withCorrelation($correlationId)
    ->logUserAction('inventory_updated', $userId);
```

### Request and Session Tracking

```php
// Track events within a request
AuditClient::withRequest($requestId)
    ->log(['event' => 'request_processed']);

// Track events within a session
AuditClient::withSession($sessionId)
    ->log(['event' => 'user_action']);

// Chain multiple tracking IDs
AuditClient::withCorrelation($correlationId)
    ->withRequest($requestId)
    ->withSession($sessionId)
    ->logUserAction('complex_operation', $userId);
```

### Model Auditing

Use the `Auditable` trait to automatically audit model changes:

```php
use Illuminate\Database\Eloquent\Model;
use Nrmis\AuditClient\Traits\Auditable;

class User extends Model
{
    use Auditable;

    // Specify attributes to audit (optional - defaults to fillable)
    protected $auditableAttributes = [
        'name', 'email', 'status'
    ];

    // Exclude sensitive attributes (optional)
    protected $auditExclude = [
        'password', 'remember_token'
    ];
}
```

Now model changes are automatically audited:

```php
// Automatically logs 'created' event
$user = User::create(['name' => 'John', 'email' => 'john@example.com']);

// Automatically logs 'updated' event with old/new values
$user->update(['name' => 'Jane']);

// Automatically logs 'deleted' event
$user->delete();
```

#### Manual Model Auditing

```php
// Log custom events for models
$user->logAuditEvent('password_reset', null, null, [
    'reset_method' => 'email',
    'initiated_by' => 'user'
]);
```

#### Temporary Disable Auditing

```php
// Disable auditing for specific operations
$user->withoutAuditing(function ($model) {
    $model->update(['last_login' => now()]);
    // This update won't be audited
});
```

### Health Checks

Monitor the audit service connectivity:

```php
$health = AuditClient::healthCheck();

if ($health['healthy']) {
    echo "Audit service is running: " . $health['service'];
} else {
    echo "Audit service error: " . $health['error'];
}
```

### Enable/Disable Auditing

```php
// Disable auditing temporarily
$disabledClient = AuditClient::enable(false);
$disabledClient->log(['event' => 'test']); // Won't send to service

// Check if auditing is enabled
if (AuditClient::isEnabled()) {
    // Auditing is active
}

// Get current configuration
$config = AuditClient::getConfig();
```

## Advanced Usage

### Custom HTTP Client Configuration

```php
use Nrmis\AuditClient\AuditClient;

$client = new AuditClient([
    'base_url' => 'https://custom-audit-service.com/api/v1',
    'service_name' => 'custom-service',
    'timeout' => 30,
    'verify_ssl' => false,
    'default_metadata' => [
        'environment' => 'staging',
        'region' => 'us-west-2'
    ]
]);
```

### Error Handling

The package handles errors gracefully and logs them:

```php
// Even if the audit service is down, your application continues
$result = AuditClient::log(['event' => 'important_action']);

if (!$result) {
    // Audit failed, but your app keeps running
    // Error details are logged to your application logs
}
```

### Testing

When testing your application, you may want to disable audit logging:

```php
// In your test setup
config(['audit-client.enabled' => false]);

// Or use environment variables
// AUDIT_ENABLED=false
```

### Performance Considerations

1. **Use Async Mode**: Enable async mode for better performance
```php
config(['audit-client.async' => true]);
```

2. **Batch Operations**: Use batch logging for multiple events
```php
AuditClient::logBatch($multipleAudits);
```

3. **Selective Auditing**: Only audit what's necessary
```php
class User extends Model
{
    use Auditable;
    
    protected $auditableAttributes = ['email', 'status']; // Only audit these
    protected $auditExclude = ['password', 'remember_token', 'updated_at'];
}
```

## Configuration Reference

| Option | Default | Description |
|--------|---------|-------------|
| `base_url` | `http://audit-service:7777/api/v1` | Audit service API URL |
| `service_name` | `config('app.name')` | Name of your service |
| `service_version` | `1.0.0` | Version of your service |
| `environment` | `config('app.env')` | Environment (production, staging, etc.) |
| `enabled` | `true` | Enable/disable audit logging |
| `async` | `true` | Use asynchronous HTTP requests |
| `timeout` | `10` | HTTP timeout in seconds |
| `connect_timeout` | `5` | HTTP connection timeout in seconds |
| `retry_attempts` | `3` | Number of retry attempts |
| `retry_delay` | `1000` | Delay between retries (ms) |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [NRMIS Development Team](https://github.com/nrmis)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

For support, email dev@nrmis.gov.kh or create an issue on GitHub.

## Related Packages

- [nrmis/audit-service](https://github.com/nrmis/audit-service) - The centralized audit service
- [owen-it/laravel-auditing](https://github.com/owen-it/laravel-auditing) - Local Laravel auditing package

---

Made with ❤️ by the NRMIS team for the Laravel community.
