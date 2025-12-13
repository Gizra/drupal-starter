#!/bin/bash

set -e
set -x

cd "$GITHUB_WORKSPACE" || exit 1

# Make Git operations possible.
cp pantheon-key ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa

# Authenticate with Terminus.
ddev config global --web-environment-add="TERMINUS_MACHINE_TOKEN=$TERMINUS_TOKEN"

export GIT_SSH_COMMAND="ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no"

if [ -z "$PANTHEON_GIT_URL" ]; then
  echo "Error: PANTHEON_GIT_URL is not set. Add it to workflow secrets/env vars"
  exit 1
fi

if [[ ! -d .pantheon ]]; then
  git clone "$PANTHEON_GIT_URL" -b master .pantheon
fi

ddev stop

# Expose some environment variables to DDEV to be able to notify on auto-deploy.
COMMIT_MESSAGE="${GITHUB_COMMIT_MESSAGE}"
# Make sure commit message variable does not contain special characters:
# { , } , [ , ] , & , * , # , ? , | , - , < , > , = , ! , % , @ , ", ', `
# and comma itself.
# These could break the YAML/Bash syntax.
# shellcheck disable=SC2001
COMMIT_MESSAGE=$(echo "$COMMIT_MESSAGE" | tr '\n' ' ' | sed -e 's/[{},&*?|<>=%@\"'\''`-]//g')
ddev config global --web-environment-add="GITHUB_COMMIT_MESSAGE=$COMMIT_MESSAGE"
ddev config global --web-environment-add="GITHUB_TOKEN=$GITHUB_TOKEN"
if [ -n "${DEPLOY_EXCLUDE_WARNING}" ]; then
  ddev config global --web-environment-add="DEPLOY_EXCLUDE_WARNING=$DEPLOY_EXCLUDE_WARNING"
fi

rm .ddev/config.local.yaml || true
ddev start

# Make the DDEV container aware of your SSH keys.
ddev auth ssh
ddev . terminus auth:login --machine-token="$TERMINUS_TOKEN"
