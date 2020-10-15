#!/usr/bin/env bash
set -e

# -------------------------------------------------- #
# Installing Profile.
# -------------------------------------------------- #
echo "Install Drupal."

cp .ddev/config.local.yaml.example .ddev/config.local.yaml
ddev restart || ddev logs

if [ ! -f ./web/themes/custom/server_theme/dist/css/style.css ]; then
  echo "Theme compilation failed"
  exit 1
fi
