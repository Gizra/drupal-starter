#!/usr/bin/env bash
set -e

# -------------------------------------------------- #
# Run PHPUnit tests
# -------------------------------------------------- #
ddev phpunit --do-not-cache-result --testdox

exit 0
