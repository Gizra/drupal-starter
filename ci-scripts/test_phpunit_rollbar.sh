#!/usr/bin/env bash
set -e

if [ -z "$ROLLBAR_SERVER_TOKEN" ]; then
  echo "ROLLBAR_SERVER_TOKEN is not set or empty. Exiting early."
  exit 0
fi

if [ -z "$ROOT_DIR" ]; then
  # If ROOT_DIR is not set, set it to the current script's parent directory.
  ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )/.."
fi

cd "$ROOT_DIR"
cp ci-scripts/memory-limit-minimal.php.ini "$ROOT_DIR"/.ddev/php/memory-limit.php.ini
rm -rf .ddev/config.local.yaml
ddev restart
git checkout web/sites/default/settings.ddev.php
cat ci-scripts/settings_rollbar.php >> web/sites/default/settings.ddev.php
ddev drush pm:enable server_rollbar_test --yes
ddev phpunit --do-not-cache-result --testdox --group=Rollbar
git checkout "$ROOT_DIR"/.ddev/php/memory-limit.php.ini
git checkout web/sites/default/settings.ddev.php
ddev restart
ddev drush pm:uninstall server_rollbar_test --yes
exit 0
