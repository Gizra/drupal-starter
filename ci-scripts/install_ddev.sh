#!/usr/bin/env bash
set -e

# -------------------------------------------------- #
# Installing ddev dependencies.
# -------------------------------------------------- #
echo "Install Docker Compose."
sudo rm /usr/local/bin/docker-compose
curl -s -L "https://github.com/docker/compose/releases/download/1.24.1/docker-compose-$(uname -s)-$(uname -m)" > docker-compose
chmod +x docker-compose
sudo mv docker-compose /usr/local/bin

echo "Install mkcert."
wget -nv https://github.com/FiloSottile/mkcert/releases/download/v1.4.0/mkcert-v1.4.0-linux-amd64
sudo mv mkcert-v1.4.0-linux-amd64 /usr/bin/mkcert
chmod +x /usr/bin/mkcert
mkcert -install

echo "Install ddev."
curl -s -L https://raw.githubusercontent.com/drud/ddev/master/scripts/install_ddev.sh | bash

# -------------------------------------------------- #
# Configuring ddev.
# -------------------------------------------------- #
echo "Configuring ddev."
mkdir ~/.ddev
cp "ci-scripts/global_config.yaml" ~/.ddev/
docker network create ddev_default || ddev logs

ddev composer install || ddev logs
