# Drupal 8 Starter

Starter repo for Drupal 8 development

## Requirements

* [DDEV](https://ddev.readthedocs.io/en/stable/)

## Installation

    git clone git@github.com:amitaibu/drupal8-starter.git
    cd drupal8-starter
    ddev composer install
    cp .ddev/config.local.yaml.example .ddev/config.local.yaml
    ddev restart


### Troubleshooting

If you had a previous installation of this repo, and have an error similar to `composer [install] failed, composer command failed: failed to load any docker-compose.*y*l files in /XXX/multi-repo/.ddev: err=<nil>. stderr=`

then execute the following, and re-try installation steps.

    ddev rm --unlist

## Code Sniffer

    ddev run_coder
