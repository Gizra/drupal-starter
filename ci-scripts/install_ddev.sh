#!/usr/bin/env bash
set -e

echo "Logging into Docker Hub"
docker login --password "$DOCKER_PASSWORD" --username amitaibu

echo "Install mkcert."
wget -nv https://github.com/FiloSottile/mkcert/releases/download/v1.4.0/mkcert-v1.4.0-linux-amd64
sudo mv mkcert-v1.4.0-linux-amd64 /usr/bin/mkcert
chmod +x /usr/bin/mkcert
mkcert -install

echo "Install ddev."
curl -s -L https://raw.githubusercontent.com/drud/ddev/master/scripts/install_ddev.sh | bash

echo "Configuring ddev."
mkdir ~/.ddev
cp "ci-scripts/global_config.yaml" ~/.ddev/
docker network create ddev_default || ddev logs

ddev composer install || ddev logs
