[![Build Status](https://app.travis-ci.com/Gizra/drupal-starter.svg?branch=main)](https://app.travis-ci.com/Gizra/drupal-starter)

# Drupal 9 Starter

Starter repo for Drupal 9 development. This starter is an opinionated approach,
with the following concepts and tools:

1. [ddev](https://ddev.readthedocs.io/) should be the only requirement, and
every operation should happen inside ddev's containers. For example, one should
not ever need to execute commands such as `composer install` from the host
machine. Instead we have `ddev composer install`. The advantage is that we have
a consistent, reproducible and shareable environment, so developers don't have
to lose time over configuration of their host machine.
1. [Robo](https://robo.li/) is the task manager, and is favored over Bash
scripts. The reason for this is that it's
assumed PHP developers are more comfortable with PHP than Bash, and it provides
us with easier iteration, reading and manipulating yaml files, pre-defined
[tasks](https://robo.li/tasks/Assets/), etc.
1. We use Travis-CI for continuous integration. A pre-configured and working
`.travis.yaml` is part of this repo.
1. We use Pantheon for hosting. A `ddev robo deploy:pantheon` will take care of
deployments. See more under ["Deploy to Pantheon"](#deploy-to-pantheon) section.
1. We use [Pluggable Entity View Builder](https://www.drupal.org/project/pluggable_entity_view_builder) to define how an entity should look like. See [example](https://github.com/Gizra/drupal-starter/blob/main/web/modules/custom/server_general/src/Plugin/EntityViewBuilder/NodeLandingPage.php).

## GitPod

The project is integrated with [GitPod](https://www.gitpod.io/docs/).
Click on the badge above to try it out the project in action and start editing
the source code! By default Drupal is accessible publicly at `8888-` and you can access other
DDEV services like Mailhog using the non-HTTPS port, for instance `8025-` should work for
checking the outgoing mails.

## Requirements

* [DDEV](https://ddev.readthedocs.io/en/stable/)
* Optional but recommended: follow the "mkcert" [installation notes](https://ddev.readthedocs.io/en/stable/#installation) for local SSL

## Installation

    ddev composer install
    cp .ddev/config.local.yaml.example .ddev/config.local.yaml
    ddev restart

Once the Drupal installation is complete you can use `ddev login` to
log in to the site as user 1 using your default browser.

### Troubleshooting

If you had a previous installation of this repo, and have an error similar to `composer [install] failed, composer command failed: failed to load any docker-compose.*y*l files in /XXX/multi-repo/.ddev: err=<nil>. stderr=`

then execute the following, and re-try installation steps.

    ddev rm --unlist

## Theme Development

By default, `ddev restart` compiles the theme using Robo (`ddev robo theme:compile-debug`)

This is used only for watching Tailwind styles, it's not compiling js, images, etc.

On the local development environment, which is using TailWind's [JIT](https://tailwindcss.com/docs/just-in-time-mode) (Just-In-Time), execute:

```bash
ddev theme:watch
```

This will compile Tailwind and keep watching for any changes.

When running `ddev robo theme:compile` it will purge any TailWind's CSS class
which is not found in the code, twig, or under `tailwind.config.js` `whitelist` property.

The directory structure:
 - `src/` - put all source stylesheets images, fonts, etc here.
 - `dist/` - `.gitignore`-ed path where the compiled / optimized files live, the theme should refer the assets from that directory.

For theme development, it's advisable to entirely turn off caching: https://www.drupal.org/node/2598914

### Breakpoints and Responsive Images

It is advised to use Drupal's Responsive image module.

If there are new breakpoints added, or existing breakpoints updated in
`server_theme/tailwind.config.js`, you must ensure to also update the Drupal
breakpoints configuration file for the theme `server_theme.breakpoints.yml` so
that the media queries for the responsive images are in sync with tailwind's.
It is advisable to finalize this configuration before any responsive image
styles get added, otherwise you will need to ensure the existing responsive
image styles are also re-configured for the new/updated breakpoints.

Currently, the breakpoints are configured to follow [Tailwind's breakpoints](https://tailwindcss.com/docs/responsive-design)
for example `sm`, `md`, etc.

### How to get dimensions for, and configure the responsive image styles

This process should be done as a last step of a "wiring" of the frontend
component with Drupal. This is because we rely on the frontend component for our
dimensions. These are guidelines to usually follow, but not set in stone, it's
always a bit of a judgement call:

1. Figure out the rules.

   Each frontend component which outputs a user-uploaded content image is unique
design-wise, so you should study the design closely or contact the designer for
any clarifications of how an image should transform at various widths. For
example whether the image height is static (i.e. always `400px`), or whether
there is a max width for the full width hero image.
2. Figure out the biggest dimensions needed for each breakpoint.

   Now that we know the rules, we can figure out the biggest dimension image
needed for each of our breakpoints. You should always start with mobile. Go to
the styleguide page (or any output of the component) and set your browser to the
highest dimension of the breakpoint. For example on Mobile it's `639px`, because
at 640px the `sm` breakpoint starts, for `md` it's `1023px` as the `lg`
breakpoint starts at `1024px`. Now just check the image dimension output and
note the width and height.
3. Create the image styles for each breakpoint.

   Now that we have all the needed information, we can create the image styles.
For naming we use this style, but you're free to figure out your own method as
long as it's consistent:
    - Label: `[Component name] [breakpoint] [multiplier] ([width]x[height)`

      The width and height isn't necessary, we simply add it for aesthetics.
    - Machine name: `[component_name]_[breakpoint]_[multiplier]`
    - Example:
      - `Hero md 1x (900x600)` - `hero_md_1x` (Scale and Crop)
      - `Content image md 2x (1800w)` - `content_image_md_2x` (Scale only)

   Note: For the 2x multiplier, simply double the dimensions.
4. Create the responsive image style.

   Use the `server_theme`'s breakpoints when creating the responsive image style
and assign the image styles created in step3 to each breakpoint.
5. Finally use the responsive image style in the wire-up of the component with
Drupal. With PEVB, see `BuildFieldTrait::buildMediaResponsiveImage()`.

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

Follow the steps listed in `.ddev/providers/pantheon.yaml`.
Make sure to add the correct site name under `environment_variables.project`.

#### Create your site

Then, you can create a new site in Pantheon which can also be done with a
[terminus command](https://pantheon.io/docs/guides/drupal8-commandline):

    ddev exec terminus site:create my-site "My Site" "Drupal 9"

#### Change to nested docroot structure

To allow Pantheon to work with composer managed sites and recognize the `web`
directory, we need to follow the [Pantheon instructions](https://pantheon.io/docs/nested-docroot#disable-one-click-updates)

When following the instructions, clone the pantheon repository in the required
location with this command:

    git clone ssh://codeserver.dev.<long-hash>.drush.in:2222/~/repository.git .pantheon

In order to successfully install and import configuration you will need to add the
config directory in the `web/sites/default/settings.php` file:

    $settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config/sync';

### Executing

In case you haven't done so before, make the DDEV container aware of your ssh.

    ddev auth ssh

### Install the Site with the Profile

After first deploy, you will want to install the site:
`ddev robo deploy:pantheon-install-env dev`

This command is also useful if a deployment got stuck due to non-deployable
config changes, so it can reboot the environment from scratch.

### Pantheon's `settings.php`

During the deployments, `web/sites/default/settings.pantheon.php` gets copied
to `web/sites/default/settings.php` into the Pantheon repository, so any kind
of configuration override (SMTP credentials, dev mode alterations) can be
injected to that file.

## Deploy Environments

To Deploy to a Pantheon environment (e.g. TEST or LIVE) you can use

    # With no argument, deploys to TEST.
    ddev robo deploy:pantheon-sync

    # Deploy to LIVE.
    ddev robo deploy:pantheon-sync live

### Release notes

Deployments should imply a release, you can generate a release notes based on
tags.
In order to provide verbose release notes, it is required to [create a personal
access token](https://docs.github.com/en/github/authenticating-to-github/keeping-your-account-and-data-secure/creating-a-personal-access-token).
Then specify two [new environment variables for DDEV web container](https://ddev.readthedocs.io/en/stable/users/extend/customization-extendibility/#providing-custom-environment-variables-to-a-container):
 - `GITHUB_USERNAME`
 - `GITHUB_ACCESS_TOKEN`

Then you can generate a changelog using

    ddev robo generate:release-notes

Or you can specify a tag that's the base of the comparison.

    ddev robo generate:release-notes 0.1.2

One line in the changelog reflects one merged pull requests, and the command
assembles it from the Git log.

## Automatic Deployment to Pantheon

In order to deploy upon every merge automatically by Travis, you shall:

1. Initiate QA (`qa` branch) multidev environment for the given project.
1. Double-check if `./.ddev/providers/pantheon.yaml` contains the proper Pantheon project name.
1. Get a [Pantheon machine token](https://pantheon.io/docs/machine-tokens) (using a dummy new Pantheon user ideally, one user per project for the sake of security)
1. Get a GitHub Personal access token, it is needed for [Travis CLI to authenticate](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token). It will be used like this: `travis login --pro --github-token=`.
1. `ddev robo deploy:config-autodeploy [your terminus token] [your github token]`
1. `git commit -m "Deployment secrets and configuration"`
1. Add the public key in `travis-key.pub` to the newly created dummy [Pantheon user](https://pantheon.io/docs/ssh-keys)

Optionally you can specify which target branch you'd like to push on Pantheon, by default it's `master`, so the target is the DEV environment, but alternatively you can issue:
`ddev robo deploy:config-autodeploy [your terminus token] [your github token] [pantheon project name] [gh_branch] [pantheon_branch]`

## Pulling DB & Files From Pantheon

    ddev auth ssh

    # Pull DB & Files
    ddev pull pantheon

## Stage File Proxy
If you don't want to copy production files locally, you can enable stage_file_proxy module.
It saves you time and disk space by sending requests to your development environment's files directory
to the production environment and making a copy of the production file in your development site.

Configure the origin path at `/admin/config/system/stage_file_proxy`.

### Flood Control

As the project uses Redis, it is not possible to use the SQL console to reset flood table.
There's a custom DDEV command to help with that. Usages:

```bash
ddev pantheon-flood-flush
ddev ddev-flood-flush
```

Purges all the entries from Pantehon's `live` environment or DDEV's own Redis.

```bash
ddev pantheon-flood-flush test
```

Purges all the entries from Pantheon's `test` environment.

```bash
ddev pantheon-flood-flush test 193.165.2.3
ddev ddev-flood-flush 193.165.2.3
```

Purges entries related to IP `193.165.2.3` from Pantheon's `test` environment, or alternatively from DDEV's own Redis.
