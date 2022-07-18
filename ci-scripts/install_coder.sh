#!/bin/sh
set -e

# ---------------------------------------------------------------------------- #
#
# Installs the coder library so we can use it for code reviews.
#
# ---------------------------------------------------------------------------- #

cd "$TRAVIS_BUILD_DIR"
COMPOSER_MEMORY_LIMIT=-1 composer global require drupal/coder:^8.3.14
composer global config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
