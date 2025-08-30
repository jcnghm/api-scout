# API Scout Testing Guide

This document provides detailed instructions for running and maintaining the API Scout test suite.

## Test Suite Overview

The API Scout package includes comprehensive tests covering all major functionality:

## Running Tests

### Prerequisites

Make sure you have the following installed:

- PHP 8.1 or higher
- Composer
- PHPUnit (installed via composer)

### Basic Test Execution

```bash
# Run all tests
composer test

# Or directly with PHPUnit
./vendor/bin/phpunit

# Run with coverage report
./vendor/bin/phpunit --coverage-html coverage/
```

### Running Specific Test Suites

```bash
# Run only data type tests
./vendor/bin/phpunit tests/DataTypes/

# Run only service tests
./vendor/bin/phpunit tests/Services/

# Run only facade tests
./vendor/bin/phpunit tests/Facades/

# Run a specific test file
./vendor/bin/phpunit tests/ApiScoutTest.php

# Run a specific test method
./vendor/bin/phpunit --filter test_analyze_single_endpoint
```

### Debugging Tests

```bash
# Run with verbose output
./vendor/bin/phpunit --verbose

# Run with debug output
./vendor/bin/phpunit --debug

# Run a single test with detailed output
./vendor/bin/phpunit --filter test_specific_method --verbose
```

## Coverage Reports

Generate coverage reports to ensure comprehensive testing:

```bash
# HTML coverage report
./vendor/bin/phpunit --coverage-html coverage/

# Text coverage report
./vendor/bin/phpunit --coverage-text

# Clover XML coverage (for CI/CD)
./vendor/bin/phpunit --coverage-clover coverage.xml
```

## Test Maintenance

### Adding New Tests

1. **Follow Naming Convention**: `TestNameTest.php`
2. **Extend TestCase**: Use the base `TestCase` class
3. **Use Descriptive Names**: Test methods should clearly describe what they test
4. **Mock External Dependencies**: Use mocks for HTTP requests and file operations
5. **Clean Up Resources**: Ensure tests don't leave side effects

### Test Data

- **Fixtures**: Store test data in the test files or separate fixture files
- **Realistic Data**: Use realistic API responses for testing
- **Edge Cases**: Include tests for error conditions and edge cases

### Continuous Integration

The test suite is designed to run in CI/CD environments:

```yaml
# Example GitHub Actions workflow
- name: Run Tests
  run: |
    composer install
    composer test
    ./vendor/bin/phpunit --coverage-clover coverage.xml
```

## 🔍 Test Quality Guidelines

1. **Arrange-Act-Assert**: Follow the AAA pattern for test structure
2. **Single Responsibility**: Each test should test one specific behavior
3. **Descriptive Names**: Test names should clearly indicate what is being tested
4. **Minimal Dependencies**: Tests should have minimal external dependencies
5. **Fast Execution**: Tests should execute quickly (< 100ms each)
6. **Reliable**: Tests should be deterministic and not flaky

## Failing Tests

If tests fail:

1. **Check Environment**: Ensure PHP version and extensions are correct
2. **Update Dependencies**: Run `composer update`
3. **Clear Caches**: Clear Laravel and Composer caches
4. **Check Configuration**: Verify `phpunit.xml` and test configuration
5. **Review Changes**: Check if recent changes broke existing functionality

## Writing New Tests

When adding new functionality, ensure you:

1. **Test Happy Path**: Test normal operation
2. **Test Error Cases**: Test error conditions and edge cases
3. **Test Integration**: Test how new code integrates with existing functionality
4. **Update Coverage**: Ensure new code is covered by tests
5. **Document Changes**: Update this guide if needed
