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
- **Tailwind CSS**: Theme styling (JIT mode)
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
ddev drush cr            # Clear cache
ddev drush cex           # Export config
ddev drush cim           # Import config
ddev drush updb          # Run database updates
ddev drush uli           # Generate login link
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
1. **Drupal Test Traits (DTT)**: Fast tests on existing installation
2. **Selenium Tests**: Headless Chrome with JavaScript support
3. **Parallel Tests**: ParaTest runs tests concurrently
4. **Sequential Tests**: Mark with `@group sequential` for tests that must run alone

## Architecture Patterns

### Pluggable Entity View Builder (PEVB)
Entity rendering via PEVB plugins. Example:
- `web/modules/custom/server_general/src/Plugin/EntityViewBuilder/NodeLandingPage.php`

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
