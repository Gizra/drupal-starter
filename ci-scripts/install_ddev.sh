#!/usr/bin/env bash
set -e

echo "Logging into Docker Hub if the password is set"
if [ -z "${DOCKER_PASSWORD}" ]; then
  echo "No Docker Hub password set, skipping login."
else
  docker login --password "$DOCKER_PASSWORD" --username amitaibu
fi

echo "Install ddev."
curl -s -L https://raw.githubusercontent.com/drud/ddev/master/scripts/install_ddev.sh | bash

echo "Configuring ddev."
mkdir ~/.ddev
cp "ci-scripts/global_config.yaml" ~/.ddev/
docker network create ddev_default || ddev logs

ddev composer install || ddev logs
