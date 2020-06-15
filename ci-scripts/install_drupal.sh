#!/usr/bin/env bash
set -e

# -------------------------------------------------- #
# Installing Profile.
# -------------------------------------------------- #
echo "Install Drupal."

cp .ddev/config.local.yaml.example .ddev/config.local.yaml
ddev restart || ddev logs
