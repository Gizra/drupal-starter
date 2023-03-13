#!/bin/bash

# Use on host machine, not inside DDEV.
# bash drupal-starter/scripts/mass_patch.sh gh_token /tmp/our-little-change.patch
# The working directory shall be the one with all the working copies.

# Check arguments.
if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <github_token> <patch_file>"
  exit 1
fi

GH_TOKEN="$1"
PATCH_FILE=$(realpath "$2")
PR_TITLE="$3"
if [ -z "$PR_TITLE" ]; then
  PR_TITLE="Apply patch $PATCH_FILE"
fi
BRANCH_NAME=$(basename "$PATCH_FILE" .patch)
BASE_DIR=$(pwd)

# Check if the patch file actually exists.
if [ ! -f "$PATCH_FILE" ]; then
  echo "The patch file does not exist."
  exit 1
fi

# Check if the branch name contains space, abort in that case.
if [[ "$BRANCH_NAME" == *" "* ]]; then
  echo "The branch name cannot contain spaces."
  exit 1
fi

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
if [ -f "$SCRIPT_DIR/mass_patch.config.sh" ]; then
  # shellcheck source=/dev/null
  source "$SCRIPT_DIR/mass_patch.config.sh"
fi

# The list of repos to patch comes from an environment variable
# or from the configfile.
# If the variable is not set, ask for the user input.
if [ -z "$REPOSITORIES" ]; then
  echo "Please enter the list of repositories to patch, separated by space."
  read -r REPOSITORIES
fi

# Make sure we are not in a repository root, by checking if .git directory exists,
if [ -d .git ]; then
  echo "You are in a repository root, please execute this script from"
  echo "a directory that contains your working copies. for example:"
  echo "cd /home/User/projects"
  exit 1
fi

# Convert the string to an array.
# shellcheck disable=SC2206
REPOSITORIES=($REPOSITORIES)

# Clone or update each repository
for REPO in "${REPOSITORIES[@]}"
do
  cd "$BASE_DIR" || exit 1

  cd "$REPO" || continue
  SLUG=$(git remote get-url origin | awk 'BEGIN { FS = ":" } ; { print $2 }' | sed s/.git$//)
  DEFAULT_BRANCH=$(curl -s -H "Authorization: token $GH_TOKEN" "https://api.github.com/repos/$SLUG" | jq -r '.default_branch')
  echo "Patching $REPO ($DEFAULT_BRANCH is the default branch)"

  echo "Cleaning $REPO repository"
  git config pull.rebase false
  git checkout "$DEFAULT_BRANCH"
  git fetch
  git reset --hard origin/"$DEFAULT_BRANCH" || continue
  git clean -f

  echo "Applying patch"
  git apply "$PATCH_FILE" || continue
  git checkout -b "$BRANCH_NAME" || continue
  git add .
  git commit -m "Apply patch $PATCH_FILE" || continue
  git push origin "$BRANCH_NAME"
  curl -H "Authorization: token $GH_TOKEN" \
       -X POST \
       -d '{"title":"'"$PR_TITLE"'", "head":"'"$BRANCH_NAME"'", "base":"'"$DEFAULT_BRANCH"'"}' \
       "https://api.github.com/repos/$SLUG/pulls"
  echo "$REPO is completed"
  echo ""
  # We have various rate limiting on GitHub, so let's sleep a bit.
  sleep 5
done
