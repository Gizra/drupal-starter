#!/usr/bin/env bash

echo "Logging into Docker Hub if the password is set"
if [ -z "${DOCKER_PASSWORD}" ]; then
  echo "No Docker Hub password set, skipping login."
else
  docker login --password "$DOCKER_PASSWORD" --username amitaibu
fi

DDEV_VERSION="v1.24.1"

if ! command -v ddev &>/dev/null; then
    echo "Installing ddev."
    curl -s -LO https://raw.githubusercontent.com/drud/ddev/master/scripts/install_ddev.sh && bash install_ddev.sh $DDEV_VERSION
    rm install_ddev.sh
else
    echo "ddev $DDEV_VERSION is already installed."
fi

# Upon travis_retry, have a fresh start.
docker system prune -a --volumes -f

echo "Configuring ddev."
mkdir ~/.ddev
cp "ci-scripts/global_config.yaml" ~/.ddev/

# Check if the Docker network exists before attempting to create it
if ! docker network inspect ddev_default &>/dev/null; then
    echo "Creating Docker network ddev_default."
    if ! docker network create ddev_default; then
        echo "Failed to create Docker network ddev_default."
        ddev logs
        exit 1
    fi
else
    echo "Docker network ddev_default already exists."
fi

echo "Running ddev composer install."
if ! ddev composer install; then
    echo "ddev composer install failed."
    ddev logs
    exit 1
fi

if [ -n "$ROLLBAR_SERVER_TOKEN" ]; then
  ddev config global --web-environment-add="ROLLBAR_SERVER_TOKEN=$ROLLBAR_SERVER_TOKEN"
fi

echo "DDEV installation completed successfully."
