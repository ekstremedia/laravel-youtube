# Contributing to Laravel YouTube Package

First off, thank you for considering contributing to Laravel YouTube Package! It's people like you that make this package better for everyone.

## Code of Conduct

By participating in this project, you are expected to uphold our Code of Conduct:
- Be respectful and inclusive
- Be patient with others
- Accept constructive criticism gracefully
- Focus on what is best for the community

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the issue list as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

* **Use a clear and descriptive title**
* **Describe the exact steps which reproduce the problem**
* **Provide specific examples to demonstrate the steps**
* **Describe the behavior you observed after following the steps**
* **Explain which behavior you expected to see instead and why**
* **Include screenshots if possible**
* **Include your environment details** (OS, PHP version, Laravel version, etc.)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

* **Use a clear and descriptive title**
* **Provide a step-by-step description of the suggested enhancement**
* **Provide specific examples to demonstrate the steps**
* **Describe the current behavior and explain which behavior you expected to see instead**
* **Explain why this enhancement would be useful**

### Pull Requests

* Fill in the required template
* Do not include issue numbers in the PR title
* Follow the PHP coding standards (PSR-12)
* Include thoughtfully-worded, well-structured Pest tests
* Document new code based on the PHPDoc standard
* End all files with a newline

## Development Setup

1. Fork the repository
2. Clone your fork: `git clone git@github.com:your-username/laravel-youtube.git`
3. Install dependencies: `composer install`
4. Create a branch: `git checkout -b my-feature-branch`
5. Make your changes
6. Run tests: `composer test`
7. Run code style fixer: `composer format`
8. Run static analysis: `composer analyse`
9. Commit your changes: `git commit -am 'Add some feature'`
10. Push to the branch: `git push origin my-feature-branch`
11. Submit a pull request

## Coding Standards

This project follows PSR-12 coding standards and uses Laravel Pint for code formatting.

### Running Code Style Checks

```bash
# Check code style
composer format -- --test

# Fix code style automatically
composer format
```

### Running Static Analysis

```bash
composer analyse
```

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test file
vendor/bin/pest tests/Feature/YouTubeServiceTest.php

# Run tests in watch mode
vendor/bin/pest --watch
```

## Writing Tests

We use Pest for testing. All new features should include tests.

### Test Structure

```php
it('does something', function () {
    // Arrange
    $service = new SomeService();

    // Act
    $result = $service->doSomething();

    // Assert
    expect($result)->toBe('expected');
});
```

### Test Coverage

- Aim for 100% code coverage for new features
- All bug fixes should include a test that would have caught the bug
- Tests should be clear, focused, and well-documented

## Commit Messages

We follow conventional commits format:

```
type(scope): subject

body

footer
```

### Types

- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation only changes
- `style`: Changes that do not affect the meaning of the code
- `refactor`: A code change that neither fixes a bug nor adds a feature
- `perf`: A code change that improves performance
- `test`: Adding missing tests or correcting existing tests
- `chore`: Changes to the build process or auxiliary tools

### Examples

```
feat(upload): add support for 8K video uploads

Add support for uploading 8K resolution videos by increasing
the chunk size and adjusting upload parameters.

Closes #123
```

```
fix(auth): prevent token refresh loop

Fixed an issue where expired tokens would cause an infinite
refresh loop. Now properly handles refresh failures.

Fixes #456
```

## Documentation

- Update the README.md if you change functionality
- Update PHPDoc blocks for all public methods
- Add code examples where appropriate
- Keep documentation clear and concise

## Release Process

(For maintainers)

1. Update CHANGELOG.md
2. Update version in composer.json
3. Create a new GitHub release
4. Tag the release with semantic versioning (v1.2.3)
5. Publish to Packagist (happens automatically)

## Questions?

Feel free to open an issue with your question or reach out to the maintainers directly.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.