#!/usr/bin/env bash
set -e

# Load helper functionality.
source ci-scripts/helper_functions.sh

# -------------------------------------------------- #
# Run PHPUnit tests
# -------------------------------------------------- #
cd "$ROOT_DIR"
ddev exec ../vendor/bin/phpunit -c ../phpunit.xml.dist modules/custom

exit 0
