#!/bin/bash
set -ex

wait_for_docker() {
  # Loop until Docker responds, indicating it's ready
  while true; do
    docker ps > /dev/null 2>&1 && break
    sleep 1
  done
  echo "Docker is ready."
}

wait_for_docker

# Proceed with commands requiring Docker
ddev composer install
cp .ddev/config.local.yaml.example .ddev/config.local.yaml
ddev restart -y
ddev describe
