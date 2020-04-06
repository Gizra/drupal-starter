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

## First Config Export

The config for the `server` profile was created out of the `Standard` profile. To have the intial config as a different one, follow the following steps. We'll use `demo_umami` as example.

    # Re-install drupal based on the profile.
    ddev . drush site-install demo_umami -y
    
    # Delete existing config
    rm -rf config/sync
    mkdir config/sync
    
    # Let Drupal know about our config directory.
    ddev exec "echo \"\$settings['config_sync_directory'] = '../config/sync';\" >> /var/www/html/web/sites/default/settings.ddev.php"
    
    # Re-export config
    ddev exec drush cex
    
Next, in the replace the profile name with `server` in [here](https://github.com/amitaibu/drupal-static-elasticsearch/blob/35ab12438ca89966f70740adb3157fdd70b70509/config/sync/core.extension.yml#L45) and [here](https://github.com/amitaibu/drupal-static-elasticsearch/blob/35ab12438ca89966f70740adb3157fdd70b70509/config/sync/core.extension.yml#L51)

You can now `ddev restart` and the new installation should use the `server` profile, along with the new exported config.
