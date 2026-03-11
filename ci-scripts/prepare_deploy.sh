#!/bin/bash

set -e
set -x

cd "${GITHUB_WORKSPACE:-.}" || exit 1

# SSH key should already be set up by the workflow in ~/.ssh/pantheon-key
# and copied to .ddev/homeadditions/.ssh/
if [ ! -f ~/.ssh/pantheon-key ]; then
  echo "Error: SSH key not found at ~/.ssh/pantheon-key"
  exit 1
fi

# Authenticate with Terminus.
ddev config global --web-environment-add="TERMINUS_MACHINE_TOKEN=$TERMINUS_TOKEN"

export GIT_SSH_COMMAND="ssh -i ~/.ssh/pantheon-key -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no"

if [ -z "$PANTHEON_GIT_URL" ]; then
  echo "Error: PANTHEON_GIT_URL is not set. Add it to workflow secrets/env vars"
  exit 1
fi

if [[ ! -d .pantheon ]]; then
  git clone "$PANTHEON_GIT_URL" -b master .pantheon
fi

ddev stop

# Expose some environment variables to DDEV to be able to notify on auto-deploy.
# Use COMMIT_MESSAGE from workflow env, fall back to GITHUB_COMMIT_MESSAGE for backward compatibility.
DEPLOY_COMMIT_MESSAGE="${COMMIT_MESSAGE:-${GITHUB_COMMIT_MESSAGE:-}}"
# Make sure commit message variable does not contain special characters:
# { , } , [ , ] , & , * , # , ? , | , - , < , > , = , ! , % , @ , ", ', `
# and comma itself.
# These could break the YAML/Bash syntax.
# shellcheck disable=SC2001
DEPLOY_COMMIT_MESSAGE=$(echo "$DEPLOY_COMMIT_MESSAGE" | tr '\n' ' ' | sed -e 's/[{},&*?|<>=%@\"'\''`-]//g')

if [ -n "$DEPLOY_COMMIT_MESSAGE" ]; then
  # Use TRAVIS_COMMIT_MESSAGE for backward compatibility with DeploymentTrait.
  ddev config global --web-environment-add="TRAVIS_COMMIT_MESSAGE=$DEPLOY_COMMIT_MESSAGE"
fi

ddev config global --web-environment-add="GITHUB_TOKEN=$GITHUB_TOKEN"

if [ -n "${DEPLOY_EXCLUDE_WARNING:-}" ]; then
  ddev config global --web-environment-add="DEPLOY_EXCLUDE_WARNING=$DEPLOY_EXCLUDE_WARNING"
fi

rm .ddev/config.local.yaml || true
ddev start

# Make the DDEV container aware of your SSH keys.
ddev auth ssh
ddev . terminus auth:login --machine-token="$TERMINUS_TOKEN"
