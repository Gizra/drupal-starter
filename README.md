# Drupal 8 & 9 Starter

Starter repo for Drupal 8 & 9 development. This starter is an opinionated approach,
with the following concepts and tools:

1. [ddev](https://ddev.readthedocs.io/) should be the only requirement, and
every operation should happen inside ddev's containers. For example, one should
not ever need to execute commands such as `composer install` from the host
machine. Instead we have `ddev composer install`. The advantage is that we have
a consistent, reproducible and shareable environment, so developers don't have
to lose time over configuration of their host machine.
1. [Robo](https://robo.li/) is the task manager, and is favored over bash
scripts. The reason for this is that it's
assumed PHP developers are more comfortable with PHP than Bash, and it provides
us with easier iteration, reading and manipulating yaml files, pre-defined
[tasks](https://robo.li/tasks/Assets/), etc.
1. We use Travis-CI for continuous integration. A pre-configured and working
`.travis.yaml` is part of this repo.
1. We use Pantheon for hosting. A `ddev robo deploy:pantheon` will take care of
deployments. See more under "Pantheon settings" section.

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
ddev robo theme:watch-debug
```

Then the modifications of the theme will be watched and compiled. The `-debug` suffix ensures that the CSS code remains human-readable,
and includes a sourcemap.

If you just need to re-compile the theme, `ddev robo theme:compile` is sufficient.

The directory structure:
 - `src/` - put all source stylesheets images, fonts, etc here.
 - `dist/` - `.gitignore`-ed path where the compiled / optimized files live, the theme should refer the assets from that directory.

For theme development, it's advisable to entirely turn off caching: https://www.drupal.org/node/2598914

## PHPCS (Code Sniffer)

    ddev phpcs

## Tests

For testing we use [Drupal Test Traits](https://medium.com/massgovdigital/introducing-drupal-test-traits-9fe09e84384c) (DTT), as it allows a very fast and convinent way of testing existing installation profiles.
See the [example](https://github.com/Gizra/drupal8-starter/blob/master/web/modules/custom/server_general/tests/src/ExistingSite/ServerGeneralExampleTest.php) test.

    ddev phpunit

## Deploy to Pantheon

After first deploy, you will want to install the site. We've noticed that
it gives an error, but after cache-clear, the site can be accessed.

    terminus drush <your-site>.dev -- site-install server -y --existing-config
    terminus drush <your-site>.dev -- cr
    terminus drush <your-site>.dev -- uli

### Pantheon settings

To allow Pantheon work with composer managed sites and recognize the `web` directory.

https://pantheon.io/docs/nested-docroot#disable-one-click-updates

### Install

First we need to allow DDEV to authenticate with terminus. This is a one time
action you need to take, and it will apply for all your projects. See docs [here](https://ddev.readthedocs.io/en/latest/users/providers/pantheon/#authentication)

In short, create a [Machine token](https://dashboard.pantheon.io/users/#account/tokens/) and then

    ddev auth pantheon <YOUR TOKEN>

Next we need to grab a local copy of the Pantheon site, and have it placed
(yet git ignored) under this repository. Run this from the root directory:

    git clone ssh://codeserver.dev.<long-hash>.drush.in:2222/~/repository.git .pantheon

### Executing

In case you haven't done so before, make the DDEV container aware of your ssh

    ddev auth ssh

Then you can deploy with

    ddev robo deploy:pantheon
