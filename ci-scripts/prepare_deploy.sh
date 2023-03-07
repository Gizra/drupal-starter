#!/bin/bash

set -e

cd "$TRAVIS_BUILD_DIR" || exit 1

# Make Git operations possible.
cp travis-key ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa

# Authenticate with Terminus.
ddev config global --web-environment-add="TERMINUS_MACHINE_TOKEN=$TERMINUS_TOKEN"

export GIT_SSH_COMMAND="ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no"

git clone "$PANTHEON_GIT_URL" -b master .pantheon

ddev stop

# Expose some environment variables to DDEV to be able to notify on auto-deploy.
ddev config global --web-environment-add="TRAVIS_COMMIT_MESSAGE=$TRAVIS_COMMIT_MESSAGE"
ddev config global --web-environment-add="GITHUB_TOKEN=$GITHUB_TOKEN"

rm .ddev/config.local.yaml || true
ddev start

# Make the DDEV container aware of your SSH keys.
ddev auth ssh
ddev . terminus auth:login --machine-token="$TERMINUS_TOKEN"
