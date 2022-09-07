#!/bin/bash

set -e

cd "$TRAVIS_BUILD_DIR" || exit 1

# Make Git operations possible.
cp travis-key ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa

# Authenticate with Terminus.
cat >> ~/.ddev/global_config.yaml << EOF
web_environment:
  - TERMINUS_MACHINE_TOKEN=$TERMINUS_TOKEN
EOF

export GIT_SSH_COMMAND="ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no"

git clone "$PANTHEON_GIT_URL" -b master .pantheon

# Make the DDEV container aware of your SSH keys.
ddev auth ssh
