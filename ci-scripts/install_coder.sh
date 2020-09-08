#!/bin/sh
set -e

# ---------------------------------------------------------------------------- #
#
# Installs The coder library so we can use t for code reviews.
#
# ---------------------------------------------------------------------------- #

cd "$TRAVIS_BUILD_DIR"
COMPOSER_MEMORY_LIMIT=-1 composer global require drupal/coder:^8.3.10
phpcs --config-set installed_paths ~/.config/composer/vendor/drupal/coder/coder_sniffer
