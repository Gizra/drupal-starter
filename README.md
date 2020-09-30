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
deployments. See more under ["Deploy to Pantheon"](#deploy-to-pantheon) section.

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

## ElasticSearch

The starter kit comes out of the box with ElasticSearch. Search API is activated and DDEV provides an ElasticSearch instance, already configured to use a [stopwords](https://github.com/Gizra/drupal-starter/blob/master/config/elasticsearch/stopwords.txt) and a [synonyms](https://github.com/Gizra/drupal-starter/blob/master/config/elasticsearch/synonyms.txt) list. Also it creates 4 indices (QA, DEV, TEST and LIVE) to reflect our typical Pantheon setup. The site inside DDEV will use the DEV index.
To take a look, you can check these first:
 - https://drupal-starter.ddev.site:9201/
 - https://drupal-starter.ddev.site:9201/\_cat/indices - list of all indices
 - https://drupal-starter.ddev.site:9201/\_search - list of all documents

## PHPCS (Code Sniffer)

    ddev phpcs

## Tests

For testing we use [Drupal Test Traits](https://medium.com/massgovdigital/introducing-drupal-test-traits-9fe09e84384c) (DTT), as it allows a very fast and convinent way of testing existing installation profiles.
See the [example](https://github.com/Gizra/drupal8-starter/blob/master/web/modules/custom/server_general/tests/src/ExistingSite/ServerGeneralExampleTest.php) test.

    ddev phpunit

## Deploy to Pantheon

### Pantheon Setup

#### Authenticate with Terminus

First we need to allow DDEV to authenticate with terminus. This is a one time
action you need to take, and it will apply for all your projects. See docs [here](https://ddev.readthedocs.io/en/latest/users/providers/pantheon/#authentication)

In short, create a [Machine token](https://dashboard.pantheon.io/users/#account/tokens/) and then

    ddev auth pantheon <YOUR TOKEN>

#### Create your site

Then, you can create a new site in Pantheon which can also be done with a
[terminus command](https://pantheon.io/docs/guides/drupal8-commandline):

    ddev exec terminus site:create my-site "My Site" "Drupal 8"

#### Change to nested docroot structure
To allow Pantheon to work with composer managed sites and recognize the `web`
directory, we need to follow the [Pantheon instructions](https://pantheon.io/docs/nested-docroot#disable-one-click-updates)

When following the instructions, clone the pantheon repository in the required
location with this commmand:

    git clone ssh://codeserver.dev.<long-hash>.drush.in:2222/~/repository.git .pantheon

In order to successfully install and import configuration you will need to add the
config directory in the `web/sites/default/settings.php` file:

    $settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config/sync';

### Executing

In case you haven't done so before, make the DDEV container aware of your ssh

    ddev auth ssh

Then you can deploy with

    ddev robo deploy:pantheon

### Install the Site with the Profile

After first deploy, you will want to install the site:
`ddev robo deploy:pantheon-install-env dev`

This command is also useful if a deployment got stuck due to non-deployable
config changes, so it can reboot the environment from scratch.

## Deploy Environments

To Deploy to a Pantheon environment (e.g. TEST or LIVE) you can use

    # With no argument, deploys to TEST.
    ddev robo deploy:pantheon-sync

    # Deploy to LIVE.
    ddev robo deploy:pantheon-sync live

### Release notes

Deployments should imply a release, you can generate a release notes based on
tags. You can generate a changelog using

    ddev robo generate:release-notes

Or alternatively, you can specify a tag that's the base of the comparison.

    ddev robo generate:release-notes 0.1.2

One line in the changelog reflects one merged pull requests and the command
assembles it from the Git log.

## Automatic Deployment to Pantheon

In order to deploy upon every merge automatically by Travis, you shall:

1. Get a Pantheon machine token (using a dummy new Pantheon user ideally, one user per project for the sake of security): https://pantheon.io/docs/machine-tokens
1. `ddev deploy:config-autodeploy [your new token] [pantheon project name]`
1. `git commit -m "Deployment secrets and configuration"`
1. Add the public key in `travis-key.pub` to the newly created dummy Pantheon user: https://pantheon.io/docs/ssh-keys

Optionally you can specify which target branch you'd like to push on Pantheon, by default it's `master`, so the target is the DEV environment, but alternatively you can issue:
`ddev deploy:config-autodeploy [your new token] [pantheon project name] [gh_branch] [pantheon_branch]`
