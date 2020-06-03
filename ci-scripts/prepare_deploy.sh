#!/bin/bash

set -e

cd "$TRAVIS_BUILD_DIR" || exit 1

# Make Git operations possible.
cp travis-key ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa

# Authenticate with Terminus.
ddev auth pantheon "$TERMINUS_TOKEN"

ssh-keyscan -p 2222 "$GIT_HOST" >> ~/.ssh/known_hosts

git clone "$PANTHEON_GIT_URL" .pantheon

# Make the DDEV container aware of your ssh.
ddev auth ssh
