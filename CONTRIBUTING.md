# Contributing to WP AI Assistant

Thank you for your interest in contributing to WP AI Assistant! This document provides guidelines and instructions for contributing to the project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How to Contribute](#how-to-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Git Workflow](#git-workflow)
- [Pull Request Process](#pull-request-process)
- [Testing Requirements](#testing-requirements)
- [Documentation Requirements](#documentation-requirements)
- [Accessibility Requirements](#accessibility-requirements)

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inspiring community for all. Please be respectful and constructive in your interactions.

### Our Standards

**Positive Behavior**:
- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Unacceptable Behavior**:
- Trolling, insulting/derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information without explicit permission
- Other conduct which could reasonably be considered inappropriate

### Enforcement

Instances of abusive, harassing, or otherwise unacceptable behavior may be reported to the project team at hello@kanopi.com. All complaints will be reviewed and investigated promptly and fairly.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates.

**When creating a bug report, include**:
- **Clear title** - Descriptive summary of the issue
- **Description** - Detailed explanation of the problem
- **Steps to reproduce** - Numbered steps to recreate the issue
- **Expected behavior** - What you expected to happen
- **Actual behavior** - What actually happened
- **Environment**:
  - WordPress version
  - PHP version
  - Plugin version
  - Browser (if frontend issue)
  - Node.js version (if indexer issue)
- **Screenshots** - If applicable
- **Error messages** - Full error text and stack traces

**Example**:
```markdown
## Bug: Search returns no results despite indexed content

### Description
AI search returns "No results found" even though content has been indexed successfully.

### Steps to Reproduce
1. Index content with `wp ai-indexer index`
2. Verify indexing succeeded (450 vectors created)
3. Navigate to search page
4. Enter query "WordPress development"
5. Submit search

### Expected Behavior
Should return relevant pages about WordPress development.

### Actual Behavior
Returns "No results found" message.

### Environment
- WordPress: 6.4.2
- PHP: 8.2.0
- Plugin: 1.0.0
- Browser: Chrome 120

### Error Messages
None visible in browser console or PHP error log.
```

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues.

**When creating an enhancement suggestion, include**:
- **Clear title** - Concise feature name
- **Use case** - Why is this feature needed?
- **Proposed solution** - How should it work?
- **Alternatives considered** - Other approaches you've thought about
- **Additional context** - Screenshots, mockups, examples

### Contributing Code

1. **Fork the repository**
2. **Create a feature branch** from `main`
3. **Make your changes** following our coding standards
4. **Write tests** for new functionality
5. **Update documentation** as needed
6. **Submit a pull request**

### Contributing Documentation

Documentation improvements are always welcome:
- Fix typos or unclear explanations
- Add examples and use cases
- Improve organization
- Translate to other languages

## Development Setup

### Prerequisites

**Required**:
- **PHP**: 8.0 or higher
- **Composer**: 2.x
- **Node.js**: 18.x or higher
- **npm**: 8.x or higher
- **WordPress**: 5.6 or higher

**Recommended**:
- **DDEV**: For local WordPress development
- **Git**: For version control
- **VS Code**: With PHP and WordPress extensions

### Local Development Setup

#### Option 1: Using DDEV (Recommended)

```bash
# Navigate to project root
cd path/to/kanopi-2019

# Ensure plugin is activated
ddev wp plugin activate wp-ai-assistant

# Install PHP dependencies
ddev composer install --working-dir=web/wp-content/plugins/wp-ai-assistant

# Install indexer package (monorepo setup)
ddev exec "cd packages/wp-ai-indexer && npm install && npm run build"

# Configure environment variables in .ddev/config.yaml
# Add:
# web_environment:
#   - OPENAI_API_KEY=sk-...
#   - PINECONE_API_KEY=...
#   - WP_AI_INDEXER_KEY=your-secure-key

# Restart DDEV to apply environment variables
ddev restart

# Verify installation
ddev wp ai-indexer check
```

#### Option 2: Standalone WordPress

```bash
# Navigate to plugin directory
cd wp-content/plugins/wp-ai-assistant

# Install PHP dependencies
composer install

# Install indexer package
cd indexer
npm install

# Configure environment variables in wp-config.php or .env
# Add:
# define('OPENAI_API_KEY', 'sk-...');
# define('PINECONE_API_KEY', '...');
# define('WP_AI_INDEXER_KEY', 'your-secure-key');

# Activate plugin
wp plugin activate wp-ai-assistant

# Verify installation
wp ai-indexer check
```

### Development Tools Setup

#### Install Development Dependencies

```bash
# PHP dev dependencies (already in composer.json)
composer install

# Install code quality tools
composer require --dev squizlabs/php_codesniffer
composer require --dev wp-coding-standards/wpcs
composer require --dev phpcompatibility/phpcompatibility-wp

# Configure PHPCS
vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs,vendor/phpcompatibility/phpcompatibility-wp
```

#### VS Code Extensions

Recommended extensions for development:

```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "wordpresstoolbox.wordpress-toolbox",
    "ms-vscode.vscode-typescript-next",
    "dbaeumer.vscode-eslint",
    "esbenp.prettier-vscode"
  ]
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run integration tests only
composer test:integration

# Generate coverage report
composer test:coverage
# Opens coverage/index.html in browser
```

### Development Workflow

```bash
# Start DDEV environment
ddev start

# Watch for file changes (if you set up file watching)
npm run watch

# Make changes to code

# Run linters
composer phpcs

# Auto-fix style issues
composer phpcbf

# Run tests
composer test

# Index test content
ddev wp ai-indexer index --debug

# Stop DDEV when done
ddev stop
```

## Coding Standards

We follow WordPress Coding Standards with some modifications for modern PHP practices.

### PHP Standards

#### WordPress Coding Standards

Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) with these notes:

**File Naming**:
```
class-wp-ai-core.php          ✓ Correct
WP_AI_Core.php                ✗ Wrong
```

**Class Naming**:
```php
class WP_AI_Core {}           ✓ Correct (WordPress style)
class wpAiCore {}             ✗ Wrong
class WpAiCore {}             ✗ Wrong
```

**Function Naming**:
```php
public function get_settings() {}      ✓ Correct (snake_case)
public function getSettings() {}       ✗ Wrong (camelCase)
```

**Variable Naming**:
```php
$post_id = 123;              ✓ Correct (snake_case)
$postId = 123;               ✗ Wrong (camelCase)
```

#### Modern PHP Features (Allowed)

We use PHP 8.0+ features:

```php
// Type declarations ✓
public function get_setting(string $key, mixed $default = ''): mixed {}

// Null coalescing operator ✓
$value = $settings['key'] ?? 'default';

// Spaceship operator ✓
usort($array, fn($a, $b) => $a <=> $b);

// Arrow functions ✓
array_map(fn($x) => $x * 2, $array);
```

#### DocBlocks

**Required for all public methods**:

```php
/**
 * Create embedding vector using OpenAI API
 *
 * Converts text into a numerical vector representation using OpenAI's
 * embedding models. Results are cached to reduce API calls.
 *
 * @since 1.0.0
 *
 * @param string $text Text to embed (max 8191 tokens)
 * @return array|WP_Error Embedding vector (1536 floats) or error
 *
 * @throws InvalidArgumentException If text is empty
 *
 * @example
 * $embedding = $openai->create_embedding('Hello world');
 * if (!is_wp_error($embedding)) {
 *     echo count($embedding); // 1536
 * }
 */
public function create_embedding(string $text) {
    // Implementation
}
```

**DocBlock Elements**:
- **Summary** (required) - One-line description
- **Description** (optional) - Detailed explanation
- **`@since`** (required) - Version introduced
- **`@param`** (required for parameters) - Type, name, description
- **`@return`** (required if returns value) - Type and description
- **`@throws`** (optional) - Exceptions thrown
- **`@example`** (recommended) - Usage example

### JavaScript Standards

Follow WordPress JavaScript coding standards:

**Variable Naming**:
```javascript
const userName = 'John';      // ✓ camelCase
const user_name = 'John';     // ✗ Wrong in JS
```

**Function Naming**:
```javascript
function handleSubmit() {}    // ✓ camelCase
function handle_submit() {}   // ✗ Wrong
```

**Constants**:
```javascript
const API_ENDPOINT = '...';   // ✓ UPPER_SNAKE_CASE
const apiEndpoint = '...';    // ✗ Wrong for constants
```

**JSDoc Comments**:
```javascript
/**
 * Load Deep Chat library dynamically
 *
 * @param {Function} callback - Function to call when loaded
 * @returns {void}
 */
function loadDeepChat(callback) {
    // Implementation
}
```

### CSS/SCSS Standards

Follow WordPress CSS coding standards:

```css
/* ✓ Correct */
.wp-ai-chatbot {
  display: flex;
  flex-direction: column;
}

.wp-ai-chatbot__popup {
  position: fixed;
  z-index: 9999;
}

/* ✗ Wrong - inconsistent indentation, missing space after : */
.wp-ai-chatbot{
    display:flex;
  flex-direction: column;
}
```

### Linting

Run linters before committing:

```bash
# PHP
composer phpcs

# Auto-fix PHP issues
composer phpcbf

# JavaScript (if configured)
npm run lint

# Auto-fix JS issues
npm run lint:fix
```

**Fix common issues**:
```bash
# Fix indentation
composer phpcbf

# Fix line endings
git config core.autocrlf false
```

## Git Workflow

### Branching Strategy

**Main Branches**:
- `main` - Production-ready code
- `develop` - Integration branch for features (if used)

**Feature Branches**:
```bash
# Create feature branch from main
git checkout main
git pull origin main
git checkout -b feature/add-custom-boost

# Create bugfix branch from main
git checkout -b bugfix/fix-rate-limiting

# Create hotfix branch from main
git checkout -b hotfix/security-patch
```

**Branch Naming**:
- `feature/short-description` - New features
- `bugfix/issue-description` - Bug fixes
- `hotfix/urgent-fix` - Urgent production fixes
- `docs/improvement` - Documentation updates
- `refactor/component-name` - Code refactoring

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

**Format**:
```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat` - New feature
- `fix` - Bug fix
- `docs` - Documentation changes
- `style` - Code style changes (formatting, no logic change)
- `refactor` - Code refactoring
- `test` - Adding or updating tests
- `chore` - Maintenance tasks

**Examples**:
```bash
# Good commits
git commit -m "feat(search): add post type relevance boosting"
git commit -m "fix(cache): prevent cache key collision in multisite"
git commit -m "docs(api): add examples for WP_AI_OpenAI class"

# Bad commits (avoid these)
git commit -m "fixed stuff"
git commit -m "updates"
git commit -m "WIP"
```

**Multi-line commits**:
```bash
git commit -m "feat(chatbot): add rate limiting per IP address

Implements rate limiting to prevent API abuse. Default limit is
10 requests per minute per IP address.

- Add WP_AI_Chatbot_Module::check_rate_limit()
- Add transient-based request tracking
- Add filters for customization
- Add tests for rate limiting logic

Closes #123"
```

### Keeping Your Branch Updated

```bash
# Update your feature branch with latest main
git checkout main
git pull origin main
git checkout feature/your-feature
git rebase main

# Resolve conflicts if any
# git add <resolved-files>
# git rebase --continue

# Force push to update your PR
git push origin feature/your-feature --force-with-lease
```

## Pull Request Process

### Before Submitting

**Checklist**:
- [ ] Code follows WordPress coding standards
- [ ] All tests pass (`composer test`)
- [ ] No linting errors (`composer phpcs`)
- [ ] New features have tests
- [ ] Documentation updated
- [ ] Accessibility tested (if UI changes)
- [ ] CHANGELOG.md updated (if applicable)
- [ ] Commit messages follow conventions

### Creating a Pull Request

1. **Push your branch to GitHub**:
```bash
git push origin feature/your-feature
```

2. **Open Pull Request** on GitHub

3. **Fill out PR template**:

```markdown
## Description
Brief description of changes and why they're needed.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Related Issue
Closes #123

## Changes Made
- Added rate limiting to chatbot endpoint
- Implemented IP-based request tracking
- Added filters for customization

## Testing
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] Manual testing completed

### Manual Testing Steps
1. Submit 11 chat requests in quick succession
2. Verify 11th request returns 429 error
3. Wait 60 seconds
4. Verify next request succeeds

## Screenshots
(If applicable)

## Accessibility
- [ ] Keyboard navigation tested
- [ ] Screen reader tested
- [ ] Color contrast verified

## Performance
- [ ] No performance degradation
- [ ] Caching utilized where appropriate

## Documentation
- [ ] Code comments added/updated
- [ ] API documentation updated
- [ ] README updated (if needed)

## Checklist
- [ ] Code follows WordPress coding standards
- [ ] Tests pass locally
- [ ] No linting errors
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
```

### Review Process

**What to expect**:
1. **Automated checks** - CI runs tests and linting
2. **Code review** - Maintainer reviews code quality, architecture
3. **Feedback** - Requested changes or questions
4. **Approval** - PR approved when ready
5. **Merge** - Merged into main branch

**Response times**:
- Initial review: Within 1 week
- Follow-up reviews: Within 3 business days

### Addressing Feedback

```bash
# Make requested changes
# Add commits (don't amend if already reviewed)
git add .
git commit -m "fix: address review feedback"
git push origin feature/your-feature

# PR automatically updates
```

## Testing Requirements

### Required Tests

**New Features**:
- Must include unit tests
- Integration tests if modifying core functionality
- Manual testing documented in PR

**Bug Fixes**:
- Must include test that reproduces bug
- Test must fail before fix, pass after fix

### Test Organization

```
tests/
├── Unit/                      # Unit tests (isolated)
│   ├── Core/                  # Core functionality tests
│   │   ├── CoreValidationTest.php
│   │   └── SecretsTest.php
│   ├── CLI/                   # CLI command tests
│   ├── REST/                  # REST API tests
│   └── SystemCheck/           # System check tests
├── Integration/               # Integration tests
│   └── CLI/
│       └── CommandIntegrationTest.php
├── Fixtures/                  # Test data
│   └── settings-response.json
├── Helpers/                   # Test utilities
│   ├── TestCase.php
│   └── MockNodeExecutor.php
└── bootstrap.php             # Test bootstrap
```

### Writing Tests

**Unit Test Example**:
```php
<?php
namespace WP_AI_Tests\Unit\Core;

use WP_AI_Core;
use WP_AI_Tests\Helpers\TestCase;

class CoreValidationTest extends TestCase {

    public function test_validate_chatbot_temperature() {
        $core = new WP_AI_Core();

        // Test valid temperature
        $validated = $core->validate_settings([
            'chatbot_temperature' => 0.5
        ]);
        $this->assertEquals(0.5, $validated['chatbot_temperature']);

        // Test temperature clamping
        $validated = $core->validate_settings([
            'chatbot_temperature' => 5.0  // Too high
        ]);
        $this->assertEquals(2.0, $validated['chatbot_temperature']);

        $validated = $core->validate_settings([
            'chatbot_temperature' => -1.0  // Too low
        ]);
        $this->assertEquals(0.0, $validated['chatbot_temperature']);
    }
}
```

### Running Specific Tests

```bash
# Run specific test file
composer test tests/Unit/Core/CoreValidationTest.php

# Run specific test method
vendor/bin/phpunit --filter test_validate_chatbot_temperature

# Run tests with coverage
composer test:coverage
```

### Test Coverage

**Coverage Requirements**:
- New code: Minimum 80% coverage
- Critical paths: 100% coverage recommended

**Check coverage**:
```bash
composer test:coverage
# Opens HTML report in browser
```

## Documentation Requirements

### Required Documentation

**All new features must include**:
1. **Code comments** - Inline explanations for complex logic
2. **DocBlocks** - Complete PHPDoc for all public methods
3. **API documentation** - Update docs/API.md
4. **User documentation** - Update README.md if user-facing
5. **Examples** - Add to docs/examples/ if applicable

### Documentation Standards

**API Documentation**:
- Include all parameters with types
- Document return values
- Provide usage examples
- Note any side effects

**User Documentation**:
- Step-by-step instructions
- Screenshots for UI features
- Common issues and solutions
- Prerequisites clearly stated

### Updating Documentation

```bash
# Before submitting PR, verify docs are current
grep -r "TODO" docs/  # Should return nothing

# Check for outdated version numbers
grep -r "1.0.0" docs/  # Update if changed
```

## Accessibility Requirements

All UI changes must meet WCAG 2.1 Level AA standards.

### Required Testing

**Keyboard Navigation**:
- [ ] All interactive elements reachable via Tab
- [ ] Tab order is logical
- [ ] Focus indicators visible
- [ ] No keyboard traps

**Screen Readers**:
- [ ] Test with NVDA (Windows) or VoiceOver (Mac)
- [ ] All images have alt text
- [ ] Form fields have labels
- [ ] Status messages announced

**Visual**:
- [ ] Color contrast meets WCAG AA (4.5:1 for text)
- [ ] Focus indicators visible
- [ ] Text resizable to 200%
- [ ] No content lost when zoomed

### Testing Tools

**Automated**:
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE](https://wave.webaim.org/)

**Manual**:
- Keyboard navigation
- Screen reader (NVDA/VoiceOver)
- Color contrast checker

### Accessibility Checklist

```markdown
## Accessibility Testing

### Keyboard Navigation
- [x] All buttons accessible via Tab
- [x] Modal closes with Escape
- [x] Focus trapped in modal when open
- [x] Focus returns to trigger on close

### Screen Reader
- [x] Tested with NVDA
- [x] All interactive elements labeled
- [x] Loading states announced
- [x] Error messages announced

### Visual
- [x] Color contrast verified (4.5:1+)
- [x] Focus indicators visible (blue outline)
- [x] Tested at 200% zoom
- [x] Reduced motion respected

### Semantic HTML
- [x] Proper heading hierarchy
- [x] Landmark regions defined
- [x] Forms properly labeled
- [x] ARIA used appropriately
```

---

## Getting Help

**Questions?**
- Open a GitHub Discussion
- Email: hello@kanopi.com

**Found a security issue?**
- **Do not** open a public issue
- Email: hello@kanopi.com with "SECURITY" in subject

**Need clarification?**
- Comment on the relevant issue or PR
- Tag @kanopi/developers for attention

---

## Recognition

Contributors will be recognized in:
- CHANGELOG.md for significant contributions
- GitHub contributors page
- Plugin credits (for major features)

Thank you for contributing to WP AI Assistant! 🎉
