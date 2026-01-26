# Drupal Starter - AI Agent Instructions

This is a Drupal 10/11 starter project using DDEV, Robo, Pantheon, and Drupal best practices.

## Core Principles

- Follow Drupal 10/11 best practices with "return early" coding style
- All operations happen inside DDEV containers
- Always satisfy linting before committing (`ddev phpcs` and `ddev phpstan`)
- Execute tests selectively for speed (`ddev phpunit --filter TestName`)
- Use `gh` CLI for GitHub operations
- Only `git add` files you intended to change
- Always specify branch name when doing `git push`
- Never do `git push --force`

## Drupal Best Practices

### Patterns to Follow ✅
- Use Drupal APIs (Entity, Form, Cache, Database APIs)
- Dependency injection for services
- Configuration entities for admin features
- Proper hooks (`hook_form_alter`, `hook_theme`)
- Render arrays for HTML output
- Translation with `t()`, `\Drupal::translation()`
- Cache API with proper tags/contexts
- Plugins for extensibility
- Form API validation

### Patterns to Avoid 🚫
- Direct database queries (use Entity Query or Database API)
- Hardcoded strings (use configuration or translation)
- Raw HTML output (use render arrays)
- Global variables (use dependency injection)
- jQuery (use vanilla JS and ES6+ unless absolutely necessary)
- Bypassing permissions (never bypass Drupal's permission system)
- Ignoring cache invalidation (always invalidate caches properly)

### Security & Access Control
- Always implement proper access control for custom functionality
- Validate all inputs and sanitize outputs consistently
- Use permission system: Check user permissions before granting access
- Sanitize output using `Xss::filter`, `Html::escape`
- Validate forms using Form API validation
- Use render arrays for automatic sanitization
- Check entity access with `$entity->access()` before operations

### Frontend Development
- Prefer Vanilla JavaScript: Use ES6+ features over jQuery
- Drupal Behaviors: Use `Drupal.behaviors` for proper initialization and AJAX compatibility
- Native DOM APIs: Prefer modern DOM APIs over legacy jQuery patterns
- Progressive Enhancement: Ensure functionality works without JavaScript when possible

## Code Quality

### Linting (Required Before Commit)
```bash
ddev phpcs      # Code style check
ddev phpstan    # Static analysis
```

### Code Comments Philosophy
Only add comments that provide value beyond the code:
- Explain **why** decisions were made (business logic, edge cases, workarounds)
- Provide **context** not obvious from code (issue references, external requirements)
- Describe **trade-offs** and non-obvious implications

Avoid comments that restate the code.

### Code Style Guidelines

**PHP Files:**
- Must start with `declare(strict_types=1);` after opening `<?php` tag
- Use proper namespace declarations matching directory structure
- Follow PSR-4 autoloading: `Drupal\module_name\subnamespace`
- Use type hints for all method parameters and return types
- Use Drupal's dependency injection for services, avoid static `\Drupal::service()` calls in production code
- All user-facing strings must use `t()` or `\Drupal::translation()` for translation
- Use render arrays, never `echo` or `print` for HTML output
- Always validate user input using Form API validation handlers
- Check entity access with `$entity->access()` before operations
- Sanitize output using `Xss::filter()` or `Html::escape()` when needed

**Naming Conventions:**
- Classes: `PascalCase` (e.g., `NodeLandingPage`, `ElementWrapThemeTrait`)
- Methods: `camelCase` with descriptive names (e.g., `buildFull`, `wrapContainerWide`)
- Variables: `camelCase` (e.g., `$element`, `$bg_color`)
- Constants: `UPPER_SNAKE_CASE` (e.g., `INSTALLED_LANGUAGES`)
- Files: `PascalCase.php` for classes matching class names

**Error Handling:**
- Never suppress errors with `@` operator
- Use Drupal's exception hierarchy for proper error handling
- Log errors using `\Drupal::logger()->error()` with context
- Return early on validation failures to reduce nesting depth

**Imports:**
- Always use fully-qualified namespaces in `use` statements
- Group imports: Drupal Core first, then contributed modules, then custom modules
- Sort imports alphabetically within each group
- Use trailing comma in multi-line use statements for clarity

## Project Structure

### Custom Modules
- **server_general**: Core functionality, entity view builders, utilities
- **server_migrate**: CSV-based migrations for demo/default content
- **server_style_guide**: Style guide for frontend components
- **server_default_content**: Default content using `default_content` module

### Key Technologies
- **DDEV**: Local development environment
- **Robo**: Task automation (PHP-based)
- **Pluggable Entity View Builder (PEVB)**: Entity rendering pattern
- **Tailwind CSS 3.x**: Theme styling (JIT mode)
- **Drupal Test Traits (DTT)**: Testing framework

## Development Workflow

### Starting Development
```bash
ddev start              # Start DDEV environment
ddev login              # Log in as admin user
```

### Running Tests
See Testing section below.

### Translation Management

**UI Translations** (`config/po_files/*.po`):
- Import: `ddev robo locale:import`
- Export: Enable potx, then `ddev drush potx --files path/to/file.php`

**Config Translations** (`config/po_files/*_config.po`):
- Export: Update `managed-config.txt`, then `ddev robo locale:export-from-config`
- Import: `ddev robo locale:import-to-config` then `ddev drush config:export`

## Git & GitHub Workflow

### Pull Requests
When opening a PR for a specific issue: `#[issue number]` followed by brief summary.

### Commit Messages
Keep short - summarize what was done.

### Repository Visibility
Never link/mention private repositories when working on public repositories.

## Common Commands

### DDEV Commands
```bash
ddev start/stop/restart  # Manage environment
ddev login               # Login to site as admin
ddev drush [command]     # Run drush command
ddev composer [command]  # Run composer command
ddev ssh                 # SSH into web container
```

### Drupal Commands
```bash
ddev drush deploy        # Main command after git pull (runs updb + cim + cr)
ddev drush cex           # Export config
ddev drush uli           # Generate login link

# Standalone commands:
ddev drush cr            # Clear cache
ddev drush cim           # Import config
ddev drush updb          # Run database updates
```

### Custom DDEV Commands
```bash
ddev phpunit [filter]                 # Run PHPUnit tests (see Testing section)
ddev phpcs                            # Run PHP Code Sniffer
ddev phpstan                          # Run PHPStan static analysis
ddev phpunit-contrib <module_name>   # Run contrib module tests
```

## Testing

### Test Types

**Preferred approach**: Use `weitzman\DrupalTestTraits\ExistingSiteBase` for tests in this project, rather than Kernel or Unit tests. ExistingSite tests run against a real Drupal installation.

1. **Drupal Test Traits (DTT)**: Fast tests on existing installation using `ExistingSiteBase`
2. **Selenium Tests**: Headless Chrome with JavaScript support

### Running Tests

**All tests (parallel + sequential):** `ddev phpunit`

**Single test file:** `ddev phpunit web/modules/custom/server_general/tests/src/ExistingSite/ServerGeneralHomepageTest.php`

**Specific test method:** `ddev phpunit --filter testHomeFeaturedContent`

**Filter by test class:** `ddev phpunit --filter ServerGeneralHomepageTest`

**Tests in specific module:** `ddev phpunit web/modules/custom/server_general`

**Note**: `@group sequential` tests run separately. Rollbar tests excluded by default. Use `--group=Rollbar` to include them.

## Architecture Patterns

### Pluggable Entity View Builder (PEVB)

**Important**: This project does NOT use standard Drupal rendering with view modes configured in the UI. All entity rendering is handled through PEVB plugins defined in code.

Entity rendering via PEVB plugins. Example: `web/modules/custom/server_general/src/Plugin/EntityViewBuilder/NodeLandingPage.php`

View modes and display configurations must be defined in code via PEVB plugins, not through Drupal's UI manage display.

### Responsive Images
1. Define component rules (how images transform at breakpoints)
2. Determine largest dimensions per breakpoint
3. Create image styles: `[component]_[breakpoint]_[multiplier]` (e.g., `hero_md_1x`)
4. Create responsive image style using `server_theme.breakpoints.yml`
5. Use in PEVB: `BuildFieldTrait::buildMediaResponsiveImage()`

### Breakpoints
Tailwind breakpoints must match Drupal breakpoints in `server_theme.breakpoints.yml`:
- `sm`: 640px+, `md`: 768px+, `lg`: 1024px+, `xl`: 1280px+, `2xl`: 1536px+

## Default Content Management

Uses `drupal/default_content` module.

### Export/Update Content
1. Add UUID to `server_default_content.info.yml` for new content
2. `ddev drush en server_default_content -y`
3. `ddev drush dcem server_default_content`
4. Commit YAML files
