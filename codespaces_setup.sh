#!/bin/bash
set -x

wait_for_docker() {
  # Loop until Docker responds, indicating it's ready
  while true; do
    docker ps > /dev/null 2>&1 && break
    sleep 1
  done
  echo "Docker is ready."
}

wait_for_docker

# Remove lynx to prevent it opening a GUI while installing, which
# would cause the build to get stuck after the `ddev restart`.
echo "Removing lynx..."
sudo apt-get remove -y lynx 2>/dev/null || echo "lynx not found, skipping..."

# Proceed with commands requiring Docker
echo "Running ddev composer install..."
ddev composer install
cp .ddev/config.local.yaml.example .ddev/config.local.yaml

# As we have a `ddev login` in the end of the ddev restart, it fails on codespace.
# So we force a success exit code to avoid the build to fail.
ddev restart -y || true
