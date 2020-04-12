#!/usr/bin/env bash
set -e

# Load helper functionality.
source ci-scripts/helper_functions.sh

# -------------------------------------------------- #
# Run PHPUnit tests
# -------------------------------------------------- #
cd "$ROOT_DIR"
ddev phpunit

exit 0
