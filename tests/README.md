# API Scout Test Suite

This directory contains the ApiScout testing suite.

## Quick Start

```bash
# Run all tests
composer test

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

## Test Structure

- **TestCase.php** - Base test case with Laravel setup
- **ApiScoutTest.php** - Main ApiScout class tests
- **ApiScoutResultTest.php** - Result object tests
- **DataTypes/** - Data type detection tests
- **Services/** - Service layer tests (Authentication, Endpoint Analysis)
- **Facades/** - Facade functionality tests
- **Exceptions/** - Exception handling tests

## Documentation

For detailed testing instructions, see [TESTING.md](../TESTING.md) in the project root.
