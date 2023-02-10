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

# The list of repos to patch comes from an environment variable.
# If the variable is not set, print instructions and exit.
if [ -z "$REPOSITORIES" ]; then
  echo "The REPOSITORIES environment variable is not set."
  echo "For example:"
  echo "export REPOSITORIES=\"client1 client2 client3\""
  exit 1
fi

# Make sure if are not in a repository root, if .git directory exists,
# ask to execute it from elsewhere.
if [ -d .git ]; then
  echo "You are in a repository root, please execute this script from"
  echo "a directory that contains your working copies. for example:"
  echo "cd /home/User/gizra"
  exit 1
fi

# Convert the string to an array.
REPOSITORIES=($REPOSITORIES)

# Clone or update each repository
for REPO in "${REPOSITORIES[@]}"
do
  cd "$BASE_DIR" || exit 1
  DEFAULT_BRANCH=$(curl -s -H "Authorization: token $GH_TOKEN" "https://api.github.com/repos/Gizra/$REPO" | jq -r '.default_branch')
  echo "Patching $REPO ($DEFAULT_BRANCH is the default branch)"

  if [ ! -d "$REPO" ]; then
    git clone "git@github.com:Gizra/$REPO.git" || continue
  fi

  cd "$REPO" || continue
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
       -d '{"title":"Apply patch '$PATCH_FILE'", "head":"'$BRANCH_NAME'", "base":"'$DEFAULT_BRANCH'"}' \
       "https://api.github.com/repos/Gizra/$REPO/pulls"
  echo "$REPO is completed"
  echo ""
  # We have various rate limiting on GitHub, so let's sleep a bit.
  sleep 5
done
