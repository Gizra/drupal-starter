#!/bin/bash
set -e

ENV="${1:-TEST}"

ddev . terminus remote:drush "drupal-starter-site.$ENV" -- cr
