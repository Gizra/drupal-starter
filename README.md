# Drupal 8 Starter

Starter repo for Drupal 8 development

## Requirements

* [DDEV](https://ddev.readthedocs.io/en/stable/)

## Installation

    ddev composer install
    cp .ddev/config.local.yaml.example .ddev/config.local.yaml
    ddev restart

### Troubleshooting

If you had a previous installation of this repo, and have an error similar to `composer [install] failed, composer command failed: failed to load any docker-compose.*y*l files in /XXX/multi-repo/.ddev: err=<nil>. stderr=`

then execute the following, and re-try installation steps.

    ddev rm --unlist

## Theme development

By default, `ddev restart` compiles the theme using Robo.

On the local development environment, execute:
```bash
ddev robo watch:theme-debug
```

Then the modifications of the theme will be watched and compiled. The `-debug` suffix ensures that the CSS code remains human-readable,
and includes a sourcemap.

If you just need to re-compile the theme, `ddev robo compile:theme` is sufficient.

The directory structure:
 - `src/` - put all source stylesheets images, fonts, etc here.
 - `dist/` - `.gitignore`-ed path where the compiled / optimized files live, the theme should refer the assets from that directory.

For theme development, it's advisable to entirely turn off caching: https://www.drupal.org/node/2598914

## Code Sniffer

    ddev run_coder

## Tests

    ddev phpunit
