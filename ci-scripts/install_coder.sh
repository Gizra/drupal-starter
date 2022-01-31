#!/bin/sh
set -e

# ---------------------------------------------------------------------------- #
#
# Installs the coder library so we can use it for code reviews.
#
# ---------------------------------------------------------------------------- #

cd "$TRAVIS_BUILD_DIR"
COMPOSER_MEMORY_LIMIT=-1 composer global require slevomat/coding-standard:^7.0
COMPOSER_MEMORY_LIMIT=-1 composer global require drupal/coder:^8.3.10
phpcs --config-set installed_paths "$(readlink -f ~/.config/composer/vendor/drupal/coder/coder_sniffer)","$(readlink -f ~/.config/composer/vendor/slevomat/coding-standard)"
