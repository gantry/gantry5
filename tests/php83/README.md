# PHP 8.3 Compatibility Test Suite for Gantry5

This test suite is designed to validate Gantry5 compatibility with PHP 8.3. It includes tests for critical components that may be affected by PHP 8.3 changes.

## Test Categories

### Framework Tests
Tests core Gantry framework functionality:
- Gantry instance creation and container access
- Service registration and retrieval 
- Debug functionality

### Platform Tests
Tests platform-specific functionality:
- Platform detection
- Joomla-specific features
- WordPress-specific features

### Component Tests
Tests individual components:
- PHP 8.3 type system (nullable and union types)
- Trait compatibility
- Twig integration (filters, functions, extensions)

## Running Tests

Run the tests using PHPUnit:

```bash
# From the Gantry5 root directory
vendor/bin/phpunit
```

## Adding New Tests

To add new PHP 8.3 compatibility tests:

1. Create a new test file in the appropriate directory:
   - `tests/php83/Framework/` for core framework tests
   - `tests/php83/Platform/` for platform-specific tests
   - `tests/php83/Component/` for component tests

2. Tests should extend `PHPUnit\Framework\TestCase`

3. Focus on PHP 8.3 specific features like:
   - Proper handling of nullable and union types
   - Deprecation warnings or errors
   - New language feature compatibility

## Reporting Issues

If you find PHP 8.3 compatibility issues, please open an issue on GitHub with:
- Detailed description of the issue
- Steps to reproduce
- PHP 8.3 error messages or warnings
- Suggestions for fixes if available