#!/bin/sh
set -e

# ---------------------------------------------------------------------------- #
#
# Installs the coder library so we can use it for code reviews.
#
# ---------------------------------------------------------------------------- #

cd "$TRAVIS_BUILD_DIR"
composer global config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
COMPOSER_MEMORY_LIMIT=-1 composer global require drupal/coder:^8.3.14
