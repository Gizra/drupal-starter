#!/usr/bin/env bash
set -e

echo "Logging into Docker Hub"
docker login --password "$DOCKER_PASSWORD" --username amitaibu

echo "Install ddev."
curl -s -L https://raw.githubusercontent.com/drud/ddev/master/scripts/install_ddev.sh | bash

echo "Configuring ddev."
mkdir ~/.ddev
cp "ci-scripts/global_config.yaml" ~/.ddev/
docker network create ddev_default || ddev logs

ddev composer install || ddev logs
