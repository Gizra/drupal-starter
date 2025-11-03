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

- **Use Drupal APIs**: Leverage Entity API, Form API, Cache API, Database API
- **Dependency Injection**: Use services container for reusable logic
- **Entity API**: Use for all data modeling and content operations
- **Configuration Management**: Use Configuration entities for admin-configurable features
- **Proper Hooks**: Implement hooks following conventions (`hook_form_alter`, `hook_theme`, etc.)
- **Render Arrays**: Use Render API for all HTML output
- **Translation**: Use `t()`, `\Drupal::translation()` for user-facing strings
- **Caching**: Implement Cache API with proper cache tags and contexts
- **Plugin System**: Create plugins for extensibility
- **PHPDoc Comments**: Document all public functions
- **Form Validation**: Use Form API validation handlers

### Patterns to Avoid 🚫

- **Direct Database Queries**: Use Entity Query or Database API instead
- **Hardcoded Strings**: Use configuration or translation system
- **Raw HTML Output**: Use render arrays, not `print` or `echo`
- **Global Variables**: Use dependency injection and services
- **jQuery**: Use vanilla JavaScript and ES6+ unless absolutely necessary
- **Bypassing Permissions**: Never bypass Drupal's permission system
- **Ignoring Cache Invalidation**: Always invalidate caches properly
- **Writing Outside Designated Directories**: Respect Drupal's file structure

### Security & Access Control

- **Always implement proper access control** for custom functionality
- **Validate all inputs** and sanitize outputs consistently
- **Use permission system**: Check user permissions before granting access
- **Sanitize output**: Use appropriate sanitization (`Xss::filter`, `Html::escape`)
- **Validate forms**: Use Form API validation
- **Use render arrays**: They provide automatic sanitization
- **Check entity access**: Use `$entity->access()` before operations

### Frontend Development

- **Prefer Vanilla JavaScript**: Use ES6+ features over jQuery
- **Drupal Behaviors**: Use `Drupal.behaviors` for proper initialization and AJAX compatibility
- **Native DOM APIs**: Prefer modern DOM APIs over legacy jQuery patterns
- **Progressive Enhancement**: Ensure functionality works without JavaScript when possible

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

## Project Structure

### Custom Modules
- **server_general**: Core functionality, entity view builders, utilities
- **server_migrate**: CSV-based migrations for demo/default content
- **server_style_guide**: Style guide for frontend components
- **server_default_content**: Default content using `default_content` module

### Key Technologies
- **DDEV**: Local development environment
- **Robo**: Task automation (PHP-based, preferred over bash)
- **Pluggable Entity View Builder (PEVB)**: Entity rendering pattern
- **Tailwind CSS 3.x**: Theme styling (JIT mode)
- **Drupal Test Traits (DTT)**: Testing framework
- **ParaTest**: Parallel test execution

## Development Workflow

### Starting Development
```bash
ddev start              # Start DDEV environment
ddev login              # Log in as admin user
```

### Running Tests
```bash
ddev phpunit                                    # Run all tests in parallel
ddev phpunit --filter ServerGeneralHomepageTest # Run specific test file
ddev phpunit --filter testMethodName            # Run specific test method
```

Tests marked with `@group sequential` run separately from parallel tests.

### Theme Development
```bash
ddev theme:watch        # Watch and compile Tailwind (JIT mode)
ddev robo theme:compile # Production compile with purge
```

Theme structure:
- `web/themes/custom/server_theme/src/` - Source files
- `web/themes/custom/server_theme/dist/` - Compiled assets (gitignored)

### Migrations
```bash
ddev drush en server_migrate -y
ddev drush config:import --partial --source=modules/custom/server_migrate/config/install/ -y
ddev drush migrate:rollback --all
ddev drush migrate:import --group server
ddev drush set-homepage
```

### Translation Management

**UI Translations** (`config/po_files/*.po`):
- Import manually: `ddev robo locale:import`
- Export: Enable potx, then `ddev drush potx --files path/to/file.php`

**Config Translations** (`config/po_files/*_config.po`):
- Export: Update `managed-config.txt`, then `ddev robo locale:export-from-config`
- Import: `ddev robo locale:import-to-config` then `ddev drush config:export`

## Git & GitHub Workflow

### Pull Requests
When opening a PR for a specific issue:
```
#[issue number]

Brief summary of what was done
```

### Commit Messages
Keep short - summarize what was done, don't re-explain everything.

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

# Standalone commands (already included in deploy):
ddev drush cr            # Clear cache only
ddev drush cim           # Import config only
ddev drush updb          # Run database updates only
```

### Custom DDEV Commands
```bash
ddev phpunit [filter]                 # Run PHPUnit tests
ddev phpcs                            # Run PHP Code Sniffer
ddev phpstan                          # Run PHPStan static analysis
ddev phpunit-contrib <module_name>   # Run contrib module tests
```

## Testing

### Test Types

**Preferred approach**: Use `weitzman\DrupalTestTraits\ExistingSiteBase` for tests in this project, rather than Kernel or Unit tests. ExistingSite tests run against a real Drupal installation and are faster and more practical for integration testing.

1. **Drupal Test Traits (DTT)**: Fast tests on existing installation using `ExistingSiteBase`
2. **Selenium Tests**: Headless Chrome with JavaScript support
3. **Parallel Tests**: ParaTest runs tests concurrently
4. **Sequential Tests**: Mark with `@group sequential` for tests that must run alone

## Architecture Patterns

### Pluggable Entity View Builder (PEVB)

**Important**: This project does NOT use standard Drupal rendering with view modes configured in the UI. All entity rendering is handled through PEVB plugins defined in code.

Entity rendering via PEVB plugins. Example:
- `web/modules/custom/server_general/src/Plugin/EntityViewBuilder/NodeLandingPage.php`

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

### Export New Content
1. Create entity in fresh installation
2. Get entity UUID
3. Add UUID to `server_default_content.info.yml`
4. `ddev drush en server_default_content -y`
5. `ddev drush dcem server_default_content`
6. Commit new YAML files

### Update Existing Content
Run steps 4-5 above. Uses mass export to avoid inconsistency.
