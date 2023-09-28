#!/usr/bin/env bash

echo "Logging into Docker Hub if the password is set"
if [ -z "${DOCKER_PASSWORD}" ]; then
  echo "No Docker Hub password set, skipping login."
else
  docker login --password "$DOCKER_PASSWORD" --username amitaibu
fi

# Make sure we can repeat this script using travis_retry.
docker network rm ddev_default || true
rm -rf ~/.ddev || true

echo "Install ddev."
curl -s -L https://raw.githubusercontent.com/drud/ddev/master/scripts/install_ddev.sh | bash

echo "Configuring ddev."
mkdir ~/.ddev
cp "ci-scripts/global_config.yaml" ~/.ddev/

if ! docker network create ddev_default; then
  ddev logs
  exit 1
fi

if ! ddev composer install; then
  ddev logs
  exit 1
fi
