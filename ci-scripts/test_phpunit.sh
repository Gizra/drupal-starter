#!/usr/bin/env bash
set -e

# -------------------------------------------------- #
# Run PHPUnit tests
# -------------------------------------------------- #
ddev phpunit --do-not-cache-result --testdox || (ddev drush watchdog-show --count=1000 && exit 1)

exit 0
