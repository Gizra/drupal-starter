#!/usr/bin/env bash
set -e

# -------------------------------------------------- #
# Run PHPUnit tests
# -------------------------------------------------- #
cd "$ROOT_DIR"
ddev phpunit

exit 0
