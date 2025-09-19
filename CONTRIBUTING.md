# Contributing to NRMIS Audit Client

Thank you for considering contributing to the NRMIS Audit Client! This document outlines the process for contributing to this project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Making Contributions](#making-contributions)
- [Testing](#testing)
- [Code Style](#code-style)
- [Pull Request Process](#pull-request-process)
- [Release Process](#release-process)

## Code of Conduct

This project adheres to a [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- Laravel 10.0 or higher
- Git

### Types of Contributions

We welcome various types of contributions:

- **Bug reports and fixes**
- **Feature requests and implementations**
- **Documentation improvements**
- **Code quality improvements**
- **Performance optimizations**
- **Security enhancements**

## Development Setup

1. **Fork the repository**
```bash
git clone https://github.com/your-username/audit-client.git
cd audit-client
```

2. **Install dependencies**
```bash
composer install
```

3. **Run tests to ensure everything works**
```bash
composer test
```

## Making Contributions

### Reporting Issues

Before creating a new issue, please:

1. **Search existing issues** to avoid duplicates
2. **Use the issue template** if available
3. **Provide detailed information**:
   - PHP version
   - Laravel version
   - Package version
   - Error messages
   - Steps to reproduce
   - Expected behavior

### Suggesting Features

When suggesting a feature:

1. **Check if it aligns** with the project goals
2. **Provide use cases** and examples
3. **Consider backward compatibility**
4. **Be open to discussion** and feedback

### Making Changes

1. **Create a branch** for your changes
```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/issue-description
```

2. **Make your changes** following our coding standards

3. **Add tests** for new functionality

4. **Update documentation** if needed

5. **Commit your changes** with descriptive messages
```bash
git commit -m "Add feature: correlation tracking enhancement"
```

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test
vendor/bin/phpunit --filter=AuditClientTest::test_can_log_audit_event
```

### Writing Tests

- **Unit tests** for individual methods
- **Integration tests** for complex workflows
- **Mock external dependencies** (HTTP calls, etc.)
- **Test both success and failure scenarios**

Example test:
```php
public function test_can_log_user_action()
{
    $this->mockHandler->append(new Response(201, [], json_encode([
        'success' => true,
        'data' => ['id' => 1]
    ])));

    $result = $this->auditClient->logUserAction(
        'profile_updated',
        123,
        'App\Models\User',
        ['name' => 'John'],
        ['name' => 'Jane']
    );

    $this->assertTrue($result);
}
```

### Test Coverage

- Aim for **80%+ code coverage**
- **Focus on critical paths** and edge cases
- **Test error handling** and validation

## Code Style

We follow the **Laravel coding standards** based on PSR-12.

### Automatic Formatting

```bash
# Check code style
composer analyse

# Fix code style issues
composer format
```

### Code Style Guidelines

1. **Use descriptive variable names**
```php
// Good
$auditData = $this->prepareAuditData($data);

// Bad
$d = $this->prep($data);
```

2. **Add type hints**
```php
public function log(array $data): bool
{
    // ...
}
```

3. **Use early returns**
```php
public function log(array $data): bool
{
    if (!$this->enabled) {
        return true;
    }
    
    // ... rest of the method
}
```

4. **Add docblocks for public methods**
```php
/**
 * Log an audit event
 *
 * @param array $data The audit data to log
 * @return bool True if successful, false otherwise
 */
public function log(array $data): bool
{
    // ...
}
```

## Pull Request Process

### Before Submitting

1. **Ensure tests pass**
```bash
composer test
```

2. **Check code style**
```bash
composer analyse
composer format
```

3. **Update documentation** if needed

4. **Add entry to CHANGELOG.md** if applicable

### PR Guidelines

1. **Use descriptive title** and description
2. **Reference related issues** using `#issue-number`
3. **Keep PRs focused** - one feature/fix per PR
4. **Include tests** for new functionality
5. **Update documentation** as needed

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Added tests for new functionality
- [ ] Updated existing tests if needed

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] Changelog updated
```

### Review Process

1. **Automated checks** must pass (tests, style)
2. **Code review** by maintainers
3. **Feedback incorporation** if needed
4. **Final approval** and merge

## Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.x.x): Breaking changes
- **MINOR** (x.1.x): New features, backward compatible  
- **PATCH** (x.x.1): Bug fixes, backward compatible

### Release Steps

1. **Update CHANGELOG.md**
```markdown
## 1.1.0 - 2024-09-20

### Added
- New correlation tracking features
- Enhanced error handling

### Fixed  
- Issue with async logging
- Memory leak in batch processing

### Changed
- Improved performance for large batches
```

2. **Create release PR**
3. **Tag version after merge**
```bash
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin v1.1.0
```

4. **Packagist auto-updates** via webhook

## Development Guidelines

### Architecture Principles

1. **Single Responsibility** - Each class has one job
2. **Open/Closed** - Open for extension, closed for modification
3. **Dependency Injection** - Inject dependencies, don't instantiate
4. **Error Handling** - Graceful failures, informative messages

### Performance Considerations

1. **Async by default** for better performance
2. **Batch operations** for multiple audits
3. **Connection pooling** for HTTP requests
4. **Minimal memory footprint**

### Security Guidelines

1. **Validate all inputs**
2. **Sanitize sensitive data**
3. **Use HTTPS** for communications
4. **Handle credentials securely**

## Getting Help

### Communication Channels

- **GitHub Issues** - Bug reports and feature requests
- **GitHub Discussions** - General questions and ideas
- **Email** - dev@nrmis.gov.kh for security issues

### Documentation

- **README.md** - Package overview and basic usage
- **Code comments** - Inline documentation
- **API documentation** - Generated from docblocks
- **Examples** - In the `/examples` directory

### Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Packagist Documentation](https://packagist.org/about)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [Semantic Versioning](https://semver.org/)

## Recognition

Contributors will be recognized in:

- **README.md** - Contributors section
- **CHANGELOG.md** - Release notes
- **GitHub Contributors** - Automatic recognition

## License

By contributing, you agree that your contributions will be licensed under the same [MIT License](LICENSE.md) as the project.

---

Thank you for contributing to NRMIS Audit Client! 🎉
